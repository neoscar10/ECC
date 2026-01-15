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
     * Returns true if accessible, false if locked.
     */
    public function isProductAccessible(\App\Models\Archive\ArchiveProduct $product, ?\App\Models\MembershipTier $userTier): bool
    {
        // 1. Check parent category (Must be accessible first)
        // Optimization: Assume controller checks category access or pre-loads it. 
        // But strict check:
        // if (!$this->isAccessible($product->category, $userTier?->id)) return false;

        // 2. Check Active
        if (!$product->is_active) return false;

        // 3. Check Live Status Logic
        $isLive = $product->go_live_now || ($product->go_live_at && now()->gte($product->go_live_at));
        
        // If live, standard restriction rules apply
        if ($isLive) {
            return $this->checkStandardRestriction($product, $userTier);
        }

        // If NOT live, check Early Access
        if ($product->early_access_enabled && $userTier) {
            // Check if user's tier has an active early access window
            $window = $product->earlyAccessWindows()
                ->where('membership_tier_id', $userTier->id)
                ->where('access_at', '<=', now())
                ->first();
            
            if ($window) return true;
        }

        return false;
    }

    protected function checkStandardRestriction($entity, ?\App\Models\MembershipTier $userTier): bool
    {
        if ($entity->restriction_mode === 'public') return true;

        if (!$userTier) return false;

        if ($entity->restriction_type === 'hierarchical') {
            $minTier = $entity->restrictedMinTier; 
            // If min tier configured
            if ($minTier) {
                 // Check levels (assuming 'level' column exists per earlier verification)
                 return $userTier->level >= $minTier->level;
            }
            return false; // Should not happen if config correct
        }

        if ($entity->restriction_type === 'random') {
            // Check pivot
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
         $isLive = $product->go_live_now || ($product->go_live_at && now()->gte($product->go_live_at));
         
         // A: Blocked due to base restriction (Live)
         if ($isLive) {
             return $this->findBaseRestrictionUpgrade($product);
         }
         
         // B: Blocked due to Early Access (Not Live)
         if ($product->early_access_enabled) {
             // Find earliest active window or next upcoming
             // 1. Active window? (access_at <= now) -> Lowest Level Tier
             $bestActive = $product->earlyAccessWindows()
                 ->where('access_at', '<=', now())
                 ->join('membership_tiers', 'archive_product_early_access.membership_tier_id', '=', 'membership_tiers.id')
                 ->orderBy('membership_tiers.level', 'asc')
                 ->first();
                 
             if ($bestActive) {
                  return [
                      'tier_id' => $bestActive->membership_tier_id,
                      'tier_name' => $bestActive->name,
                      'message' => "Upgrade to {$bestActive->name} to access this now.",
                      'reason' => 'early_access_active'
                  ];
             }
             
             // 2. Upcoming? (access_at > now) -> Soonest date
             $soonest = $product->earlyAccessWindows()
                 ->where('access_at', '>', now())
                 ->orderBy('access_at', 'asc')
                 ->first();
                 
             if ($soonest) {
                  $tierName = $soonest->tier->name ?? 'Higher Tier';
                  $date = $soonest->access_at->format('d M');
                  return [
                      'tier_id' => $soonest->membership_tier_id,
                      'tier_name' => $tierName,
                      'message' => "Upgrade to {$tierName} for early access on {$date}.",
                      'reason' => 'early_access_upcoming'
                  ];
             }
         }
         
         // Fallback if not live & no early access, or just base fallback
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
