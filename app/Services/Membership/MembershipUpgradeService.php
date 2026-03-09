<?php

namespace App\Services\Membership;

use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipTier;
use Illuminate\Support\Facades\DB;
use Exception;

class MembershipUpgradeService
{
    /**
     * Process an active membership upgrade for an existing authenticated user.
     * 
     * @param User $user The authenticated user upgrading.
     * @param int $newTierId The requested target MembershipTier ID.
     * @return array
     */
    public function upgradeUserMembership(User $user, int $newTierId): array
    {
        return DB::transaction(function () use ($user, $newTierId) {
            $newTier = MembershipTier::findOrFail($newTierId);
            
            // 1. Locate current active membership natively.
            $currentMembership = $user->currentMembership;
            
            if (!$currentMembership) {
                // If they have no active membership, they shouldn't technically be in the 'upgrade' flow,
                // but we safely support creating a fresh record to ensure robustness if they hit this route.
                return $this->createNewActiveMembership($user, $newTier);
            }

            // Reject invalid downgrade scenarios if requested
            if ($newTier->sort_order < $currentMembership->membershipTier->sort_order) {
                throw new Exception("Cannot downgrade tier during an upgrade flow.");
            }

            // 2. Expire the old Membership gracefully
            $currentMembership->update([
                'status' => 'expired',
                'expires_at' => now()
            ]);

            // 3. Create the new Active Membership natively without a pending application step
            $newMembership = Membership::create([
                'user_id' => $user->id,
                'membership_tier_id' => $newTier->id,
                'status' => 'active',
                'approved_at' => now(), // Auto-approved for verified upgrades
                'started_at' => now(),
                // null source_application_id explicitly as it was not applied for
            ]);

            return [
                'old_membership' => $currentMembership,
                'new_membership' => $newMembership
            ];
        });
    }

    /**
     * Fallback creation of a fresh membership if somehow bypassing base validation natively.
     */
    protected function createNewActiveMembership(User $user, MembershipTier $tier): array
    {
        $newMembership = Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'approved_at' => now(), 
            'started_at' => now(),
        ]);

        return [
            'old_membership' => null,
            'new_membership' => $newMembership
        ];
    }
}
