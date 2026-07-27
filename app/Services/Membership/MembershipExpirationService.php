<?php

namespace App\Services\Membership;

use App\Models\Membership;
use App\Models\MembershipTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembershipExpirationService
{
    /**
     * Process all expired active memberships across the system.
     *
     * @return int Number of memberships expired.
     */
    public function processExpirations(): int
    {
        $expiredMemberships = Membership::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expiredMemberships as $membership) {
            $this->expireMembership($membership);
            $count++;
        }

        return $count;
    }

    /**
     * Expire a single active membership and assign the lowest free tier if available.
     *
     * @param Membership $membership
     * @return Membership|null The newly created free tier membership, or null if no free tier assigned.
     */
    public function expireMembership(Membership $membership): ?Membership
    {
        if ($membership->status !== 'active') {
            return null;
        }

        return DB::transaction(function () use ($membership) {
            // 1. Mark existing membership as expired
            $membership->update([
                'status' => 'expired',
            ]);

            Log::info("Membership #{$membership->id} for User #{$membership->user_id} marked as expired.", [
                'user_id' => $membership->user_id,
                'tier_id' => $membership->membership_tier_id,
                'expired_at' => $membership->expires_at?->toIso8601String(),
            ]);

            // 2. Find the lowest free membership tier
            $freeTier = $this->getLowestFreeTier();

            if ($freeTier) {
                // 3. Assign free tier membership to user
                $newMembership = Membership::create([
                    'user_id' => $membership->user_id,
                    'membership_tier_id' => $freeTier->id,
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => null, // Free/basic tier does not expire
                    'approved_at' => now(),
                    'source_application_id' => $membership->source_application_id,
                ]);

                Log::info("Assigned free tier '{$freeTier->name}' (#{$freeTier->id}) to User #{$membership->user_id}.", [
                    'user_id' => $membership->user_id,
                    'new_membership_id' => $newMembership->id,
                ]);

                return $newMembership;
            }

            Log::info("No free membership tier available for User #{$membership->user_id}. User left with no active membership.");
            return null;
        });
    }

    /**
     * Locate the lowest/free tier available in the system.
     *
     * @return MembershipTier|null
     */
    public function getLowestFreeTier(): ?MembershipTier
    {
        // 1. Look for active tier where price is 0 or null
        $freeTier = MembershipTier::where('is_active', true)
            ->where(function ($q) {
                $q->where('price_amount', 0)
                  ->orWhere('price', 0)
                  ->orWhereNull('price_amount');
            })
            ->orderBy('level', 'asc')
            ->orderBy('sort_order', 'asc')
            ->first();

        return $freeTier;
    }
}
