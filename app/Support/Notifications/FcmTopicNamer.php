<?php

namespace App\Support\Notifications;

use App\Models\Auctions\AuctionLot;

class FcmTopicNamer
{
    /**
     * Get the FCM topic name for an auction lot.
     * Format: ecc_auction_{id}
     */
    public static function auctionTopic(AuctionLot $lot): string
    {
        return 'ecc_auction_' . $lot->id;
    }
}
