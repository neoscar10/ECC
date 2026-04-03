<?php

namespace App\Support\Auctions;

use App\Models\Auctions\AuctionBid;

class BidPresenter
{
    /**
     * Format a bid for broadcast/response exactly matching the API.
     * 
     * @param AuctionBid $bid
     * @return array
     */
    public static function present(AuctionBid $bid, ?int $viewerUserId = null): array
    {
        $userId = $bid->user_id;
        $mask = self::generateBidderIdentity($userId);
        
        // Broadcast logic:
        // - Global broadcast (AuctionBidPlaced) passes null -> isMe = false.
        // - Personal broadcast (AuctionBidPlacedPersonal) passes viewerUserId -> isMe = (userId == viewerId).
        $isMe = ($viewerUserId !== null) && ((int)$viewerUserId === (int)$userId); 

        $label = $isMe ? 'You' : $mask['label'];
        $badge = $mask['badge'];

        // Terminal Escalation Override
        if ($bid->source === 'system_terminal') {
            $label = 'ECC System';
            $badge = 'EC';
        }

        return [
            'amount' => (string) $bid->amount,
            'time' => $bid->placed_at->toIso8601String(),
            'time_human' => $bid->placed_at->diffForHumans(),
            'is_me' => $isMe,
            'is_auto' => $bid->is_auto, 
            'is_highest_bid' => true, 
            'bidder_label' => $label, 
            'bidder_code' => $mask['code'],
            'bidder_badge' => $badge,
        ];
    }

    /**
     * Generate masked identity (Copied verbatim from AuctionController).
     * 
     * @param int|string $userId
     * @return array
     */
    protected static function generateBidderIdentity($userId)
    {
        $paddedId = str_pad((string)$userId, 3, '0', STR_PAD_LEFT);
        $last3 = substr($paddedId, -3);
        $label = "User ****{$last3}";

        $seed = crc32((string) $userId);
        $char1 = chr(65 + ($seed % 26));
        $char2 = chr(65 + (($seed >> 8) % 26));
        $badge = $char1 . $char2;

        $colorSeed = (string) ($seed % 10);

        return [
            'label' => $label,
            'code' => $last3,
            'badge' => $badge,
            'color_seed' => $colorSeed
        ];
    }
}
