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
                // Determine outcome using shared service
                $outcomeService = new \App\Services\Auctions\AuctionOutcomeService();
                $outcome = $outcomeService->determineOutcome($lot);

                // Handle 'admin' mode
                if ($lot->outcome_decision_mode === 'admin') {
                    $lot->status = 'pending_decision';
                    $lot->ended_at = $now;
                    $lot->save();

                    $timelineEvent = AuctionEvent::create([
                        'auction_lot_id' => $lot->id,
                        'actor_type' => 'system',
                        'event_type' => 'auction_pending_decision',
                        'payload' => [
                            'highest_bid' => $outcome['highest_bid_amount'],
                            'reserve' => $lot->min_selling_price,
                            'recommendation' => $outcome['is_sold'] ? 'sold' : 'unsold',
                            'reason' => $outcome['reason'],
                            'ended_at' => $now->toIso8601String()
                        ]
                    ]);

                    event(new \App\Events\AuctionTimelineEventCreated($timelineEvent));
                    event(new AuctionStatusChanged($lot, 'pending_decision'));
                    
                    Log::info("Auction {$lot->lot_no} pending decision.");
                    return; // Done for admin mode
                }

                // Handle 'system' mode (Automatic)
                $isSold = $outcome['is_sold'];
                $newStatus = $isSold ? 'ended' : 'unsold';
                
                $lot->status = $newStatus;
                $lot->ended_at = $now;
                
                if ($isSold) {
                    $lot->winner_user_id = $outcome['winner_user_id'];
                } else {
                    $lot->winner_user_id = null;
                }
                
                $lot->save();
                
                $timelineEvent = AuctionEvent::create([
                    'auction_lot_id' => $lot->id,
                    'actor_type' => 'system',
                    'event_type' => 'auction_ended',
                    'payload' => [
                        'status' => $newStatus, 
                        'final_price' => $outcome['highest_bid_amount'],
                        'reserve' => $lot->min_selling_price,
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
