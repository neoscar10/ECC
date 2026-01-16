<?php

namespace App\Services\Archive;

use App\Models\Archive\ArchiveCategory;
use App\Models\Archive\ArchiveProduct;
use App\Models\Archive\ArchiveProductAttachment;
use App\Models\User;
use App\Models\MembershipTier;

class ArchiveAccessResolver
{
    /**
     * Resolve the access object for a category.
     */
    public function resolveCategoryAccess(ArchiveCategory $category, ?User $user, ?MembershipTier $userTier): array
    {
        if ($category->visibility === 'public') {
            return $this->buildOpenAccess('Category is public.');
        }

        if (!$user) {
             return $this->buildLockedAccess(
                 'category_restricted',
                 'Sign in to view.',
                 ['type' => 'subscribe', 'label' => 'Login / Register', 'deeplink' => '/login'],
                 $userTier
             );
        }

        $hasAccess = $this->checkStandardRestriction($category, $userTier);
        
        if ($hasAccess) {
            return $this->buildOpenAccess('Access granted.', $userTier);
        }

        // Recommend Upgrade
        $upgrade = $this->findBaseRestrictionUpgrade($category);
        return $this->buildLockedAccess(
            'category_restricted',
            $upgrade['message'] ?? 'Restricted Category',
            [
                'type' => 'upgrade_membership',
                'label' => 'Upgrade',
                'target_tier' => $upgrade ? $this->formatTier($upgrade['tier']) : null,
                'deeplink' => '/membership/tiers'
            ],
            $userTier,
            'lock'
        );
    }

    /**
     * Resolve the access object for a product.
     */
    public function resolveProductAccess(ArchiveProduct $product, ?User $user, ?MembershipTier $userTier): array
    {
        // 1. Check Live Status First
        $isLive = $product->go_live_now || ($product->go_live_at && now()->gte($product->go_live_at));
        
        if (!$isLive) {
            // Not Live Yet -> Check Early Access
            if ($product->early_access_enabled) {
                // If no user, prompt login for early access checking
                if (!$user) {
                     return $this->buildLockedAccess(
                         'early_access_locked',
                         'Early access available for members.',
                         ['type' => 'subscribe', 'label' => 'Login', 'deeplink' => '/login'],
                         $userTier,
                         'clock',
                         ['go_live_at' => $product->go_live_at?->toIso8601String()]
                     );
                }

                $activeWindow = $product->earlyAccessWindows()
                    ->where('membership_tier_id', $userTier?->id)
                    ->where('access_at', '<=', now())
                    ->first();

                if ($activeWindow) {
                    return $this->buildOpenAccess('Early Access Granted.', $userTier, ['go_live_at' => $product->go_live_at?->toIso8601String()]);
                }
                
                // User has no active window, find best recommendation
                $recommendation = $this->findEarlyAccessRecommendation($product);
                
                return $this->buildLockedAccess(
                    'early_access_locked',
                    $recommendation['message'] ?? 'Early Access Required',
                    [
                        'type' => 'upgrade_membership',
                        'label' => 'Get Early Access',
                        'target_tier' => $recommendation['tier'] ? $this->formatTier($recommendation['tier']) : null,
                        'deeplink' => '/membership/tiers'
                    ],
                    $userTier,
                    'clock',
                    [
                        'go_live_at' => $product->go_live_at?->toIso8601String(),
                        'next_access_at' => $recommendation['access_at'] ?? null
                    ]
                );
            }
            
            // Not Live & No Early Access
            return $this->buildLockedAccess(
                'not_live_yet',
                'Coming Soon',
                ['type' => 'wait', 'label' => 'Coming Soon'],
                $userTier,
                'clock',
                ['go_live_at' => $product->go_live_at?->toIso8601String()]
            );
        }

        // 2. Product is Live -> Check Standard Restrictions
        if ($product->restriction_mode === 'public') {
            return $this->buildOpenAccess('Product is public.', $userTier);
        }
        
        if (!$user) {
             return $this->buildLockedAccess(
                 'product_restricted',
                 'Members Only Content',
                 ['type' => 'subscribe', 'label' => 'Join Now', 'deeplink' => '/register'],
                 $userTier,
                 'lock'
             );
        }

        if ($this->checkStandardRestriction($product, $userTier)) {
            return $this->buildOpenAccess('Access Granted.', $userTier);
        }

        // Recommend Upgrade
        $upgrade = $this->findBaseRestrictionUpgrade($product);
        return $this->buildLockedAccess(
            'product_restricted',
            $upgrade['message'] ?? 'Upgrade Memebership',
            [
                'type' => 'upgrade_membership',
                'label' => 'Upgrade',
                'target_tier' => $upgrade ? $this->formatTier($upgrade['tier']) : null,
                'deeplink' => '/membership/tiers'
            ],
            $userTier,
            'lock'
        );
    }

    /**
     * Resolve the access object for an attachment.
     */
    public function resolveAttachmentAccess(ArchiveProductAttachment $attachment, ArchiveProduct $product, ?User $user, ?MembershipTier $userTier): array
    {
        // 1. Inherited Product Access (must have product access first)
        // Optimization: Caller should usually pass the product access result if known, but here we strictly check.
        // If product is locked, attachment is definitely locked.
        $productAccess = $this->resolveProductAccess($product, $user, $userTier);
        if (!$productAccess['open']) {
            // Return product lock reason but sourced as attachment
            $productAccess['source'] = 'attachment'; 
            $productAccess['reason'] = 'product_restricted'; // Parent locked
            return $productAccess;
        }

        // 2. Attachment Specifics
        if ($attachment->restriction_mode === 'inherit') {
             return $this->buildOpenAccess('Inherited Access.', $userTier);
        }
        
        if ($attachment->restriction_mode === 'public') {
            // Public means "Public to anyone who can see the product".
            // Since we passed product check above, it's open.
            return $this->buildOpenAccess('Public Attachment.', $userTier);
        }

        // Restricted Subset
        if (!$user) {
             return $this->buildLockedAccess('attachment_restricted', 'Login required.', ['type' => 'subscribe'], $userTier);
        }

        if ($this->checkStandardRestriction($attachment, $userTier)) {
             return $this->buildOpenAccess('Access Granted.', $userTier);
        }

        $upgrade = $this->findBaseRestrictionUpgrade($attachment);
        return $this->buildLockedAccess(
            'attachment_restricted',
            $upgrade['message'] ?? 'Premium Attachment',
            [
                'type' => 'upgrade_membership',
                'label' => 'Upgrade',
                'target_tier' => $upgrade ? $this->formatTier($upgrade['tier']) : null,
                'deeplink' => '/membership/tiers'
            ],
            $userTier
        );
    }

    // --- Helpers ---

    protected function checkStandardRestriction($entity, ?MembershipTier $userTier): bool
    {
        if ($entity->restriction_mode === 'public') return true;
        if ($entity instanceof ArchiveCategory && $entity->visibility === 'public') return true;

        if (!$userTier) return false;

        // Unified logic for Products, Categories, Attachments
        // Note: Category uses 'visibility'='restricted', Products use 'restriction_mode'='restricted'
        // We assume $entity has restriction_type, etc. (Model standardization helps)
        
        $type = $entity->restriction_type; 
        // Categories don't share identical col names in standard ECC code usually? 
        // Let's handle Category specific (pivot check only usually) if different.
        // Checking Service logic: Category has 'tiers' relation.
        
        if ($entity instanceof ArchiveCategory) {
             // Category usually just random pivot logic in ECC templates unless heavily customized.
             // Service check: `$category->tiers->contains($tierId)`
             if ($entity->relationLoaded('tiers')) {
                return $entity->tiers->contains('id', $userTier->id);
             }
             return $entity->tiers()->where('membership_tier_id', $userTier->id)->exists();
        }

        // Product/Attachment Logic
        if ($type === 'hierarchical') {
            $minTierId = $entity->restricted_min_tier_id;
            if (!$minTierId) return true; // Config error? allow or block? default block safe.
            
            $minTier = $entity->restrictedMinTier; // Assumed loaded or fetched
            if (!$minTier) $minTier = MembershipTier::find($minTierId);
            
            if ($minTier) {
                return $userTier->level >= $minTier->level;
            }
            return false;
        }

        if ($type === 'random') {
             if ($entity->relationLoaded('tiers')) {
                return $entity->tiers->contains('id', $userTier->id);
             }
             return $entity->tiers()->where('membership_tier_id', $userTier->id)->exists();
        }

        if ($type === 'private') {
            return $entity->restricted_private_tier_id === $userTier->id;
        }

        return false;
    }

    protected function findBaseRestrictionUpgrade($entity): ?array
    {
        if ($entity instanceof ArchiveCategory) {
             // Suggest lowest level allowed tier
             $tier = $entity->tiers()->orderBy('level', 'asc')->first();
             if ($tier) return ['tier' => $tier, 'message' => "Available to {$tier->name} members."];
             return null;
        }

        if ($entity->restriction_type === 'hierarchical') {
             $minTier = $entity->restrictedMinTier ?? MembershipTier::find($entity->restricted_min_tier_id);
             if ($minTier) return ['tier' => $minTier, 'message' => "Upgrade to {$minTier->name}."];
        }

        if ($entity->restriction_type === 'random') {
             $tier = $entity->tiers()->orderBy('level', 'asc')->first();
             if ($tier) return ['tier' => $tier, 'message' => "Available to {$tier->name}."];
        }

        if ($entity->restriction_type === 'private') {
              $p = $entity->restrictedPrivateTier ?? MembershipTier::find($entity->restricted_private_tier_id);
              if ($p) return ['tier' => $p, 'message' => "Exclusive to {$p->name}."];
        }
        
        return null;
    }

    protected function findEarlyAccessRecommendation(ArchiveProduct $product): array
    {
        // 1. Find currently active windows (access_at <= now), sort by lowest tier level
        $bestActive = $product->earlyAccessWindows()
             ->where('access_at', '<=', now())
             ->with('tier')
             ->get()
             ->sortBy(fn($w) => $w->tier->level ?? 999)
             ->first();
             
        if ($bestActive) {
            return [
                'tier' => $bestActive->tier,
                'access_at' => $bestActive->access_at->toIso8601String(),
                'message' => "Upgrade to {$bestActive->tier->name} for immediate access."
            ];
        }

        // 2. Find ANY upcoming windows, sort by soonest date
        $soonest = $product->earlyAccessWindows()
             ->where('access_at', '>', now())
             ->with('tier')
             ->orderBy('access_at', 'asc')
             ->first();
             
        if ($soonest) {
             return [
                'tier' => $soonest->tier,
                'access_at' => $soonest->access_at->toIso8601String(),
                'message' => "Upgrade to {$soonest->tier->name} to access on " . $soonest->access_at->format('d M')
            ];
        }
        
        return ['tier' => null, 'message' => 'Early access not available for your tier.'];
    }

    protected function buildOpenAccess(string $title, ?MembershipTier $userTier = null, array $timing = []): array
    {
        return [
            'open' => true,
            'reason' => null,
            'source' => 'product', // caller can override
            'viewer' => $this->formatViewer($userTier),
            'message' => [
                'title' => 'Open',
                'body' => $title,
                'icon' => 'info'
            ],
            'action' => ['type' => 'none', 'label' => null, 'target_tier' => null, 'deeplink' => null],
            'timing' => array_merge(['go_live_at' => null, 'next_access_at' => null], $timing)
        ];
    }

    protected function buildLockedAccess(string $reason, string $msgBody, array $action, ?MembershipTier $userTier, string $icon = 'lock', array $timing = []): array
    {
        return [
            'open' => false,
            'reason' => $reason,
            'source' => 'product', // caller override
            'viewer' => $this->formatViewer($userTier),
            'message' => [
                'title' => 'Restricted',
                'body' => $msgBody,
                'icon' => $icon
            ],
            'action' => array_merge(['type' => 'wait', 'label' => null, 'target_tier' => null, 'deeplink' => null], $action),
            'timing' => array_merge(['go_live_at' => null, 'next_access_at' => null], $timing)
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

    protected function formatTier($tier): array
    {
        return [
            'id' => $tier->id,
            'name' => $tier->name,
            'level' => $tier->level,
            'price_amount' => $tier->price_amount ?? 0, // Assuming column exists
            'currency' => $tier->currency ?? 'INR'
        ];
    }
}
