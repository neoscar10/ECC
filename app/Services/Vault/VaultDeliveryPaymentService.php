<?php

namespace App\Services\Vault;

use App\Models\Payment;
use App\Models\VaultRemovalRequest;
use Illuminate\Support\Facades\Log;

class VaultDeliveryPaymentService
{
    /**
     * Finalize paid vault removal request.
     */
    public function finalizePaidVaultDelivery(Payment $payment): ?VaultRemovalRequest
    {
        if ($payment->payable_type !== VaultRemovalRequest::class) {
            Log::warning("VaultDeliveryPaymentService: Payment is not for VaultRemovalRequest", [
                'payment_id' => $payment->id,
                'payable_type' => $payment->payable_type,
            ]);
            return null;
        }

        /** @var VaultRemovalRequest $request */
        $request = $payment->payable;
        if (!$request) {
            Log::error("VaultDeliveryPaymentService: VaultRemovalRequest not found for payment", [
                'payment_id' => $payment->id,
            ]);
            return null;
        }

        if ($request->payment_status === VaultRemovalRequest::PAYMENT_PAID) {
            return $request;
        }

        $request->update([
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'paid_at' => $payment->paid_at ?? now(),
            'payment_reference' => $payment->gateway_payment_id ?: ('PAY-GW-' . $payment->id),
            'status' => VaultRemovalRequest::STATUS_PENDING,
        ]);

        // Do NOT auto-initiate Shiprocket shipments on vault delivery payments; only transition status to pending (which displays as "Pending Review" to the admin)
        Log::info("VaultDeliveryPaymentService: Vault physical delivery payment finalized.", [
            'payment_id' => $payment->id,
            'request_id' => $request->id,
        ]);

        return $request;
    }

    /**
     * Handle failed payment for vault removal request.
     */
    public function markPaymentFailedVaultDelivery(Payment $payment, string $reason): ?VaultRemovalRequest
    {
        if ($payment->payable_type !== VaultRemovalRequest::class) {
            return null;
        }

        /** @var VaultRemovalRequest $request */
        $request = $payment->payable;
        if (!$request) {
            return null;
        }

        $request->update([
            'payment_status' => VaultRemovalRequest::PAYMENT_FAILED,
        ]);

        Log::info("VaultDeliveryPaymentService: Payment failed for vault removal.", [
            'payment_id' => $payment->id,
            'request_id' => $request->id,
            'reason' => $reason,
        ]);

        return $request;
    }
}
