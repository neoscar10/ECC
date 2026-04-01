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
     */
    public function applyAccessibleScope(Builder $query, ?int $tierId, bool $includeLocked = false): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($tierId, $includeLocked) {
                // Always include public
                $q->where('visibility', 'public');

                if ($includeLocked) {
                    $q->orWhere('visibility', 'restricted');
                    return;
                }

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
        if (!$this->isProductVisible($product, $userTier)) {
             return $this->buildAccessResponse('blocked', 'visibility_blocked', $product, $userTier);
        }

        // 2. Blur / Clear View Logic
        if ($product->blur_enabled) {
             $hasClearView = $this->hasClearViewAccess($product, $userTier);
             
             if (!$hasClearView) {
                 return $this->buildAccessResponse('blur', 'blurred', $product, $userTier);
             }
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
        if (!$product->is_active) return false;

        $isLive = $product->go_live_now || ($product->go_live_at && now()->gte($product->go_live_at));
        
        if ($isLive) {
            return $this->checkStandardRestriction($product, $userTier);
        }

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
            'can_list' => ($mode !== 'blocked'),
            'view_mode' => $mode,
            'reason_code' => $reason,
            'message' => null,
            'action' => null
        ];

        $upgrade = null;
        if ($mode === 'blocked') {
            $upgrade = $this->findBaseRestrictionUpgrade($product, $userTier);
        } elseif ($mode === 'blur') {
            $upgrade = $this->findClearViewUpgrade($product, $userTier);
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

    protected function findClearViewUpgrade($product, ?\App\Models\MembershipTier $userTier): ?array
    {
        $tier = $product->clearViewTiers()->orderBy('level', 'asc')->first();
        if ($tier) {
             return [
                'tier_id' => $tier->id,
                'message' => $this->composeSmartAccessMessage($product, $userTier, $tier, true)
            ];
        }
        return null;
    }

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
         return $this->findBaseRestrictionUpgrade($product);
    }

    protected function findBaseRestrictionUpgrade($entity, ?\App\Models\MembershipTier $userTier = null): ?array
    {
        if ($entity->restriction_mode === 'public') return null;

        $product = ($entity instanceof \App\Models\Archive\ArchiveProduct) ? $entity : null;

        if ($entity->restriction_type === 'hierarchical') {
            $min = $entity->restrictedMinTier;
            if ($min) {
                return [
                    'tier_id' => $min->id,
                    'tier_name' => $min->name,
                    'message' => $product ? $this->composeSmartAccessMessage($product, $userTier, $min) : "Upgrade to {$min->name} to unlock.",
                    'reason' => 'tier_required'
                ];
            }
        }
        
        if ($entity->restriction_type === 'random') {
            $tier = $entity->tiers()->orderBy('level', 'asc')->first();
            if ($tier) {
                 return [
                    'tier_id' => $tier->id,
                    'tier_name' => $tier->name,
                    'message' => $product ? $this->composeSmartAccessMessage($product, $userTier, $tier) : "Upgrade to {$tier->name} to unlock.",
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
                    'message' => $product ? $this->composeSmartAccessMessage($product, $userTier, $p) : "Exclusive to {$p->name} members.",
                    'reason' => 'private_collection'
                ];
             }
        }
        
        return null;
    }

    /**
     * Compose a rich, time-aware message for restricted access.
     */
    public function composeSmartAccessMessage($product, $userTier, $targetTier, $isBlur = false): string
    {
        $messages = [];
        $now = now();

        // 1. Target Tier Early Access (The Recommendation)
        if ($product->early_access_enabled && $targetTier) {
            $targetWindow = $product->earlyAccessWindows()
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
        if ($product->early_access_enabled && $userTier && $userTier->id !== $targetTier->id) {
            $userWindow = $product->earlyAccessWindows()
                ->where('membership_tier_id', $userTier->id)
                ->first();
            
            if ($userWindow && $userWindow->access_at->gt($now)) {
                 $messages[] = "Your tier gains access " . $userWindow->access_at->diffForHumans(['parts' => 1]) . ".";
            }
        }

        // 3. General Access Timing
        // Only added if no specific tier access info was added (keeps it focused as per user request)
        if (empty($messages) && !$product->go_live_now && $product->go_live_at && $product->go_live_at->gt($now)) {
            $messages[] = "General access opens " . $product->go_live_at->diffForHumans(['parts' => 1]) . ".";
        }

        // Fallback or Basic Descriptor
        if (empty($messages)) {
            if ($isBlur) {
                return "Upgrade to {$targetTier->name} to view clearly.";
            }
            if ($product->restriction_type === 'private') {
                 return "Exclusive to {$targetTier->name} members.";
            }
            return "Upgrade to {$targetTier->name} to unlock.";
        }

        return implode(' ', array_slice($messages, 0, 2));
    }
    
    public function isAttachmentAccessible(\App\Models\Archive\ArchiveProductAttachment $att, \App\Models\Archive\ArchiveProduct $product, ?\App\Models\MembershipTier $userTier): bool 
    {
        if (!$this->isProductAccessible($product, $userTier)) {
            return false;
        }

        if ($att->restriction_mode === 'inherit') {
            return true;
        }
        
        if ($att->restriction_mode === 'public') {
            return true;
        }
        
        return $this->checkStandardRestriction($att, $userTier);
    }

    public function getAttachmentUpgrade(\App\Models\Archive\ArchiveProductAttachment $att, ?\App\Models\MembershipTier $userTier = null): ?array
    {
        return $this->findBaseRestrictionUpgrade($att, $userTier);
    }
}
