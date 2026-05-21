<?php

namespace App\Services\Membership;

use App\Models\Payment;
use App\Models\MembershipApplication;
use App\Support\Payments\PaymentPurpose;
use Illuminate\Support\Facades\Log;

class MembershipPaymentService
{
    protected MembershipUpgradeService $upgradeService;
    protected MembershipService $membershipService;

    public function __construct(
        MembershipUpgradeService $upgradeService,
        MembershipService $membershipService
    ) {
        $this->upgradeService = $upgradeService;
        $this->membershipService = $membershipService;
    }

    /**
     * Finalize the paid membership registration or upgrade.
     */
    public function finalizePaidMembership(Payment $payment)
    {
        if ($payment->payable_type !== MembershipApplication::class) {
            Log::warning("MembershipPaymentService: Payment is not for MembershipApplication", [
                'payment_id' => $payment->id,
                'payable_type' => $payment->payable_type,
            ]);
            return null;
        }

        /** @var MembershipApplication $draft */
        $draft = $payment->payable;
        if (!$draft) {
            Log::error("MembershipPaymentService: MembershipApplication not found for payment", [
                'payment_id' => $payment->id,
            ]);
            return null;
        }

        if ($payment->purpose === PaymentPurpose::MEMBERSHIP_UPGRADE) {
            // Guard: ensure we don't repeat the upgrade if it's already consumed
            if ($draft->status === 'upgrade_completed') {
                return null;
            }

            $user = $payment->user;
            $meta = $payment->meta ?? $payment->meta_json ?? [];
            $quote = $meta['upgrade_context'] ?? [];
            $targetTierId = $draft->selected_tier_id;

            if (!$user || !$targetTierId) {
                Log::error("MembershipPaymentService: Missing user or target tier ID for upgrade", [
                    'payment_id' => $payment->id,
                    'user_id' => $user?->id,
                    'target_tier_id' => $targetTierId,
                ]);
                return null;
            }

            // Perform the upgrade
            $upgradeData = $this->upgradeService->upgradeUserMembership($user, $targetTierId, $quote);

            // Consume the draft
            $this->upgradeService->consumeUpgradeDraft($draft);

            // Update application payment status
            $draft->update([
                'payment_status' => 'paid',
                'payment_meta_json' => array_merge($draft->payment_meta_json ?? [], [
                    'payment_id' => $payment->id,
                    'gateway_payment_id' => $payment->gateway_payment_id,
                ])
            ]);

            return $upgradeData;
        } else {
            // New registration application / renewal
            if ($draft->status === 'submitted') {
                return null;
            }

            // Update payment status on the application
            $draft->update([
                'payment_status' => 'paid',
                'payment_meta_json' => array_merge($draft->payment_meta_json ?? [], [
                    'payment_id' => $payment->id,
                    'gateway_payment_id' => $payment->gateway_payment_id,
                ])
            ]);

            // Submit the application
            return $this->membershipService->submitApplication($draft);
        }
    }

    /**
     * Mark the membership payment as failed.
     */
    public function markPaymentFailedMembership(Payment $payment, string $reason)
    {
        if ($payment->payable_type !== MembershipApplication::class) {
            return null;
        }

        /** @var MembershipApplication $draft */
        $draft = $payment->payable;
        if (!$draft) {
            return null;
        }

        $draft->update([
            'payment_status' => 'unpaid'
        ]);

        Log::info("MembershipPaymentService: Payment failed for membership application/upgrade", [
            'payment_id' => $payment->id,
            'application_id' => $draft->id,
            'reason' => $reason,
        ]);

        return $draft;
    }
}
