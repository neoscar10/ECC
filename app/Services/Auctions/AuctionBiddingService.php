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
    protected $accessResolver;

    public function __construct(AuctionAccessResolverService $accessResolver)
    {
        $this->accessResolver = $accessResolver;
    }

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
    public function placeBid(AuctionLot $lot, User $user, float $amount, string $source = 'web', bool $isAuto = false, bool $skipTerminalResolution = false, bool $allowPastEnd = false): AuctionLot
    {
        return DB::transaction(function () use ($lot, $user, $amount, $source, $isAuto, $skipTerminalResolution, $allowPastEnd) {
            // 1. Lock Row
            $lot = AuctionLot::lockForUpdate()->find($lot->id);

            // 2. Validate Status & Time
            if (!$this->accessResolver->isBiddingOpenForUser($lot, $user)) {
                throw new \Exception("Bidding is not open for this item yet.");
            }
            
            // Time Guard: Allowed after ends_at only if allowPastEnd is true (system closing path)
            if (!$allowPastEnd && $lot->ends_at && now()->gt($lot->ends_at)) {
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
            // Fire Personal Event for Owner (allows is_me=true)
            if ($user->id) {
                event(new \App\Events\AuctionBidPlacedPersonal($bid, $user->id));
            }
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

                // CONDITIONAL DISPATCH REFACTOR:
                // ALWAYS Fan-out 'bid_placed' to enabled subscribers EXCLUDING the actor (bidder).
                // This applies to both Manual and Auto bids so the bidder doesn't get a push for their own action.
                
                $subscribers = \App\Models\Auctions\AuctionNotificationSubscription::where('auction_lot_id', $lot->id)
                    ->where('is_enabled', true)
                    ->where('user_id', '!=', $user->id) // Exclude current bidder
                    ->pluck('user_id');

                \Illuminate\Support\Facades\Log::info("AuctionBiddingService: Fan-out bid_placed", [
                    'lot_id' => $lot->id,
                    'bid_id' => $bid->id,
                    'is_auto' => $isAuto,
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

                // REMOVED: Topic broadcast for manual bids.
                // We now strictly use fan-out for all 'bid_placed' events to enforce exclusion.

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

            // 10. Terminal Endgame Proxy Resolution
            // If this is the "Endgame", we immediately resolve all other proxy headroom
            // to ensure the strongest bidder wins at the minimum required price.
            if (!$skipTerminalResolution && $lot->isTerminalState()) {
                app(AuctionTerminalValueCaptureService::class)->capture($lot);
            }

            return $lot;
        });
    }

}
