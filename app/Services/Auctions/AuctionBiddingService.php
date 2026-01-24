<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionBid;
use App\Models\Auctions\AuctionEvent;
use App\Models\Auctions\AuctionLot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Events\AuctionBidPlaced;
use App\Events\AuctionExtended;

class AuctionBiddingService
{
    /**
     * Place a bid on an auction lot.
     * 
     * @param AuctionLot $lot
     * @param User $user
     * @param float $amount
     * @param string $source
     * @param bool $isAuto
     * @return AuctionLot
     * @throws \Exception
     */
    public function placeBid(AuctionLot $lot, User $user, float $amount, string $source = 'web', bool $isAuto = false): AuctionLot
    {
        return DB::transaction(function () use ($lot, $user, $amount, $source, $isAuto) {
            // 1. Lock Row
            $lot = AuctionLot::lockForUpdate()->find($lot->id);

            // 2. Validate Status & Time
            if ($lot->status !== 'live') {
                throw new \Exception("Auction is not live.");
            }
            if ($lot->ends_at && now()->gt($lot->ends_at)) {
                throw new \Exception("Auction has ended.");
            }

            // 3. Validate Amount
            // Must be strictly greater than current highest
            $currentData = $lot->current_highest_bid ?? $lot->starting_price;
            // If no bids yet, first bid must be >= starting price
            // If bids exist, must be >= current + min_increment
            
            // Logic:
            // If current_highest_bid is null, then min is starting_price.
            // If current_highest_bid is NOT null, then min is current + min_increment.
            
            $minRequired = $lot->current_highest_bid 
                ? ($lot->current_highest_bid + $lot->min_increment)
                : $lot->starting_price;
                
            // Floating point safety? Use bccomp or simplistic round
            if (round($amount, 2) < round($minRequired, 2)) {
                throw new \Exception("Bid amount {$amount} is too low. Minimum required: {$minRequired}");
            }

            // 4. Record Bid
            $bid = AuctionBid::create([
                'auction_lot_id' => $lot->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'source' => $source,
                'is_auto' => $isAuto,
                'placed_at' => now(),
            ]);

            // 5. Update Lot
            $lot->current_highest_bid = $amount;
            $lot->winner_user_id = $user->id; // Tentative winner

            // 6. Anti-Sniping Extension
            $extended = false;
            $extensionSeconds = 0;
            if ($lot->anti_sniping_enabled && $lot->ends_at) {
                $secondsRemaining = now()->diffInSeconds($lot->ends_at, false);
                if ($secondsRemaining <= $lot->trigger_window_seconds && $secondsRemaining > 0) {
                    // Check max extensions
                    if (is_null($lot->max_extensions) || $lot->extensions_used < $lot->max_extensions) {
                        $lot->ends_at = $lot->ends_at->addSeconds($lot->extend_by_seconds);
                        $lot->extensions_used++;
                        $extended = true;
                        $extensionSeconds = $lot->extend_by_seconds;
                    }
                }
            }

            $lot->save();

            // 7. Audit Event
            $timelineEvent = AuctionEvent::create([
                'auction_lot_id' => $lot->id,
                'actor_type' => $isAuto ? 'system' : 'user',
                'actor_id' => $isAuto ? null : $user->id,
                'event_type' => 'bid_placed',
                'payload' => [
                    'amount' => $amount,
                    'user_id' => $user->id,
                    'extended' => $extended,
                    'new_ends_at' => $lot->ends_at->toIso8601String(),
                ]
            ]);

            // 8. Fire Events (Realtime)
            event(new AuctionBidPlaced($lot, $amount, $user->id));
            if ($extended) event(new AuctionExtended($lot, 'anti_sniping'));
            
             // Fire Timeline Event
            if (isset($timelineEvent)) {
                 event(new \App\Events\AuctionTimelineEventCreated($timelineEvent));
            }

            return $lot;
        });
    }
}
