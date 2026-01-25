<?php

namespace App\Services\Common;

use App\Models\MembershipTier;
use App\Models\User;
use App\Support\Archive\AccessIconNormalizer; // reusing existing normalizer

class AccessPresentationService
{
    /**
     * Build a canonical access object ensuring parity between Archive and Auctions.
     *
     * @param string $viewMode 'clear', 'blur', 'blocked'
     * @param string|null $reason Internal reason key (e.g., 'product_restricted', 'blurred')
     * @param string $source 'product', 'lot', 'attachment'
     * @param User|null $user
     * @param array $context Additional context for messages (tier names, counts, etc.)
     * @param array $actions Array of raw action definitions
     * @param array $timing Timing info (go_live_at, next_access_at)
     * @return array
     */
    public function present(
        string $viewMode, 
        ?string $reason, 
        string $source, 
        ?User $user, 
        array $context = [], 
        array $actions = [], 
        array $timing = []
    ): array
    {
        $userTier = $user?->currentMembership?->membershipTier;
        
        // 1. Build Message (Exact Archive Logic)
        $message = $this->buildMessage($reason, $viewMode, $context);
        
        // 2. Normalize and Format Actions
        $formattedActions = array_map(function ($action) {
            return array_merge([
                'type' => 'wait', 
                'label' => null, 
                'target_tier' => null, 
                'deeplink' => null, 
                'priority' => 'primary'
            ], $action);
        }, $actions);

        // 3. Last Mile Icon Normalization
        $message['icon'] = AccessIconNormalizer::normalize($reason, $viewMode);

        return [
            'view_mode' => $viewMode,
            'reason' => $reason,
            'source' => $source,
            'viewer' => $this->formatViewer($userTier),
            'message' => $message,
            'actions' => $formattedActions,
            'timing' => array_merge(['go_live_at' => null, 'next_access_at' => null], $timing)
        ];
    }

    protected function buildMessage(?string $reason, string $viewMode, array $context): array
    {
        // Logic copied from ArchiveAccessResolver::buildMessage for 100% parity
        
        // Rule 1: Visibility Restricted
        if (in_array($reason, ['product_restricted', 'category_restricted', 'visibility_restricted', 'attachment_restricted'])) {
            if (!empty($context['private_tier_name'])) {
                return [
                    'title' => strtoupper($context['private_tier_name']),
                    'body' => 'Members Only',
                    'icon' => 'diamond'
                ];
            }

            $tierName = $context['required_tier_name'] ?? 'Membership';
            return [
                'title' => 'Restricted View',
                'body' => "{$tierName} Tier Required",
                'icon' => 'lock'
            ];
        }

        // Rule 3: Early Access
        if (in_array($reason, ['early_access_locked', 'early_access_tier_required'])) {
            $days = $context['days_remaining'] ?? 0;
            $tierName = $context['early_access_tier_name'] ?? 'Member';
            
            return [
                'title' => "Unlocks in {$days} days",
                'body' => "Early Access: {$tierName}",
                'icon' => 'clock'
            ];
        }
        
        // Blur
        if ($reason === 'blurred') {
             $tierName = $context['clear_view_tier_name'] ?? 'Higher Tier';
             return [
                'title' => 'Restricted View',
                'body' => "{$tierName} Tier Required",
                'icon' => 'lock'
             ];
        }

        // Open/Default
        if ($viewMode === 'clear') {
             return [
                'title' => 'Open',
                'body' => $context['body'] ?? 'Access Granted',
                'icon' => 'info'
            ];
        }

        return [
            'title' => $context['title'] ?? 'Info',
            'body' => $context['body'] ?? 'Access Info',
            'icon' => 'lock'
        ];
    }

    protected function formatViewer(?MembershipTier $tier): array
    {
        return [
            'membership_tier_id' => $tier?->id,
            'membership_tier_name' => $tier?->name,
            'membership_level' => $tier?->level
        ];
    }
}
