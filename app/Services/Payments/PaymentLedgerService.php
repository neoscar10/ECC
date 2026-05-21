<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Support\Facades\DB;

class PaymentLedgerService
{
    /**
     * Create a new payment record inside the ledger.
     */
    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $data['currency'] = $data['currency'] ?? config('payments.default_currency', 'INR');
            $data['status'] = $data['status'] ?? \App\Support\Payments\PaymentStatus::INITIATED;
            $data['gateway'] = $data['gateway'] ?? config('payments.default_gateway', 'razorpay');

            if (isset($data['amount'])) {
                // Ensure decimal normalization
                $data['amount'] = number_format((float) $data['amount'], 2, '.', '');
            }

            return Payment::create($data);
        });
    }

    /**
     * Record a new event audit trail entry.
     */
    public function recordEvent(array $data): PaymentEvent
    {
        return DB::transaction(function () use ($data) {
            return PaymentEvent::create([
                'payment_id' => $data['payment_id'] ?? null,
                'gateway' => $data['gateway'],
                'event_type' => $data['event_type'],
                'gateway_event_id' => $data['gateway_event_id'] ?? null,
                'payload' => $data['payload'] ?? [],
                'signature_valid' => $data['signature_valid'] ?? false,
                'processed_at' => $data['processed_at'] ?? now(),
            ]);
        });
    }

    /**
     * Atomically transition a payment status to pending.
     *
     * @param Payment $payment
     * @param array   $meta  Checkout payload from the gateway driver. Stored under the
     *                       'checkout' key so that RazorpayPaymentController can read
     *                       $payment->meta['checkout'] reliably.
     */
    public function markPending(Payment $payment, array $meta = []): Payment
    {
        return DB::transaction(function () use ($payment, $meta) {
            // Wrap gateway checkout data under 'checkout' key so controllers can
            // reliably access it via $payment->meta['checkout'].
            $wrappedMeta = !empty($meta) ? ['checkout' => $meta] : [];

            $payment->update([
                'status' => \App\Support\Payments\PaymentStatus::PENDING,
                'meta' => $this->mergeMeta($payment, $wrappedMeta),
            ]);
            return $payment->fresh();
        });
    }

    /**
     * Atomically transition a payment status to paid.
     */
    public function markPaid(Payment $payment, ?string $gatewayPaymentId = null, array $meta = []): Payment
    {
        if ($payment->isPaid()) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $gatewayPaymentId, $meta) {
            $updateData = [
                'status' => \App\Support\Payments\PaymentStatus::PAID,
                'paid_at' => $payment->paid_at ?? now(),
                'meta' => $this->mergeMeta($payment, $meta),
            ];

            if ($gatewayPaymentId !== null) {
                $updateData['gateway_payment_id'] = $gatewayPaymentId;
            }

            $payment->update($updateData);

            return $payment->fresh();
        });
    }

    /**
     * Atomically transition a payment status to failed.
     */
    public function markFailed(Payment $payment, ?string $failureCode = null, ?string $failureMessage = null, array $meta = []): Payment
    {
        return DB::transaction(function () use ($payment, $failureCode, $failureMessage, $meta) {
            $updateData = [
                'status' => \App\Support\Payments\PaymentStatus::FAILED,
                'failed_at' => $payment->failed_at ?? now(),
                'failure_code' => $failureCode,
                'failure_message' => $failureMessage,
                'meta' => $this->mergeMeta($payment, $meta),
            ];

            $payment->update($updateData);

            return $payment->fresh();
        });
    }

    /**
     * Safely merge existing meta with new meta.
     */
    public function mergeMeta(Payment $payment, array $meta): array
    {
        $existing = $payment->meta ?? [];
        if (!is_array($existing)) {
            $existing = [];
        }
        return array_merge($existing, $meta);
    }
}
