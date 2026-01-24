<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionEvent;
use App\Models\Auctions\AuctionLot;
use App\Events\AuctionStatusChanged;
use Illuminate\Support\Facades\Log;

class AuctionLifecycleService
{
    /**
     * Check and transition auction statuses.
     * Run this scheduled (every minute).
     */
    public function checkLifecycle()
    {
        $now = now();
        
        // 1. Start Upcoming Auctions
        $upcoming = AuctionLot::where('status', 'upcoming')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', $now)
            ->get();
            
        foreach ($upcoming as $lot) {
            // Use transaction for safety
            \Illuminate\Support\Facades\DB::transaction(function () use ($lot) {
                $lot->status = 'live';
                // optional: $lot->updated_by = system...
                $lot->save();
                
                $timelineEvent = AuctionEvent::create([
                    'auction_lot_id' => $lot->id,
                    'actor_type' => 'system',
                    'event_type' => 'auction_started',
                    'payload' => ['started_at' => now()->toIso8601String()]
                ]);

                event(new \App\Events\AuctionTimelineEventCreated($timelineEvent));
                event(new AuctionStatusChanged($lot, 'live'));
                
                Log::info("Auction {$lot->lot_no} started.");
            });
        }
        
        // 2. End Live Auctions
        $ending = AuctionLot::where('status', 'live')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->get();
            
        foreach ($ending as $lot) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($lot, $now) {
                // Determine sold vs unsold
                // Condition: If min_selling_price is set, bid must meet it.
                // If current_highest_bid is null (no bids), it's automatically unsold.
                
                $reserve = $lot->min_selling_price; // Nullable
                $highestBid = $lot->current_highest_bid; // Nullable
                
                $isSold = false;
                
                if ($highestBid === null) {
                    // No bids at all
                    $isSold = false;
                } elseif ($reserve !== null && $highestBid < $reserve) {
                    // Start price met (implied by bid existence), but Reserve NOT met
                    $isSold = false;
                } else {
                    // Reserve is null (so any bid wins) OR Reserve is met
                    $isSold = true;
                }
                
                $newStatus = $isSold ? 'ended' : 'unsold';
                
                $lot->status = $newStatus;
                $lot->ended_at = $now;
                
                if (!$isSold) {
                    // IMPORTANT: Nullify winner if not sold
                    $lot->winner_user_id = null;
                }
                
                $lot->save();
                
                $timelineEvent = AuctionEvent::create([
                    'auction_lot_id' => $lot->id,
                    'actor_type' => 'system',
                    'event_type' => 'auction_ended',
                    'payload' => [
                        'status' => $newStatus, 
                        'final_price' => $highestBid,
                        'reserve' => $reserve,
                        'outcome' => $isSold ? 'sold' : 'reserve_not_met'
                    ]
                ]);

                event(new \App\Events\AuctionTimelineEventCreated($timelineEvent));
                event(new AuctionStatusChanged($lot, $newStatus));
                
                Log::info("Auction {$lot->lot_no} ended as {$newStatus}.");
            });
        }
    }
}
