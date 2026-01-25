<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionLot;
use App\Models\Auctions\AuctionLotAttachment;
use App\Models\MembershipTier;
use App\Models\User;
use App\Support\Archive\AccessIconNormalizer; // Verify this exists, used in ArchiveProductResource

class AuctionAccessPresenter
{
    /**
     * Format the main lot access object to match Archive schema.
     */
    public function present(AuctionLot $lot, ?User $user, array $resolverResult): array
    {
        $userTier = $user?->currentMembership?->membershipTier;
        
        // 1. Determine View Mode
        $viewMode = 'blocked';
        $reason = null;
        
        if ($resolverResult['has_visibility']) {
            if ($resolverResult['can_view_clear']) {
                $viewMode = 'clear';
            } else {
                $viewMode = 'blur';
                $reason = 'blurred';
            }
        } else {
            $viewMode = 'blocked'; // Hidden/Private
            $reason = 'product_restricted'; // Generic matching archive
        }

        // 2. Build Actions & Message Context
        $actions = [];
        $messageContext = [];

        // Upgrade Actions
        if ($viewMode !== 'clear') {
            if ($viewMode === 'blur') {
                // Upgrade for Clear View works
                $upgrade = $this->findTierUpgrade($lot, 'clearViewTiers');
                if ($upgrade) {
                    $actions[] = [
                        'type' => 'upgrade_membership',
                        'label' => 'Upgrade for Clear View',
                        'target_tier' => $this->formatTier($upgrade),
                        'deeplink' => '/membership/tiers',
                        'priority' => 'primary'
                    ];
                    $messageContext['clear_view_tier_name'] = $upgrade->name;
                }
            } elseif ($viewMode === 'blocked') {
                // Upgrade for Visibility
                $upgrade = $this->findTierUpgrade($lot, 'visibilityTiers');
                if ($upgrade) {
                    $actions[] = [
                        'type' => 'upgrade_membership',
                        'label' => 'Upgrade to View',
                        'target_tier' => $this->formatTier($upgrade),
                        'deeplink' => '/membership/tiers',
                        'priority' => 'primary'
                    ];
                    $messageContext['required_tier_name'] = $upgrade->name;
                } else {
                     $messageContext['required_tier_name'] = 'Membership';
                }
            }
        }

        // 3. Build Message
        $message = $this->buildMessage($reason, $viewMode, $messageContext);

        // 4. Timing
        $timing = [
            'go_live_at' => $lot->starts_at?->toIso8601String(),
            'next_access_at' => null // Logic for early access could go here if mapped
        ];

        return [
            'view_mode' => $viewMode,
            'reason' => $reason,
            'source' => 'lot', // 'product' in archive, 'lot' here
            'viewer' => $this->formatViewer($userTier),
            'message' => $message,
            'actions' => $actions,
            'timing' => $timing
        ];
    }
    
    /**
     * Format attachment access object.
     */
    public function presentAttachment(AuctionLotAttachment $attachment, AuctionLot $lot, ?User $user, array $lotAccess): array
    {
        $userTier = $user?->currentMembership?->membershipTier;
        
        // 1. Inherit Lot Access first
        if ($lotAccess['view_mode'] !== 'clear') {
            $inherited = $lotAccess;
            $inherited['source'] = 'attachment'; // Override source
            return $inherited;
        }

        // 2. Attachment Specific Verification
        // Map attachment restriction fields
        $isClear = true; // Default unless restricted
        $reason = null;
        $actions = [];
        $context = [];

        if ($attachment->restriction_mode === 'public' || $attachment->restriction_mode === 'inherit') {
            $isClear = true;
        } else {
             // Logic check (simplified version of resolve logic)
             $hasAccess = $this->checkAttachmentAccess($attachment, $userTier);
             if (!$hasAccess) {
                 $isClear = false;
                 $reason = 'attachment_restricted';
                 
                 // Find upgrade
                 $upgrade = $this->findAttachmentUpgrade($attachment);
                 if ($upgrade) {
                     $actions[] = [
                        'type' => 'upgrade_membership',
                        'label' => 'Upgrade',
                        'target_tier' => $this->formatTier($upgrade),
                        'deeplink' => '/membership/tiers',
                        'priority' => 'primary'
                     ];
                     $context['required_tier_name'] = $upgrade->name;
                 } else {
                     $context['required_tier_name'] = 'Private';
                 }
             }
        }

        $viewMode = $isClear ? 'clear' : 'blocked';
        
        return [
            'view_mode' => $viewMode,
            'reason' => $reason,
            'source' => 'attachment',
            'viewer' => $this->formatViewer($userTier),
            'message' => $this->buildMessage($reason, $viewMode, $context),
            'actions' => $actions,
            'timing' => $lotAccess['timing'] // Inherit timing
        ];
    }

    // --- Helpers ---

    protected function checkAttachmentAccess($attachment, $userTier) {
        if (!$userTier) return false;
        
        // Restriction Type Logic
        if ($attachment->restriction_type === 'hierarchical') {
             $min = $attachment->restrictedMinTier; 
             if (!$min && $attachment->restricted_min_tier_id) $min = MembershipTier::find($attachment->restricted_min_tier_id);
             return $min && $userTier->level >= $min->level;
        }
        if ($attachment->restriction_type === 'private') {
             return $attachment->restricted_private_tier_id === $userTier->id;
        }
        if ($attachment->restriction_type === 'random') {
             // Assuming explicit tiers relationship if random
             // Check pivot
             return $attachment->tiers()->where('membership_tier_id', $userTier->id)->exists();
        }
        return false;
    }

    protected function findAttachmentUpgrade($attachment) {
        if ($attachment->restriction_type === 'hierarchical') {
             return $attachment->restrictedMinTier ?? MembershipTier::find($attachment->restricted_min_tier_id);
        }
        if ($attachment->restriction_type === 'private') {
             return $attachment->restrictedPrivateTier ?? MembershipTier::find($attachment->restricted_private_tier_id);
        }
        // Random: find lowest
        return $attachment->tiers()->orderBy('level', 'asc')->first();
    }

    protected function findTierUpgrade(AuctionLot $lot, string $relation) {
        // Find lowest tier in the relation set
        // $relation = 'visibilityTiers' or 'clearViewTiers'
        return $lot->$relation()->orderBy('level', 'asc')->first();
    }

    protected function buildMessage($reason, $viewMode, $context)
    {
        // Replicating Archive logic simply
        if ($viewMode === 'clear') {
            return ['title' => 'Open', 'body' => 'Access Granted', 'icon' => 'info'];
        }

        if ($reason === 'blurred') {
            $name = $context['clear_view_tier_name'] ?? 'Higher Tier';
            return [
                'title' => 'Restricted View',
                'body' => "$name Tier Required",
                'icon' => 'lock'
            ];
        }

        if ($reason === 'product_restricted' || $reason === 'attachment_restricted') {
            $name = $context['required_tier_name'] ?? 'Membership';
             return [
                'title' => 'Restricted Access',
                'body' => "$name Tier Required",
                'icon' => 'lock'
            ];
        }
        
        return ['title' => 'Restricted', 'body' => 'Access Denied', 'icon' => 'lock'];
    }

    protected function formatViewer($tier): array
    {
        return [
            'membership_tier_id' => $tier?->id,
            'membership_tier_name' => $tier?->name,
            'membership_level' => $tier?->level
        ];
    }

    protected function formatTier($tier): array
    {
        return [
            'id' => $tier->id,
            'name' => $tier->name,
            'level' => $tier->level,
            'price' => (string) ($tier->price ?? '0.00'),
            'currency' => $tier->currency ?? 'INR'
        ];
    }
}
