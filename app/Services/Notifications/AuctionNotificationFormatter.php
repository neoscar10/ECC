<?php

namespace App\Services\Notifications;

use App\Models\Auctions\AuctionBid;
use App\Models\Auctions\AuctionLot;
use App\Models\User;

class AuctionNotificationFormatter
{
    public function goLive(AuctionLot $lot): array
    {
        return [
            'Auction Live',
            "Auction #{$lot->lot_no} is now live! Start bidding."
        ];
    }

    public function bidPlaced(AuctionLot $lot, AuctionBid $bid, User $actor, bool $isAuto): array
    {
        $amt = number_format($bid->amount, 2);
        return [
            'New Bid Placed',
            "A new bid of {$lot->currency} {$amt} was placed on Lot #{$lot->lot_no}."
        ];
    }

    public function outbid(AuctionLot $lot, float $newAmount): array
    {
        $amt = number_format($newAmount, 2);
        return [
            "You've Been Outbid!",
            "Someone placed a higher bid of {$lot->currency} {$amt} on '{$lot->title}' (Lot #{$lot->lot_no}). Bid again now!"
        ];
    }

    public function autoBidExecuted(AuctionLot $lot, AuctionBid $bid, User $owner): array
    {
        $amt = number_format($bid->amount, 2);
        return [
            'Auto-Bid Executed',
            "Your auto-bid of {$lot->currency} {$amt} was placed on Lot #{$lot->lot_no}."
        ];
    }

    public function reminder(AuctionLot $lot, int $minutes): array
    {
        return [
            'Auction Ending Soon',
            "Auction #{$lot->lot_no} ends in {$minutes} minutes!"
        ];
    }

    public function ended(AuctionLot $lot): array
    {
        return [
            'Auction Ended',
            "Auction #{$lot->lot_no} has ended. Waiting for results..."
        ];
    }

    public function results(AuctionLot $lot): array
    {
        if ($lot->status ==='sold' || ($lot->status === 'ended' && $lot->winner_user_id)) {
             $price = number_format($lot->current_highest_bid, 2);
             return [
                 'Auction Sold',
                 "Auction #{$lot->lot_no} sold for {$lot->currency} {$price}."
             ];
        }
        
        return [
            'Auction Unsold',
            "Auction #{$lot->lot_no} ended without a winner."
        ];
    }

    public function winner(AuctionLot $lot, User $winner): array
    {
         $price = number_format($lot->current_highest_bid, 2);
         return [
             'You Won!',
             "Congratulations! You won Lot #{$lot->lot_no} for {$lot->currency} {$price}."
         ];
    }

    /**
     * Build the standard data payload with base fields + extras.
     * Ensures all values are strings.
     */
    public function buildPayload(AuctionLot $lot, string $type, array $extra = [], ?string $eventId = null): array
    {
        // Base keys
        $data = [
            'type' => $type,
            'lot_no' => $lot->lot_no,
            'auction_lot_id' => (string)$lot->id,
            'sent_at' => now()->toIso8601String(),
        ];
        
        // Event ID
        if ($eventId) {
            $data['event_id'] = $eventId;
        }
        
        // Auto-include ends_at if relevant
        if ($lot->ends_at) {
             // Only for relevant types where ends_at logic matters
             if (in_array($type, ['bid_placed', 'auto_bid_executed', 'auction_reminder', 'auction_ended', 'auction_results', 'auction_winner'])) {
                 $data['ends_at'] = $lot->ends_at->toIso8601String();
             }
        }

        // [New Routing Keys]
        // Standardize link to auction detail page
        $data['target_page'] = 'auction_detail';
        $data['target_id'] = (string)$lot->id;

        // Merge extra
        $merged = array_merge($data, $extra);
        
        // Cast all to string (double check)
        $final = [];
        foreach ($merged as $k => $v) {
            if (!is_null($v)) {
                $final[$k] = (string)$v;
            }
        }
        
        return $final;
    }
}
