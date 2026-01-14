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
}
