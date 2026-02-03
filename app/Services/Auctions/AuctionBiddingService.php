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
            event(new AuctionBidPlaced($bid));
            if ($extended) event(new AuctionExtended($lot, 'anti_sniping'));
            
             // Fire Timeline Event
            if (isset($timelineEvent)) {
                 event(new \App\Events\AuctionTimelineEventCreated($timelineEvent));
            }

            // 9. Notifications (FCM)
            try {
                // A) Public Topic: Bid Placed
                // Logic: Exclude 'actor_user_id' in client
                $formatter = new \App\Services\Notifications\AuctionNotificationFormatter();
                [$title, $body] = $formatter->bidPlaced($lot, $bid, $user, $isAuto);
                
                // Do not expose is_autobid to public
                $extraData = [
                    'bid_id' => $bid->id,
                    'bid_amount' => $bid->amount,
                    'currency' => $lot->currency,
                    'actor_user_id' => $user->id,
                    // 'new_ends_at' => $lot->ends_at is handled by 'ends_at' in builder if we want standard field.
                    // But current catalog has 'new_ends_at'. Let's keep 'new_ends_at' for backward compat 
                    // and 'ends_at' will be added by builder automatically.
                    'new_ends_at' => $lot->ends_at ? $lot->ends_at->toIso8601String() : null,
                ];

                $eventId = "bid_placed:{$bid->id}";
                $payload = $formatter->buildPayload($lot, 'bid_placed', $extraData, $eventId);

                // CONDITIONAL DISPATCH
                if ($isAuto) {
                    // 1. Auto-Bid: Manual Fan-out to enable subscribers (EXCLUDING owner)
                    // We do not send to topic because we want to exclude $user->id
                    $subscribers = \App\Models\Auctions\AuctionNotificationSubscription::where('auction_lot_id', $lot->id)
                        ->where('is_enabled', true)
                        ->where('user_id', '!=', $user->id) // Exclude current auto-bidder
                        ->pluck('user_id');

                    \Illuminate\Support\Facades\Log::info("AuctionBiddingService: Fan-out bid_placed for Auto-Bid", [
                        'lot_id' => $lot->id,
                        'bid_id' => $bid->id,
                        'excluded_user_id' => $user->id,
                        'recipients_count' => $subscribers->count()
                    ]);

                    foreach ($subscribers as $subUserId) {
                        dispatch(new \App\Jobs\Notifications\SendFcmToUserJob(
                            $subUserId,
                            $title,
                            $body,
                            $payload
                        ));
                    }

                } else {
                    // 2. Manual Bid: Send to Topic (includes owner, but that's standard/expected for manual)
                    $topic = \App\Support\Notifications\FcmTopicNamer::auctionTopic($lot);
                    
                    dispatch(new \App\Jobs\Notifications\SendFcmToTopicJob(
                        $topic,
                        $title,
                        $body,
                        $payload
                    ));
                }

                // B) Private Auto-Bid Notification (if auto)
                if ($isAuto) {
                    [$autoTitle, $autoBody] = $formatter->autoBidExecuted($lot, $bid, $user);
                    
                    $autoExtra = [
                        'bid_id' => $bid->id,
                        'bid_amount' => $bid->amount,
                        'currency' => $lot->currency,
                        'status' => $lot->status,
                        'actor_user_id' => $user->id,
                        'new_ends_at' => $lot->ends_at ? $lot->ends_at->toIso8601String() : null,
                    ];
                    
                    $autoEventId = "auto_bid_executed:{$bid->id}";
                    $autoPayload = $formatter->buildPayload($lot, 'auto_bid_executed', $autoExtra, $autoEventId);

                    dispatch(new \App\Jobs\Notifications\SendFcmToUserJob(
                        $user->id,
                        $autoTitle,
                        $autoBody,
                        $autoPayload
                    ));
                }

            } catch (\Exception $e) {
                // Non-blocking
                \Illuminate\Support\Facades\Log::error("Auction Notification Error (Bid): " . $e->getMessage());
            }

            return $lot;
        });
    }
}
