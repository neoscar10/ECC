<?php

namespace App\Services\Membership;

use App\Models\User;
use App\Models\MembershipTier;

class MembershipTierResolver
{
    /**
     * Resolve the current membership tier for a user.
     * 
     * @param User|null $user
     * @return MembershipTier|null
     */
    public function resolveForUser(?User $user): ?MembershipTier
    {
        return $user?->currentMembership?->membershipTier;
    }

    /**
     * Get a formatted array of tier details including privileges and features, designed for UI display models.
     *
     * @param int $tierId
     * @return array|null
     */
    public function getTierDetailsForDisplay(int $tierId): ?array
    {
        $tier = $this->getTierWithDetails($tierId);

        if (!$tier) {
            return null;
        }

        $allPrivileges = $tier->privileges->map(fn($p) => $p->label ?: $p->name)->all();
        $features = $tier->features->map(fn($f) => $f->title)->all();

        return [
            'id' => $tier->id,
            'name' => $tier->name,
            'level' => $tier->level,
            'price_formatted' => $tier->price > 0 ? 'INR ' . number_format($tier->price) : 'Free',
            'duration_label' => 'Year',
            'benefits' => !empty($features) ? $features : array_slice($allPrivileges, 0, 4),
            'privileges' => $allPrivileges,
            'features' => $features
        ];
    }

    /**
     * Shared service method for fetching full tier details (used by API and Web).
     *
     * @param int $id
     * @return MembershipTier|null
     */
    public function getTierWithDetails(int $id): ?MembershipTier
    {
        return MembershipTier::with(['privileges', 'features'])
            ->where('is_active', true)
            ->find($id);
    }
}
