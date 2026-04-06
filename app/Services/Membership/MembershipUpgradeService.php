<?php

namespace App\Services\Membership;

use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipTier;
use App\Models\MembershipApplication;
use Illuminate\Support\Facades\DB;
use Exception;

class MembershipUpgradeService
{
    /**
     * Get a prorated upgrade calculation from the user's active membership constraints.
     */
    public function getUpgradeQuote(User $user, int $targetTierId): array
    {
        $targetTier = MembershipTier::findOrFail($targetTierId);
        $targetPrice = (float)$targetTier->price;

        $currentMembership = $user->currentMembership;
        
        // If there's no active membership or it's expired, full price.
        if (!$currentMembership || $currentMembership->status !== 'active' || !$currentMembership->expires_at) {
            return [
                'current_tier' => null,
                'target_tier' => [
                    'id' => $targetTier->id,
                    'name' => $targetTier->name,
                ],
                'current_membership_id' => null,
                'current_tier_price' => 0.0,
                'target_tier_price' => $targetPrice,
                'current_membership_start' => null,
                'current_membership_expiry' => null,
                'total_duration_days' => 0,
                'remaining_days' => 0,
                'unused_credit' => 0.0,
                'payable_amount' => $targetPrice,
                'currency' => 'INR',
                'is_eligible' => true,
                'reason' => null
            ];
        }

        $currentTier = $currentMembership->membershipTier;
        $currentPrice = (float)$currentTier->price;
        $totalDurationDays = max(1, $currentTier->duration_days ?? 365);

        // Check if downgrade or same
        if ($targetTier->sort_order <= $currentTier->sort_order) {
            return [
                'current_tier' => [
                    'id' => $currentTier->id,
                    'name' => $currentTier->name,
                ],
                'target_tier' => [
                    'id' => $targetTier->id,
                    'name' => $targetTier->name,
                ],
                'current_membership_id' => $currentMembership->id,
                'current_tier_price' => $currentPrice,
                'target_tier_price' => $targetPrice,
                'current_membership_start' => $currentMembership->started_at,
                'current_membership_expiry' => $currentMembership->expires_at,
                'total_duration_days' => $totalDurationDays,
                'remaining_days' => 0,
                'unused_credit' => 0.0,
                'payable_amount' => $targetPrice,
                'currency' => 'INR',
                'is_eligible' => false,
                'reason' => 'Cannot upgrade to a tier identical or lower than the current tier.'
            ];
        }

        // Calculate remaining credit natively based on carbon instance
        $expiresAt = \Carbon\Carbon::parse($currentMembership->expires_at);
        $now = now()->startOfDay();
        $end = $expiresAt->startOfDay();
        
        $remainingDays = 0;
        if ($end->isAfter($now)) {
            $remainingDays = $now->diffInDays($end);
            if ($remainingDays > $totalDurationDays) {
                $remainingDays = $totalDurationDays; // Hard Cap it
            }
        }

        $dailyValue = $currentPrice / $totalDurationDays;
        $unusedCredit = $dailyValue * $remainingDays;
        
        $payableAmount = max(0.0, $targetPrice - $unusedCredit);

        return [
            'current_tier' => [
                'id' => $currentTier->id,
                'name' => $currentTier->name,
            ],
            'target_tier' => [
                'id' => $targetTier->id,
                'name' => $targetTier->name,
            ],
            'current_membership_id' => $currentMembership->id,
            'current_tier_price' => $currentPrice,
            'target_tier_price' => $targetPrice,
            'current_membership_start' => $currentMembership->started_at?->toIso8601String(),
            'current_membership_expiry' => $currentMembership->expires_at?->toIso8601String(),
            'total_duration_days' => $totalDurationDays,
            'remaining_days' => $remainingDays,
            'unused_credit' => round($unusedCredit, 2),
            'payable_amount' => round($payableAmount, 2),
            'currency' => 'INR',
            'is_eligible' => true,
            'reason' => null
        ];
    }

    /**
     * Process an active membership upgrade for an existing authenticated user.
     * 
     * @param User $user The authenticated user upgrading.
     * @param int $newTierId The requested target MembershipTier ID.
     * @param array|null $quoteSnapshot Optional quote data for audit-safe record keeping.
     * @return array
     */
    public function upgradeUserMembership(User $user, int $newTierId, ?array $quoteSnapshot = null): array
    {
        return DB::transaction(function () use ($user, $newTierId, $quoteSnapshot) {
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

            // 3. Compute the new membership expiry from the target tier's duration_days.
            //    This is critical: without expires_at, future prorated quotes break.
            $targetDurationDays = max(1, $newTier->duration_days ?? 365);
            $newExpiresAt = now()->addDays($targetDurationDays);

            // 4. Create the new Active Membership with correct expiry
            $newMembership = Membership::create([
                'user_id' => $user->id,
                'membership_tier_id' => $newTier->id,
                'status' => 'active',
                'approved_at' => now(), // Auto-approved for verified upgrades
                'started_at' => now(),
                'expires_at' => $newExpiresAt,
                // null source_application_id explicitly as it was not applied for
            ]);

            return [
                'old_membership' => $currentMembership,
                'new_membership' => $newMembership
            ];
        });
    }

    /**
     * Consume (mark as upgrade_completed) the MembershipApplication draft used as
     * the payment vehicle during an upgrade, preventing reuse or confusion.
     *
     * @param MembershipApplication $draft
     * @return void
     */
    public function consumeUpgradeDraft(MembershipApplication $draft): void
    {
        $draft->update([
            'status' => 'upgrade_completed',
            'current_step' => 'upgrade_completed',
            'submitted_at' => $draft->submitted_at ?? now(),
        ]);
    }

    /**
     * Fallback creation of a fresh membership if somehow bypassing base validation natively.
     */
    protected function createNewActiveMembership(User $user, MembershipTier $tier): array
    {
        $durationDays = max(1, $tier->duration_days ?? 365);

        $newMembership = Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'approved_at' => now(), 
            'started_at' => now(),
            'expires_at' => now()->addDays($durationDays),
        ]);

        return [
            'old_membership' => null,
            'new_membership' => $newMembership
        ];
    }
}
