<?php

namespace App\Services\Auctions;

use App\Jobs\Auctions\ProcessAutoBidStepJob;
use App\Models\Auctions\AuctionAutoBid;
use App\Models\Auctions\AuctionEvent;
use App\Models\Auctions\AuctionLot;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // DOMAIN VALIDATION (Mirroring Controller / Bidding Logic)
        $scale = 2;
        $errors = [];
        
        // 1. Min Increment
        $minIncrement = (string) ($lot->min_increment ?? '0.00');
        $reqIncrement = (string) $incrementAmount;
        
        if (bccomp($reqIncrement, $minIncrement, $scale) === -1) {
             $errors['increment_amount'] = ["Increment Amount must be at least {$lot->currency} {$minIncrement}."];
        }

        // 2. Max Bid
        $currentBid = (string) ($lot->current_highest_bid ?? '0.00'); 
        
        if ($lot->current_highest_bid) {
             $threshold = bcadd($currentBid, $minIncrement, $scale);
        } else {
             $threshold = (string) $lot->starting_price;
        }

        $reqMax = (string) $maxBid;
         
        if (bccomp($reqMax, $threshold, $scale) === -1) { 
             $errors['max_bid'] = ["Max Bid must be at least {$lot->currency} {$threshold}."];
        }
        
        if (!empty($errors)) {
             throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($lot, $user, $maxBid, $incrementAmount) {
             // 1. Upsert
             $autoBid = AuctionAutoBid::updateOrCreate(
                 ['auction_lot_id' => $lot->id, 'user_id' => $user->id],
                 [
                     'max_bid' => $maxBid,
                     'increment_amount' => $incrementAmount,
                     'is_enabled' => true,
                 ]
             );
             
             // 2. Log
             AuctionEvent::create([
                 'auction_lot_id' => $lot->id, 
                 'actor_type' => 'user',
                 'actor_id' => $user->id, 
                 'event_type' => 'auto_bid_set',
                 'payload' => ['max_bid' => $maxBid]
             ]);
             
             // 3. Trigger Evaluation (Non-blocking / Delayed)
             // Check if we should place an initial bid immediately
             $freshLot = AuctionLot::lockForUpdate()->find($lot->id);
             
             // Only act if auction is live (sanity check, covered by validation but status can change)
             if ($freshLot && $freshLot->status === 'live' && (!$freshLot->ends_at || now()->lt($freshLot->ends_at))) {
                 $currentWinnerId = $freshLot->winner_user_id;
                 $currentHighest = $freshLot->current_highest_bid;
                 
                 // Condition 1: User is NOT winning
                 if ($currentWinnerId !== $user->id) {
                     // Condition 2: Calculate Initial Bid
                     if ($currentHighest === null) {
                        // CASE A: No bids yet -> Immediate Bid (Starting Price)
                        $bidAmount = $freshLot->starting_price;
                        
                        // Check affordability
                        if ($bidAmount <= $maxBid) {
                             $this->biddingService->placeBid($freshLot, $user, $bidAmount, 'system', true);
                        }
                     } else {
                        // CASE B: Existing bids -> Lag applies
                        // We check if they COULD bid, to decide if we force a schedule
                        $reqInc = max($freshLot->min_increment, $incrementAmount);
                        $bidAmount = $currentHighest + $reqInc;
                        
                        if ($bidAmount <= $maxBid) {
                            // "Ensure it can trigger ... right away" (via lag mechanism)
                            // We force the schedule by clearing any existing lock
                            Cache::forget("auctions:auto_bid:pending:{$freshLot->id}");
                            $this->processAutoBids($freshLot);
                            return $autoBid; // processed
                        }
                     }
                 }
             }

             // Standard schedule (fallback if we didn't force it above)
             $this->processAutoBids($lot); 
             
             return $autoBid;
        });
    }

    /**
     * Trigger auto-bid processing for a lot.
     * Starts the async job chain if not already pending.
     */
    public function processAutoBids(AuctionLot $lot): void
    {
        $this->scheduleAutoBidStep($lot->id);
    }

    /**
     * Schedule the next auto-bid step with random lag.
     */
    public function scheduleAutoBidStep(int $lotId): void
    {
        // 1. Lightweight fetch to verify status/time & calculate delay
        $lot = AuctionLot::find($lotId);
        if (!$lot || $lot->status !== 'live') {
            return;
        }
        if ($lot->ends_at && now()->gt($lot->ends_at)) {
            return;
        }

        // 2. Calculate Delay
        $lagMin = config('auctions.autobid_lag_min', 60);
        $lagMax = config('auctions.autobid_lag_max', 120);
        $cutoff = config('auctions.autobid_lag_cutoff', 120);

        $remainingSeconds = $lot->ends_at ? now()->diffInSeconds($lot->ends_at, false) : 9999;
        
        $delaySeconds = 0;
        if ($remainingSeconds > $cutoff) {
            try {
                $delaySeconds = random_int((int)$lagMin, (int)$lagMax);
            } catch (\Exception $e) {
                $delaySeconds = 60; // Fallback
            }
        }

        Log::info("AutoBid Schedule Lot: {$lotId}", [
            'queue_conn' => config('queue.default'),
            'env_queue' => env('QUEUE_CONNECTION'),
            'ends_at' => $lot->ends_at,
            'now' => now(),
            'remaining' => $remainingSeconds,
            'cutoff' => $cutoff,
            'calc_delay' => $delaySeconds
        ]);

        // 3. Concurrency Guard using Cache Lock (Timestamp based)
        // Key: auctions:auto_bid:pending:{lotId}
        // Value: Timestamp (integer) when the pending schedule expires/executes
        $lockKey = "auctions:auto_bid:pending:{$lotId}";
        
        $currentLock = Cache::get($lockKey);
        $nowTimestamp = now()->timestamp;

        // If lock exists and is in the future, we assume a job is already pending/scheduled
        if ($currentLock && $currentLock > $nowTimestamp) {
             Log::info("AutoBid skipped - pending exists: {$lotId}", ['pending_until' => $currentLock]);
             return;
        }

        // If we are here, either no lock exists OR it is stale (past).
        // If stale, we proceed to schedule a new one (effectively self-healing).
        
        $executeAt = now()->addSeconds($delaySeconds);
        // We set the lock value to the execution time + small buffer (e.g. 15s grace for job start)
        // This means "Do not schedule another job until after [Job Start Time + Grace]"
        $lockValue = $executeAt->timestamp + 15;
        
        // TTL should be slightly longer than the value to ensure it expires naturally if we crash
        $ttlSeconds = $delaySeconds + 60; 

        Cache::put($lockKey, $lockValue, $ttlSeconds);

        Log::info("AutoBid scheduled: {$lotId}", [
            'delay' => $delaySeconds, 
            'pending_until' => $lockValue,
            'was_stale' => ($currentLock && $currentLock <= $nowTimestamp)
        ]);

        $job = new ProcessAutoBidStepJob($lotId);
        if ($delaySeconds > 0) {
            $job->delay($executeAt);
        }
        dispatch($job);
    }

    /**
     * Cancel an active auto-bid for a user on a lot.
     */
    public function cancelAutoBid(AuctionLot $lot, User $user): array
    {
        return DB::transaction(function () use ($lot, $user) {
            $autoBid = AuctionAutoBid::where('auction_lot_id', $lot->id)
                ->where('user_id', $user->id)
                ->first();

            if ($autoBid) {
                $id = $autoBid->id;
                $autoBid->delete();
                
                // Log Event
                AuctionEvent::create([
                    'auction_lot_id' => $lot->id, 
                    'actor_type' => 'user',
                    'actor_id' => $user->id, 
                    'event_type' => 'auto_bid_cancelled',
                    'payload' => null
                ]);

                return [
                    'id' => $id,
                    'is_enabled' => false,
                    'status' => 'cancelled',
                    'cancelled_at' => now()->toIso8601String(),
                    'reason' => 'user_cancelled'
                ];
            }

            return [
                'id' => null,
                'is_enabled' => false,
                'status' => 'not_active',
                'cancelled_at' => null,
                'reason' => 'not_active'
            ];
        });
    }
}
