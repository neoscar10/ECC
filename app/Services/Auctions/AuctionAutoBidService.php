<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionAutoBid;
use App\Models\Auctions\AuctionEvent;
use App\Models\Auctions\AuctionLot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuctionAutoBidService
{
    protected $biddingService;

    public function __construct(AuctionBiddingService $biddingService)
    {
        $this->biddingService = $biddingService;
    }

    /**
     * Set or update an auto-bid for a user.
     */
    public function setAutoBid(AuctionLot $lot, User $user, float $maxBid, float $incrementAmount): AuctionAutoBid
    {
        return DB::transaction(function () use ($lot, $user, $maxBid, $incrementAmount) {
             // 1. Check Permissions (Controller usually handles this via AccessResolver, but double check)
             // We assume caller checked 'can_auto_bid'.
             
             // 2. Upsert
             $autoBid = AuctionAutoBid::updateOrCreate(
                 ['auction_lot_id' => $lot->id, 'user_id' => $user->id],
                 [
                     'max_bid' => $maxBid,
                     'increment_amount' => $incrementAmount,
                     'is_enabled' => true,
                 ]
             );
             
             // 3. Log
             AuctionEvent::create([
                 'auction_lot_id' => $lot->id, 
                 'actor_type' => 'user',
                 'actor_id' => $user->id, 
                 'event_type' => 'auto_bid_set',
                 'payload' => ['max_bid' => $maxBid]
             ]);
             
             // 4. Trigger Immediate Evaluation
             // If this new auto-bid can beat the current high bid (if different user), do it.
             $this->processAutoBids($lot); // Recursive potential? 
             
             return $autoBid;
        });
    }

    /**
     * Process auto-bids for a lot.
     * Use when a new bid is placed OR a new auto-bid is set.
     */
    public function processAutoBids(AuctionLot $lot): void
    {
        // Prevent infinite loops or deep recursion: simple iteration loop
        // We need to lock the lot to read authoritative state
        
        $maxIterations = 20; // Safety break
        $iterations = 0;
        
        while ($iterations < $maxIterations) {
            $iterations++;
            
            DB::beginTransaction();
            try {
                // Refresh lot with lock
                $lot = AuctionLot::lockForUpdate()->find($lot->id);
                
                if ($lot->status !== 'live' || now()->gt($lot->ends_at)) {
                    DB::commit(); 
                    break;
                }

                $currentHigh = $lot->current_highest_bid ?? $lot->starting_price;
                $winnerId = $lot->winner_user_id;

                // Find eligible auto-bids that are NOT the current winner
                // and have max_bid > currentHigh + increment (or just > currentHigh if they are willing to bid)
                // Actually constraint: We need to beat currentHigh.
                // Required Bid = currentHigh + min_increment
                // Exception: if currentHigh is starting_price and no bids? handled in BiddingService.
                
                $minRequired = $lot->current_highest_bid 
                    ? ($lot->current_highest_bid + $lot->min_increment)
                    : $lot->starting_price;

                // Find the candidate with the highest max_bid who is NOT the current winner
                // We order by max_bid DESC, then updated_at ASC (FIFO for ties)
                $candidate = AuctionAutoBid::where('auction_lot_id', $lot->id)
                    ->where('is_enabled', true)
                    ->where('user_id', '!=', $winnerId) // Don't bid against self
                    ->where('max_bid', '>=', $minRequired)
                    ->orderBy('max_bid', 'desc')
                    ->orderBy('updated_at', 'asc')
                    ->first();
                
                if (!$candidate) {
                    DB::commit();
                    break; // No one else to bid
                }
                
                // Place bid for this candidate
                // Amount to bid? 
                // We want to be high enough to win, but not max out immediately unless forced.
                // We bid exactly $minRequired.
                // BUT, wait. If there are multiple auto-bidders?
                // Example: A has auto=500. B has auto=600. Current=100.
                // Candidate B is picked (highest max).
                // If we bid 110, A is still eligible (max > 110).
                // So next loop, A will be picked? No, A's max is 500. B is current winner.
                // Candidate query excludes winner. 
                // So loop 1: B bids 110. Winner=B.
                // Loop 2: A is candidate (max 500 > 110, not winner). A bids 120. Winner=A.
                // Loop 3: B is candidate (max 600 > 120, not winner). B bids 130.
                // This "ping-pong" is correct behavior for proxy bidding timeline, allows logging each step.
                // Efficiency: It might generate many rows. 20 iterations safety.
                // Is there a request to jump straight to result? 
                // "No bid can be placed below... atomic correctness... maintain leading position"
                // "Each system bid is stored... and logged" implies we want the history.
                
                // Amount: $minRequired.
                // Check if candidate's increment matters? 
                // "using increment_amount to maintain leading position"
                // Usually system uses LOT's min_increment to calculate next valid bid. 
                // User's increment_amount is usually for *their* logic? Or is it "I want to outbid by X"?
                // "using increment_amount" -> The user defined increment.
                // If user defines increment=10, and lot min_inc=5. 
                // Current=100. MinRequired=105. 
                // Should we bid 110 (100+10)? Or 105?
                // SRS says "using *increment_amount* to maintain leading position".
                // I will interpret this as: usage of user's personal increment if > lot increment?
                // Or simply: Bid = current + max(lot.min_inc, user.inc).
                
                $bidAmount = $lot->current_highest_bid 
                    ? ($lot->current_highest_bid + max($lot->min_increment, $candidate->increment_amount))
                    : $lot->starting_price;
                
                // Cap at max_bid
                if ($bidAmount > $candidate->max_bid) {
                     $bidAmount = $candidate->max_bid; // Last gasp
                }
                
                // Final Check: Is this valid? (must handle BiddingService check)
                // If bidAmount is still < minRequired (e.g. max_bid was lower than required), skip
                if ($bidAmount < $minRequired) {
                    // Candidate cannot afford next step
                    DB::commit();
                    // We need to exclude this candidate to avoid infinite loop -> but query handles "max_bid >= minRequired"
                    // So this block is theoretically unreachable unless concurrent update
                    break; 
                }

                // Place the bid via Bidding Service (it handles locking nested? No, we have lock).
                // BiddingService does `lockForUpdate`. Nested transactions/locks are tricky in MySQL.
                // If we call placeBid, it will try to lock again. 
                // Since we are in transaction, same session, re-entry lock should be fine.
                
                $this->biddingService->placeBid(
                    $lot, 
                    $candidate->user, 
                    $bidAmount, 
                    'system', 
                    true // isAuto
                );
                
                DB::commit(); // Commit this single step so it's visible? 
                // If we commit, we lose the outer lock? Yes.
                // So we release look, loop repeats, re-acquires lock. Perfect for "ping pong".
                
            } catch (\Exception $e) {
                DB::rollBack();
                // Log error
                \Illuminate\Support\Facades\Log::error("AutoBid Error: " . $e->getMessage());
                break;
            }
        }
    }
}
