<?php

namespace App\Services\Archive;

use App\Models\Archive\ArchiveCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ArchiveAccessService
{
    /**
     * Resolve the active membership tier ID for the user.
     */
    public function resolveUserTierId(User $user): ?int
    {
        $membership = $user->currentMembership;
        return $membership ? $membership->membership_tier_id : null;
    }

    /**
     * Apply access filtering to the category query.
     *
     * @param Builder $query
     * @param int|null $tierId
     * @param bool $includeLocked If true, returns all items but marks them as locked. If false, returns only accessible.
     * @return Builder
     */
    public function applyAccessibleScope(Builder $query, ?int $tierId, bool $includeLocked = false): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($tierId, $includeLocked) {
                // Always include public
                $q->where('visibility', 'public');

                // If include_locked is true, we simply don't filter out the restricted ones here
                // We just let them pass (and handle "is_accessible" property later)
                if ($includeLocked) {
                    $q->orWhere('visibility', 'restricted');
                    return;
                }

                // Otherwise, restrict to allowed tiers
                if ($tierId) {
                    $q->orWhere(function ($sub) use ($tierId) {
                        $sub->where('visibility', 'restricted')
                            ->whereHas('tiers', function ($pivot) use ($tierId) {
                                $pivot->where('membership_tier_id', $tierId);
                            });
                    });
                }
            });
    }

    /**
     * Check if a specific category is accessible by the user.
     */
    public function isAccessible(ArchiveCategory $category, ?int $tierId): bool
    {
        if (!$category->is_active) {
            return false;
        }

        if ($category->visibility === 'public') {
            return true;
        }

        if (!$tierId) {
            return false;
        }

        // Check if tier is in the allowed list
        // Optimization: if relations loaded
        if ($category->relationLoaded('tiers')) {
            return $category->tiers->contains('id', $tierId);
        }

        return $category->tiers()->where('membership_tier_id', $tierId)->exists();
    }
    /**
     * Resolve the full membership tier for the user.
     */
    public function resolveUserTier(User $user): ?\App\Models\MembershipTier
    {
        $membership = $user->currentMembership()->with('membershipTier')->first();
        return $membership ? $membership->membershipTier : null;
    }

    /**
     * Check if a specific product is accessible by the user.
     * Returns computed permission object.
     */
    public function productAccess(User $user, \App\Models\Archive\ArchiveProduct $product): array
    {
        $userTier = $this->resolveUserTier($user);
        
        // 1. Base Visibility (Can list/access AT ALL?)
        // If not accessible -> BLOCKED
        if (!$this->isProductVisible($product, $userTier)) {
             return $this->buildAccessResponse('blocked', 'visibility_blocked', $product, $userTier);
        }

        // 2. Blur / Clear View Logic
        // If visible, check if we need to blur it
        if ($product->blur_enabled) {
             // Check if user is in the CLEAR VIEW list
             // We can optimize this if needed, but for now:
             $hasClearView = $this->hasClearViewAccess($product, $userTier);
             
             if (!$hasClearView) {
                 return $this->buildAccessResponse('blur', 'blurred', $product, $userTier);
             }
        }

        // 3. Early Access Check (If not live yet)
        // If visible but early access logic applies
        if (!$product->go_live_now && $product->go_live_at && now()->lt($product->go_live_at)) {
             // Visibility already passed, but early access might still block FULL access if we treat early access purely
             // as a "can you see it" gate. 
             // However, existing logic says if early access enabled, user sees it if they have early access window.
             // If they don't have early access window, they shouldn't have passed visibility check?
             // Actually, `isProductVisible` handles early access logic too.
             // But let's refining 'clear' vs 'blur' for early access if needed.
             // For now, if visible and early access active, it's clear.
        }

        return [
            'can_list' => true,
            'view_mode' => 'clear',
            'reason_code' => 'allowed',
            'message' => null,
            'action' => null
        ];
    }

    protected function isProductVisible($product, $userTier): bool
    {
         // 1. Check Active
        if (!$product->is_active) return false;

        // 2. Check Live/Schedule
        $isLive = $product->go_live_now || ($product->go_live_at && now()->gte($product->go_live_at));
        
        if ($isLive) {
            return $this->checkStandardRestriction($product, $userTier);
        }

        // 3. Early Access
        if ($product->early_access_enabled && $userTier) {
            $window = $product->earlyAccessWindows()
                ->where('membership_tier_id', $userTier->id)
                ->where('access_at', '<=', now())
                ->first();
            if ($window) return true;
        }

        return false;
    }

    protected function hasClearViewAccess($product, $userTier): bool
    {
        if (!$userTier) return false;
        
        if ($product->relationLoaded('clearViewTiers')) {
             return $product->clearViewTiers->contains('id', $userTier->id);
        }
        return $product->clearViewTiers()->where('membership_tier_id', $userTier->id)->exists();
    }

    protected function buildAccessResponse($mode, $reason, $product, $userTier): array
    {
        $response = [
            'can_list' => ($mode !== 'blocked'), // If blurred, can_list is true (it appears in list)
            'view_mode' => $mode, // blocked, blur, clear
            'reason_code' => $reason,
            'message' => null,
            'action' => null
        ];

        // Add upgrade CTA
        $upgrade = null;
        if ($mode === 'blocked') {
            $upgrade = $this->findBaseRestrictionUpgrade($product);
        } elseif ($mode === 'blur') {
            // Find upgrade that grants Clear View
            $upgrade = $this->findClearViewUpgrade($product);
        }

        if ($upgrade) {
            $response['message'] = [
                'title' => 'Restricted Access',
                'body' => $upgrade['message'],
                'icon_type' => 'lock'
            ];
            $response['action'] = [
                'type' => 'upgrade_tier',
                'membership_tier_id' => $upgrade['tier_id']
            ];
        }

        return $response;
    }

    protected function findClearViewUpgrade($product): ?array
    {
        // Find lowest tier in clearViewTiers
        $tier = $product->clearViewTiers()->orderBy('level', 'asc')->first();
        if ($tier) {
             return [
                'tier_id' => $tier->id,
                'message' => "Upgrade to {$tier->name} to view clearly."
            ];
        }
        return null;
    }

    /**
     * Deprecated wrapper if needed, or remove.
     * Keeping for compatibility with standard restriction check function usage inside class
     */
    public function isProductAccessible(\App\Models\Archive\ArchiveProduct $product, ?\App\Models\MembershipTier $userTier): bool
    {
        return $this->isProductVisible($product, $userTier);
    }

    protected function checkStandardRestriction($entity, ?\App\Models\MembershipTier $userTier): bool
    {
        if ($entity->restriction_mode === 'public') return true;

        if (!$userTier) return false;

        if ($entity->restriction_type === 'hierarchical') {
            $minTier = $entity->restrictedMinTier; 
            if ($minTier) {
                 return $userTier->level >= $minTier->level;
            }
            return false;
        }

        if ($entity->restriction_type === 'random') {
            if ($entity->relationLoaded('tiers')) {
                return $entity->tiers->contains('id', $userTier->id);
            }
            return $entity->tiers()->where('membership_tier_id', $userTier->id)->exists();
        }

        if ($entity->restriction_type === 'private') {
            return $entity->restricted_private_tier_id === $userTier->id;
        }

        return false;
    }

    public function getRecommendedUpgrade(\App\Models\Archive\ArchiveProduct $product): ?array 
    {
         // Re-implement or wrapper
         // This seems used by old logic. We should unify.
         // For now, keeping logic but relying on buildAccessResponse helper concepts if possible.
         // Left mostly as is for `blocked` state fallback.
         
         return $this->findBaseRestrictionUpgrade($product);
    }

    protected function findBaseRestrictionUpgrade($entity): ?array
    {
        if ($entity->restriction_mode === 'public') return null;

        if ($entity->restriction_type === 'hierarchical') {
            $min = $entity->restrictedMinTier;
            if ($min) {
                return [
                    'tier_id' => $min->id,
                    'tier_name' => $min->name,
                    'message' => "Upgrade to {$min->name} to unlock.",
                    'reason' => 'tier_required'
                ];
            }
        }
        
        if ($entity->restriction_type === 'random') {
            // Pick lowest level allowed tier
            $tier = $entity->tiers()->orderBy('level', 'asc')->first();
            if ($tier) {
                 return [
                    'tier_id' => $tier->id,
                    'tier_name' => $tier->name,
                    'message' => "Upgrade to {$tier->name} to unlock.",
                    'reason' => 'tier_required'
                ];
            }
        }

        if ($entity->restriction_type === 'private') {
             $p = $entity->restrictedPrivateTier;
             if ($p) {
                 return [
                    'tier_id' => $p->id,
                    'tier_name' => $p->name,
                    'message' => "Exclusive to {$p->name} members.",
                    'reason' => 'private_collection'
                ];
             }
        }
        
        return null;
    }
    
    public function isAttachmentAccessible(\App\Models\Archive\ArchiveProductAttachment $att, \App\Models\Archive\ArchiveProduct $product, ?\App\Models\MembershipTier $userTier): bool 
    {
        // 1. Base Product Access Requirement (Attachments are sub-resources)
        if (!$this->isProductAccessible($product, $userTier)) {
            return false;
        }

        // 2. Attachment Specific Rules
        if ($att->restriction_mode === 'inherit') {
            return true; // Covered by product access
        }
        
        if ($att->restriction_mode === 'public') {
            return true; // Still within product scope, which is verified
        }
        
        return $this->checkStandardRestriction($att, $userTier);
    }

    public function getAttachmentUpgrade(\App\Models\Archive\ArchiveProductAttachment $att): ?array
    {
        return $this->findBaseRestrictionUpgrade($att);
    }
}
