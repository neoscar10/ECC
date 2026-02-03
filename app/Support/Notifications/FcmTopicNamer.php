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

    /**
     * Get the global topic name for all users.
     */
    public static function globalTopic(): string
    {
        return 'ecc_all_users';
    }

    /**
     * Get the topic name for a specific membership tier.
     */
    public static function membershipTierTopic($tierId): string
    {
        return 'ecc_membership_' . $tierId;
    }

    /**
     * Get the topic name for a specific user.
     */
    public static function userTopic($userId): string
    {
        return 'ecc_user_' . $userId;
    }
}
