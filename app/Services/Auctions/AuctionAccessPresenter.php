<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionLot;
use App\Models\Auctions\AuctionLotAttachment;
use App\Models\MembershipTier;
use App\Models\User;
use Unicodeveloper\Identify\Identify; // Unused but check imports
use App\Services\Common\AccessPresentationService;

class AuctionAccessPresenter
{
    protected $commonPresenter;

    public function __construct(AccessPresentationService $commonPresenter)
    {
        $this->commonPresenter = $commonPresenter;
    }
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
            // Unified upgrade search: Find "Next Tier" that gives Clear View (and implies Visibility)
            $upgrade = $this->findTierUpgrade($lot, $userTier);
            
            if ($upgrade) {
                $label = 'Upgrade to View';
                if ($viewMode === 'blur') {
                    $label = 'Upgrade for Clear View';
                    $messageContext['clear_view_tier_name'] = $upgrade->name;
                } else {
                    $messageContext['required_tier_name'] = $upgrade->name;
                }

                $actions[] = [
                    'type' => 'upgrade_membership',
                    'label' => $label,
                    'target_tier' => $this->formatTier($upgrade),
                    'deeplink' => '/membership/tiers',
                    'priority' => 'primary'
                ];
            } else {
                // No higher tier available to solve this?
                // Might be private or misconfigured.
                if ($viewMode === 'blocked') {
                     $messageContext['required_tier_name'] = 'Membership';
                }
            }
        }

        // Auto-Bid Eligibility Action
        if ($resolverResult['can_bid'] && !($resolverResult['can_auto_bid'] ?? false)) {
             $upgrade = $this->findAutoBidUpgrade($userTier);
             if ($upgrade) {
                 $actions[] = [
                    'type' => 'upgrade_membership',
                    'label' => 'Upgrade to Enable Auto-Bid',
                    'target_tier' => $this->formatTier($upgrade),
                    'deeplink' => '/membership/tiers',
                    'priority' => 'secondary'
                 ];
             }
        }

        // 3. Delegate to Common Presenter
        $timing = [
            'go_live_at' => $lot->starts_at?->toIso8601String(),
            'next_access_at' => null
        ];

        $presented = $this->commonPresenter->present(
            $viewMode,
            $reason,
            'lot',
            $user,
            $messageContext,
            $actions,
            $timing
        );

        // Append can_auto_bid to the result
        $presented['can_auto_bid'] = $resolverResult['can_auto_bid'] ?? false;

        return $presented;
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
        
        return $this->commonPresenter->present(
            $viewMode,
            $reason,
            'attachment',
            $user,
            $context,
            $actions,
            $lotAccess['timing'] ?? []
        );
    }

    // --- Helpers ---
    
    // Kept for internal logic (finding upgrades)
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

    protected function findTierUpgrade(AuctionLot $lot, ?MembershipTier $currentUserTier)
    {
        // Rule: Find the lowest tier (by level) that:
        // 1) Has Visibility (is in allowlist if restricted)
        // 2) Has Clear View (passes blur strategy)
        // 3) Level > Current User Level
        
        $currentLevel = $currentUserTier ? $currentUserTier->level : 0;
        
        // Base Query: Tiers higher than current user
        $candidates = MembershipTier::where('level', '>', $currentLevel)
                                    ->where('is_active', true)
                                    ->orderBy('level', 'asc')
                                    ->get();

        foreach ($candidates as $tier) {
            // Check Visibility
            $hasVisibility = true; 
            if ($lot->restriction_mode !== 'public') {
                $hasVisibility = false;
                if ($lot->restriction_type === 'hierarchical') {
                     $min = $lot->restrictedMinTier ?? MembershipTier::find($lot->restricted_min_tier_id);
                     if ($min && $tier->level >= $min->level) $hasVisibility = true;
                } elseif ($lot->restriction_type === 'private') {
                     if ($lot->restricted_private_tier_id === $tier->id) $hasVisibility = true;
                } elseif ($lot->restriction_type === 'random' || $lot->restriction_type === 'allowlist') {
                     // Check DB pivot for this candidate tier
                     // Optimization: Eager load or check existence? 
                     // Since candidates are few, check usage.
                     if ($lot->visibilityTiers()->whereKey($tier->id)->exists()) $hasVisibility = true;
                }
            }
            
            if (!$hasVisibility) continue;

            // Check Clear View
            $canViewClear = false;
            if (!$lot->blur_enabled) {
                $canViewClear = true;
            } else {
                $blurStrategy = $lot->blur_strategy ?? 'hierarchical';
                if ($blurStrategy === 'hierarchical') {
                    $minClear = $lot->minClearViewTier ?? MembershipTier::find($lot->min_clear_view_tier_id);
                    // User Rule: tier.level >= min_clear_view_tier.level
                    if ($minClear && $tier->level >= $minClear->level) $canViewClear = true;
                } elseif ($blurStrategy === 'allowlist' || $blurStrategy === 'random') { // random legacy
                     if ($lot->clearViewTiers()->whereKey($tier->id)->exists()) $canViewClear = true;
                } elseif ($blurStrategy === 'private') {
                     if ($lot->clear_private_tier_id === $tier->id) $canViewClear = true;
                }
            }

            if ($canViewClear) {
                return $tier; // Found the lowest eligible tier
            }
        }

        return null;
    }

    // Legacy/Removed methods: findBaseRestrictionUpgrade, checkAttachmentAccess, findAttachmentUpgrade
    // We will keep checkAttachmentAccess/findAttachmentUpgrade but simplified if needed.
    // Ideally we apply the same logic for attachments if they have similar complexity.
    
    public function findAutoBidUpgrade(?MembershipTier $currentTier)
    {
        $currentLevel = $currentTier ? $currentTier->level : 0;

        // Find next tier with is_auto_bidding_enabled = true
        return MembershipTier::where('is_active', true)
            ->where('is_auto_bidding_enabled', true)
            ->where('level', '>', $currentLevel)
            ->orderBy('level', 'asc')
            ->first();
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
