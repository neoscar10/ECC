<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionLot;

class AuctionOutcomeService
{
    /**
     * Determine the outcome of an auction lot.
     * Returns an array with:
     * - is_sold (bool)
     * - reason (string)
     * - highest_bid_amount (float|null)
     * - winner_user_id (int|null)
     */
    public function determineOutcome(AuctionLot $lot): array
    {
        $highestBid = $lot->bids()->orderByDesc('amount')->first();
        
        if (!$highestBid) {
            return [
                'is_sold' => false,
                'reason' => 'No bids placed.',
                'highest_bid_amount' => null,
                'winner_user_id' => null,
                'reserve_met' => false,
            ];
        }

        $amount = (float) $highestBid->amount;
        $reserve = $lot->min_selling_price ? (float) $lot->min_selling_price : null;
        
        // If reserve is set and not met
        if ($reserve !== null && $amount < $reserve) {
            return [
                'is_sold' => false,
                'reason' => 'Reserve price not met.',
                'highest_bid_amount' => $amount,
                'winner_user_id' => $highestBid->user_id, // Allow admin to override
                'reserve_met' => false,
            ];
        }

        // Sold
        return [
            'is_sold' => true,
            'reason' => 'Highest bid met requirements.',
            'highest_bid_amount' => $amount,
            'winner_user_id' => $highestBid->user_id,
            'reserve_met' => true,
        ];
    }
}
