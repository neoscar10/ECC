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
                // Use robust findBaseRestrictionUpgrade instead of just checking visibilityTiers
                $upgrade = $this->findBaseRestrictionUpgrade($lot);
                
                if ($upgrade) {
                    $context = [];
                     if ($lot->restriction_type === 'private') {
                          $context['private_tier_name'] = $upgrade['name'] ?? 'Private';
                     } else {
                          $context['required_tier_name'] = $upgrade['name'] ?? 'Higher';
                     }
                     // Merge context
                     $messageContext = array_merge($messageContext, $context);

                    $actions[] = [
                        'type' => 'upgrade_membership',
                        'label' => 'Upgrade to View',
                        'target_tier' => $this->formatTier($upgrade),
                        'deeplink' => '/membership/tiers',
                        'priority' => 'primary'
                    ];
                } else {
                     $messageContext['required_tier_name'] = 'Membership';
                }
            }
        }

        // Auto-Bid Eligibility Action
        if ($resolverResult['can_bid'] && !$resolverResult['can_auto_bid'] && $userTier) {
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

        return $this->commonPresenter->present(
            $viewMode,
            $reason,
            'lot',
            $user,
            $messageContext,
            $actions,
            $timing
        );
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

    protected function findTierUpgrade(AuctionLot $lot, string $relation) {
        // Find lowest tier in the relation set
        // $relation = 'visibilityTiers' or 'clearViewTiers'
        return $lot->$relation()->orderBy('level', 'asc')->first();
    }
    
    // Derived from ArchiveAccessResolver::findBaseRestrictionUpgrade
    protected function findBaseRestrictionUpgrade(AuctionLot $lot)
    {
        if ($lot->restriction_type === 'hierarchical') {
             return $lot->restrictedMinTier ?? MembershipTier::find($lot->restricted_min_tier_id);
        }

        if ($lot->restriction_type === 'random') {
             return $lot->visibilityTiers()->orderBy('level', 'asc')->first();
        }

        if ($lot->restriction_type === 'private') {
             return $lot->restrictedPrivateTier ?? MembershipTier::find($lot->restricted_private_tier_id);
        }
        
        // Fallback or explicit visibility tiers usage if not set?
        // If restriction_mode is restricted but type is null, maybe fallback?
        return $lot->visibilityTiers()->orderBy('level', 'asc')->first();
    }
    
    public function findAutoBidUpgrade(MembershipTier $currentTier)
    {
        // Find next tier with is_auto_bidding_enabled = true
        return MembershipTier::where('is_active', true)
            ->where('is_auto_bidding_enabled', true)
            ->where('level', '>', $currentTier->level)
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
