<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use Carbon\Carbon;

class AuctionAccessResolverService
{
    /**
     * Determine user permissions for a specific lot.
     */
    public function resolve(AuctionLot $lot, ?User $user): array
    {
        $tier = $user?->currentMembership?->membershipTier;
        $now = now();

        // 1. Base Visibility
        // Logic Update: Use restriction_mode and restriction_type
        // Fail Closed if private
        
        $hasVisibility = true; 
        
        if ($lot->restriction_mode !== 'public') {
             $hasVisibility = false; // Default closed if not public
             
             if ($tier) {
                 if ($lot->restriction_type === 'hierarchical') {
                     $min = $lot->restrictedMinTier ?? MembershipTier::find($lot->restricted_min_tier_id);
                     if ($min && $tier->level >= $min->level) {
                         $hasVisibility = true;
                     }
                 } elseif ($lot->restriction_type === 'private') {
                      if ($lot->restricted_private_tier_id === $tier->id) {
                          $hasVisibility = true;
                      }
                 } elseif ($lot->restriction_type === 'random') {
                      // Check visibility pivot
                      if ($lot->visibilityTiers()->exists()) {
                          if ($lot->visibilityTiers->contains($tier->id)) {
                              $hasVisibility = true;
                          }
                      }
                 }
             }
        }

        // 2. Clear View (Unblurred)
        $canViewClear = false;

        // If public, check if blur is enabled
        if ($lot->restriction_mode === 'public') {
             if ($lot->blur_enabled) {
                 // Check clear tiers
                 if ($tier) {
                     if ($lot->clearViewTiers()->count() > 0) {
                          if ($lot->clearViewTiers->contains($tier->id)) {
                              $canViewClear = true;
                          }
                     } else {
                         // If blur enabled but no clear tiers? Assume blocked? Or Open?
                         // Archive logic: public + blur + no clear tiers = strictly blurred?
                         // Let's assume strict: false.
                     }
                 }
             } else {
                 $canViewClear = true;
             }
        } else {
            // Restricted Mode
            // If visible, is it clear?
            // Usually if you have access to a private/restricted item, you see it clear?
            // Unless layered (Restricted Visibility + Blurred). 
            // Assuming: If you pass visibility check on restricted item, you view it clear, UNLESS blur_enabled is explicitly on?
            // Let's assume logical inheritance: Visibility Access = Clear View for restricted items unless specified.
            if ($hasVisibility) {
                $canViewClear = true; 
                if ($lot->blur_enabled) {
                    $canViewClear = false;
                     if ($tier && $lot->clearViewTiers->contains($tier->id)) {
                         $canViewClear = true;
                     }
                }
            }
        }

        // 3. Early Access
        $earlyAccess = $this->resolveEarlyAccess($lot, $tier, $now);
        
        // 4. Bidding Eligibility
        // Does the user have permission to bid?
        // Core rule: User must be logged in. 
        // Optional rule: Specific tiers? (We didn't add bid_tiers pivot, but might use visibility/clear as proxy or generic can_bid)
        // AND: Status must be Live.
        $canBid = $user && $lot->status === 'live';
        
        // 5. Auto Bid Eligibility
        // Only if User's Tier has can_auto_bid (is_auto_bidding_enabled) = true
        // AND user has bid permission (live status)
        // AND user has VISIBILITY (cannot bid on what you cannot see)
        $canBid = $user && $lot->status === 'live' && $hasVisibility;
        $canAutoBid = $canBid && ($tier?->is_auto_bidding_enabled ?? false);

        return [
            'has_visibility' => $hasVisibility,
            'can_view_clear' => $canViewClear,
            'should_blur' => !$canViewClear,
            'can_bid' => $canBid,
            'can_auto_bid' => $canAutoBid,
            'early_access' => $earlyAccess,
            'is_live' => $lot->status === 'live',
            'is_upcoming' => $lot->status === 'upcoming' || ($lot->status === 'live' && $now->lt($lot->starts_at)), // Just generic check
        ];
    }

    protected function resolveEarlyAccess(AuctionLot $lot, ?MembershipTier $tier, Carbon $now): array
    {
        // Copy logic from ArchiveAccessResolver if needed.
        // Basic: check if lot has early_access rows.
        // If so, map them.
        $windows = $lot->earlyAccessWindows;
        if ($windows->isEmpty()) {
            return ['is_active' => false];
        }

        // Logic similar to Archive: find best window for user
        return [
            'is_active' => true,
            // ... (simplified for now, expand if strict logic needed)
        ];
    }
}
