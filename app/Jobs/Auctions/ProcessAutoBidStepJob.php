<?php

namespace App\Jobs\Auctions;

use App\Models\Auctions\AuctionAutoBid;
use App\Models\Auctions\AuctionEvent;
use App\Models\Auctions\AuctionLot;
use App\Services\Auctions\AuctionAutoBidService;
use App\Services\Auctions\AuctionBiddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAutoBidStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lotId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $lotId)
    {
        $this->lotId = $lotId;
    }

    /**
     * Execute the job.
     */
    public function handle(AuctionBiddingService $biddingService, AuctionAutoBidService $autoBidService): void
    {
        // 0. Release Concurrency Lock Early
        Cache::forget("auctions:auto_bid:pending:{$this->lotId}");

        DB::beginTransaction();
        try {
            // 1. Lock Row
            $lot = AuctionLot::lockForUpdate()->find($this->lotId);

            if (!$lot) {
                DB::rollBack();
                return;
            }

            // 2. Refresh Status/Time
            if ($lot->status !== 'live' || ($lot->ends_at && now()->gt($lot->ends_at))) {
                DB::commit();
                return;
            }

            // 3. Determine Min Required
            // The absolute minimum bid required to take the lead
            $minRequired = $lot->current_highest_bid 
                ? ($lot->current_highest_bid + $lot->min_increment)
                : $lot->starting_price;

            $winnerId = $lot->winner_user_id;

            // 4. Find Best Candidate
            // Must be enabled, not current winner
            // Removed strict 'max_bid >= minRequired' check from query so we can catch those who fail it and disable them
            // But to avoid processing thousands of low-ballers, we should target:
            // "Eligible ones" OR "Ones who think they are eligible but aren't anymore"?
            // Requirement: "if the candidate is eligible at selection time but at execution time cannot meet..."
            // "Eligible at selection time" implies "is_enabled=true".
            // So we fetch the highest enabled candidate regardless of max_bid logic?
            // No, we want the highest MAX bid to see if THAT person can win.
            // If the person with highest MAX bid cannot win, then NO ONE can win? 
            // (Assumes sorted by Max Bid). Yes.
            
            $candidate = AuctionAutoBid::where('auction_lot_id', $lot->id)
                ->where('is_enabled', true)
                ->where('user_id', '!=', $winnerId)
                ->orderBy('max_bid', 'desc')
                ->orderBy('updated_at', 'asc')
                ->first();

            if (!$candidate) {
                DB::commit();
                return;
            }

            // Requirement 1: Check if Max Bid Exceeded
            // We calculate what they NEED to bid.
            // Be careful: if they need 105, and max is 100 -> FAIL.
            // "next_required_bid = current + max(lot.min, auto.inc)" OR starting
            
            $candidateInc = max($lot->min_increment, $candidate->increment_amount);
            
            if ($lot->current_highest_bid) {
                $nextRequiredByCandidate = $lot->current_highest_bid + $candidateInc;
            } else {
                $nextRequiredByCandidate = $lot->starting_price;
            }
            
            // If they can't afford the strict next step
            // Wait, standard logic says we cap at max_bid?
            // "if bidAmount > candidate.max_bid => bidAmount = candidate.max_bid"
            // "if bidAmount < minRequired => do not bid"
            // The new requirement sets a STRICTER disable rule: 
            // "next_required_bid > auto_bid.max_bid" -> DISABLE.
            // Where next_required_bid is defined using THEIR increment.
            
            // Check condition:
            if ($nextRequiredByCandidate > $candidate->max_bid) {
                // AUTO-DISABLE
                $candidate->is_enabled = false;
                $candidate->save();
                
                // Logging
                AuctionEvent::create([
                    'auction_lot_id' => $lot->id, 
                    'actor_type' => 'system',
                    'actor_id' => $candidate->user_id, // Attributed to user or system? System acting on user.
                    'event_type' => 'auto_bid_disabled',
                    'payload' => [
                        'reason' => 'max_exceeded',
                        'required_bid' => (string)$nextRequiredByCandidate,
                        'max_bid' => (string)$candidate->max_bid
                    ]
                ]);

                DB::commit();
                
                // Since this candidate is now disabled, we should immediately re-check for the NEXT candidate?
                // Yes, logic implies we want to find a valid bidder if one exists.
                // Re-run scheduling immediately.
                 $this->checkAndScheduleNext($lot, $autoBidService);
                 return;
            }

            // If we are here, they CAN afford the bid.
            // Calculate actual bid amount (capped at max_bid)
            $bidAmount = $nextRequiredByCandidate;
            if ($bidAmount > $candidate->max_bid) {
                $bidAmount = $candidate->max_bid;
            }
            
            // Final safety check against GLOBAL min required (using lot min increment)
            // The candidate's increment might be smaller than lot min increment? 
            // Code `max($lot->min_increment, $candidate->increment_amount)` handles that.
            // So $nextRequiredByCandidate is safe.
            
            // 7. Place Bid
            $biddingService->placeBid(
                $lot,
                $candidate->user, 
                $bidAmount,
                'system',
                true
            );

            DB::commit();

            // 8. Re-evaluate Loop
            $lot->refresh(); 
            $this->checkAndScheduleNext($lot, $autoBidService);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AutoBid Job Error Lot {$this->lotId}: " . $e->getMessage());
        }
    }

    protected function checkAndScheduleNext(AuctionLot $lot, AuctionAutoBidService $service) 
    {
         if ($lot->status !== 'live') return;
         if ($lot->ends_at && now()->gt($lot->ends_at)) return;

         // We just verify if ANY enabled candidates remain who MIGHT be eligible
         // We don't do complex math here, just existence check.
         $winnerId = $lot->winner_user_id;

         $exists = AuctionAutoBid::where('auction_lot_id', $lot->id)
            ->where('is_enabled', true)
            ->where('user_id', '!=', $winnerId)
            ->exists();
            
         if ($exists) {
             $service->scheduleAutoBidStep($lot->id);
         }
    }
}
