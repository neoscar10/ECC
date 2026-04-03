<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionAutoBid;
use App\Models\Auctions\AuctionLot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuctionTerminalValueCaptureService
{
    protected $accessResolver;

    public function __construct(AuctionAccessResolverService $accessResolver)
    {
        $this->accessResolver = $accessResolver;
    }

    /**
     * Perform ECC-style terminal value capture for an auction lot.
     * This ensures the highest authorized auto-bid is reached before the auction closes.
     */
    public function capture(AuctionLot $lot): void
    {
        // 1. Prerequisites Check (Calling service must have locked the lot)
        if (!$lot || !in_array($lot->status, ['live', 'upcoming'])) return;

        $currentWinnerId = $lot->winner_user_id;
        $currentHighestBid = (float)($lot->current_highest_bid ?? 0);

        // 2. Load and filter active auto-bids
        $autoBids = AuctionAutoBid::where('auction_lot_id', $lot->id)
            ->where('is_enabled', true)
            ->with('user')
            ->get();

        if ($autoBids->isEmpty()) return;

        // 3. Resolve Highest Authorized Max
        $competitors = [];

        // Add current winner as a competitor (even if they don't have an auto-bid)
        if ($currentWinnerId) {
            $winnerAutoBid = $autoBids->where('user_id', $currentWinnerId)->first();
            $competitors[$currentWinnerId] = [
                'user_id' => $currentWinnerId,
                'max_bid' => $winnerAutoBid ? (float)$winnerAutoBid->max_bid : $currentHighestBid,
                'is_current_winner' => true,
                'increment_amount' => $winnerAutoBid ? (float)$winnerAutoBid->increment_amount : (float)$lot->min_increment,
                'user' => $winnerAutoBid ? $winnerAutoBid->user : User::find($currentWinnerId)
            ];
        }

        foreach ($autoBids as $ab) {
            if (isset($competitors[$ab->user_id])) {
                $competitors[$ab->user_id]['max_bid'] = (float)$ab->max_bid;
                $competitors[$ab->user_id]['increment_amount'] = (float)$ab->increment_amount;
                continue;
            }

            if ($this->accessResolver->isBiddingOpenForUser($lot, $ab->user)) {
                $competitors[$ab->user_id] = [
                    'user_id' => $ab->user_id,
                    'max_bid' => (float)$ab->max_bid,
                    'is_current_winner' => false,
                    'increment_amount' => (float)$ab->increment_amount,
                    'user' => $ab->user
                ];
            }
        }

        if (count($competitors) < 1) return;

        // Sort by max_bid desc
        uasort($competitors, function ($a, $b) {
            if ($a['max_bid'] != $b['max_bid']) {
                return $b['max_bid'] <=> $a['max_bid'];
            }
            if ($a['is_current_winner']) return -1;
            if ($b['is_current_winner']) return 1;
            return 0;
        });

        $competitorList = array_values($competitors);
        $top = $competitorList[0];
        $second = $competitorList[1] ?? null;

        // ECC Strategy Requirement:
        // "Winner should be advanced to their max_bid"
        // "System creates a competing bid at max_bid - increment"
        
        $targetWinnerId = $top['user_id'];
        $targetWinnerMax = $top['max_bid'];
        $increment = max((float)$lot->min_increment, $top['increment_amount']);

        // If the current bid is already at or above the target max, we do nothing.
        if ($currentHighestBid >= $targetWinnerMax) {
            return;
        }

        Log::info("Auction Terminal Value Capture Triggered LOT-{$lot->id}", [
            'current_bid' => $currentHighestBid,
            'target_max' => $targetWinnerMax,
            'winner_id' => $targetWinnerId
        ]);

        $biddingService = app(AuctionBiddingService::class);

        // Step A: Generate "Platform Pressure" bid
        // Position: Just below the winner's max
        $pressureAmount = $targetWinnerMax - $increment;

        // Safety: if pressureAmount is below current, we use current + increment as immediate pressure
        if ($pressureAmount < $currentHighestBid) {
            $pressureAmount = $currentHighestBid + $increment;
        }

        // Important: Pressure bid must be strictly less than $targetWinnerMax to allow the winner to finish it.
        if ($pressureAmount >= $targetWinnerMax) {
            // If there's no room for a final jump, the winner just takes it at their max.
        } else {
            // Use User ID 1 (Super Admin) as the proxy actor for Platform Pressure.
            $systemUser = User::find(1);
            if ($systemUser) {
                $biddingService->placeBid($lot, $systemUser, $pressureAmount, 'system_terminal', true, true);
                $lot->refresh(); // Sync state after system bid
            }
        }

        // Step B: Final Winning Bid for the User
        $winner = User::find($targetWinnerId);
        if ($winner) {
            $biddingService->placeBid($lot, $winner, $targetWinnerMax, 'system', true, true);
        }
    }
}
