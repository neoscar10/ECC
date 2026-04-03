<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionEvent;
use App\Models\Auctions\AuctionLot;
use App\Events\AuctionStatusChanged;
use Illuminate\Support\Facades\Log;

class AuctionLifecycleService
{
    protected $terminalValueCapture;

    public function __construct(AuctionTerminalValueCaptureService $terminalValueCapture)
    {
        $this->terminalValueCapture = $terminalValueCapture;
    }

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
                
                // NOTIFICATION: Go Live
                $this->notifyLifecycleEvent($lot, 'auction_go_live');
            });
        }
        
        // 2. End Live Auctions
        $ending = AuctionLot::where('status', 'live')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->get();
            
        foreach ($ending as $lot) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($lot, $now) {
                // Perform final terminal value capture pass before closing
                $this->terminalValueCapture->capture($lot);
                $lot->refresh();

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
                    
                    // NOTIFICATION: Ended (Waiting for Decision)
                    $this->notifyLifecycleEvent($lot, 'auction_ended');
                    
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
                
                // NOTIFICATION: Ended & Results
                $this->notifyLifecycleEvent($lot, 'auction_ended');
                $this->notifyLifecycleEvent($lot, 'auction_results');
                
                if ($lot->winner_user_id) {
                     $this->notifyLifecycleEvent($lot, 'auction_winner');
                }
            });
        }
        
        // 3. Reminders (Live Auctions)
        $this->processReminders();
    }
    
    protected function notifyLifecycleEvent(AuctionLot $lot, string $type, array $extra = [])
    {
        try {
            $dedupe = new \App\Services\Notifications\NotificationDedupe();
            $key = "{$type}:{$lot->id}";
            if (isset($extra['key_suffix'])) {
                $key .= ":{$extra['key_suffix']}";
                unset($extra['key_suffix']);
            }
            
            if ($dedupe->alreadySent($key)) {
                return;
            }
            
            $formatter = new \App\Services\Notifications\AuctionNotificationFormatter();
            $topic = \App\Support\Notifications\FcmTopicNamer::auctionTopic($lot);
            
            $title = '';
            $body = '';
            $extraData = [
                'status' => $lot->status
            ];
            $eventId = null;
            
            switch($type) {
                case 'auction_go_live':
                    [$title, $body] = $formatter->goLive($lot);
                    $eventId = "auction_go_live:{$lot->id}";
                    break;
                case 'auction_ended':
                    [$title, $body] = $formatter->ended($lot);
                    $eventId = "auction_ended:{$lot->id}";
                    // "status" is already in extraData.
                    // If lot status is "pending_decision", keep it.
                    // No need to override unless we want "status_lot".
                    break;
                case 'auction_results':
                     [$title, $body] = $formatter->results($lot);
                     $eventId = "auction_results:{$lot->id}";
                     $extraData['status'] = $lot->status; // sold/unsold
                     $extraData['final_price'] = $lot->current_highest_bid;
                     $extraData['winner_user_id'] = $lot->winner_user_id;
                     $extraData['currency'] = $lot->currency; // Added
                     
                     if ($lot->winner_user_id) {
                         // Attempt to get winning bid ID
                         $winningBid = $lot->bids()->orderByDesc('amount')->first();
                         if ($winningBid) {
                             $extraData['winning_bid_id'] = $winningBid->id;
                         }
                     }
                     break;
                case 'auction_winner':
                    if (!$lot->winner_user_id) return;
                    $winner = \App\Models\User::find($lot->winner_user_id);
                    if ($winner) {
                        [$title, $body] = $formatter->winner($lot, $winner);
                        $eventId = "auction_winner:{$lot->id}:{$lot->winner_user_id}";
                        
                        // Payload
                        $extraData['status'] = $lot->status;
                        $extraData['final_price'] = $lot->current_highest_bid;
                        $extraData['winner_user_id'] = $lot->winner_user_id;
                        $extraData['currency'] = $lot->currency; // Added
                        
                        // Attempt to get winning bid ID
                        $winningBid = $lot->bids()->orderByDesc('amount')->first();
                         if ($winningBid) {
                             $extraData['winning_bid_id'] = $winningBid->id;
                         }

                        // Private Send
                        // Mark sent BEFORE dispatch (at-most-once)
                        $dedupe->markSent($key, $type, $lot->id, $lot->winner_user_id);
                        
                        $payload = $formatter->buildPayload($lot, $type, $extraData, $eventId);
                        
                        dispatch(new \App\Jobs\Notifications\SendFcmToUserJob(
                            $lot->winner_user_id,
                            $title,
                            $body,
                            $payload
                        ));
                    }
                    return; // Done
                case 'auction_reminder':
                    $mins = $extra['minutes'] ?? 0;
                    [$title, $body] = $formatter->reminder($lot, $mins);
                    $eventId = "auction_reminder:{$lot->id}:{$mins}";
                    $extraData['minutes_remaining'] = $mins;
                    break;
            }
            
            if ($title && $body) {
                // Mark Sent for Topic
                $dedupe->markSent($key, $type, $lot->id, null, $extra);
                
                $payload = $formatter->buildPayload($lot, $type, $extraData, $eventId);
                
                dispatch(new \App\Jobs\Notifications\SendFcmToTopicJob(
                    $topic,
                    $title,
                    $body,
                    $payload
                ));
            }
            
        } catch (\Exception $e) {
            Log::error("Lifecycle Notification Error ({$type}): " . $e->getMessage());
        }
    }
    
    protected function processReminders()
    {
        $reminderMinutes = config('auction_notifications.reminder_minutes', [60,30,15,10,5,1]);
        
        $liveAuctions = AuctionLot::where('status', 'live')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->get();
            
        foreach ($liveAuctions as $lot) {
            $minsRemaining = now()->diffInMinutes($lot->ends_at, false);
            // diffInMinutes rounds down? No, it's integer diff.
            // Let's use float minutes for better threshold check?
            // "at specific remaining times" usually means "reached the T minute mark".
            // e.g. if 29m 30s remaining, diffInMinutes = 29.
            // We want to trigger when it effectively CROSSES the threshold.
            
            // Loop through configured minutes
            foreach ($reminderMinutes as $threshold) {
                 // Check if we are "at or closely below" the threshold but HAVEN'T sent yet.
                 // Simple logic: if minsRemaining <= threshold.
                 // Dedupe key ensures we send once.
                 
                 if ($minsRemaining <= $threshold && $minsRemaining > ($threshold - 1)) {
                      // We are in the "minute" of the threshold.
                      // e.g. Threshold 30. Remaining 29.8 => send.
                      // Wait, diffInMinutes(absolute=false) might return 29 for 29m 59s.
                      // Correct logic: if remaining is roughly equal to threshold.
                      // Let's just say: if remaining <= threshold, and we haven't sent it yet.
                      // This covers "oops we missed minute 30, it's now minute 28". better late than never?
                      // Usually better to be precise. 
                      // If we are at 25 mins, and we haven't sent 30, should we? Probably not, it's confusing.
                      // Let's stick to strict window: threshold >= remaining > threshold - 1.
                      
                      $this->notifyLifecycleEvent($lot, 'auction_reminder', [
                          'minutes' => $threshold,
                          'key_suffix' => (string)$threshold
                      ]);
                 }
            }
        }
    }
}
