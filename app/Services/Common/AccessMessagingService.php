<?php

namespace App\Services\Common;

use Carbon\Carbon;

class AccessMessagingService
{
    /**
     * Compose a rich, time-aware message for restricted access.
     * Compatible with ArchiveProduct and AuctionLot.
     */
    public function composeSmartAccessMessage($entity, $userTier, $targetTier, $isBlur = false): string
    {
        $messages = [];
        $now = now();

        // Normalize time fields (Archive: go_live_at, Auctions: starts_at)
        $goLiveAt = $entity->go_live_at ?? $entity->starts_at ?? null;
        $goLiveNow = property_exists($entity, 'go_live_now') ? $entity->go_live_now : ($entity->status === 'live');

        // 1. Target Tier Early Access (The Recommendation)
        if ($entity->early_access_enabled && $targetTier) {
            $targetWindow = $entity->earlyAccessWindows()
                ->where('membership_tier_id', $targetTier->id)
                ->first();

            if ($targetWindow) {
                if ($targetWindow->access_at->lte($now)) {
                    $messages[] = "Early access for {$targetTier->name} is active now.";
                } else {
                    $messages[] = "{$targetTier->name} early access begins " . $targetWindow->access_at->diffForHumans(['parts' => 1]) . ".";
                }
            }
        }

        // 2. User's Current Tier Access (If applicable and different from target)
        if ($entity->early_access_enabled && $userTier && $userTier->id !== $targetTier->id) {
            $userWindow = $entity->earlyAccessWindows()
                ->where('membership_tier_id', $userTier->id)
                ->first();
            
            if ($userWindow && $userWindow->access_at->gt($now)) {
                 $messages[] = "Your tier gains access " . $userWindow->access_at->diffForHumans(['parts' => 1]) . ".";
            }
        }

        // 3. General Access Timing
        // Only added if no specific tier access info was added (keeps it focused as per user request)
        if (empty($messages) && !$goLiveNow && $goLiveAt && $goLiveAt->gt($now)) {
            $messages[] = "General access opens " . $goLiveAt->diffForHumans(['parts' => 1]) . ".";
        }

        // Fallback or Basic Descriptor
        if (empty($messages)) {
            if ($isBlur) {
                return "Upgrade to {$targetTier->name} to view clearly.";
            }
            if ($entity->restriction_type === 'private') {
                 return "Exclusive to {$targetTier->name} members.";
            }
            return "Upgrade to {$targetTier->name} to unlock.";
        }

        // Return combined message, limited to 2 parts for UI constraints
        return implode(' ', array_slice($messages, 0, 2));
    }
}
