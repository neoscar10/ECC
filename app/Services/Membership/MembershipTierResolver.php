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
}
