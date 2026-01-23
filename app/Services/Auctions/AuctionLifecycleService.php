<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionEvent;
use App\Models\Auctions\AuctionLot;
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
            ->where('starts_at', '<=', $now)
            ->get();
            
        foreach ($upcoming as $lot) {
            $lot->update(['status' => 'live']);
            
            AuctionEvent::create([
                'auction_lot_id' => $lot->id,
                'event_type' => 'status_changed',
                'payload' => ['new_status' => 'live']
            ]);
            
            // Broadcast 'started' event here
            // Notify watchers/auto-bidders?
            Log::info("Auction {$lot->lot_no} started.");
        }
        
        // 2. End Live Auctions
        $ending = AuctionLot::where('status', 'live')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->get();
            
        foreach ($ending as $lot) {
            // Determine sold/unsold
            $finalPrice = $lot->current_highest_bid ?? 0;
            $reserve = $lot->min_selling_price ?? 0;
            
            // If no bids, unsold.
            // If bids but < reserve, unsold.
            // If >= reserve, sold.
            
            $status = 'unsold';
            if ($lot->current_highest_bid > 0) {
                 if ($finalPrice >= $reserve) {
                     $status = 'sold';
                 } else {
                     $status = 'unsold'; 
                     // Or 'reserve_not_met'? SRS says "sold/unsold based on reserve".
                 }
            }
            
            $lot->update([
                'status' => $status,
                'ended_at' => $now
            ]);
            
            AuctionEvent::create([
                'auction_lot_id' => $lot->id,
                'event_type' => 'status_changed',
                'payload' => ['new_status' => $status, 'final_price' => $finalPrice]
            ]);
            
            // Broadcast 'ended' event
            // Trigger Notifications (Winner, Losers)
            Log::info("Auction {$lot->lot_no} ended as {$status}.");
        }
    }
}
