<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\Gateways\RazorpayGateway;
use App\Services\Payments\DTO\PaymentVerificationData;
use App\Services\Payments\DTO\PaymentResult;
use App\Support\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

class PaymentManager
{
    protected PaymentLedgerService $ledger;
    protected RazorpayGateway $razorpayGateway;

    /**
     * Create a new PaymentManager instance.
     */
    public function __construct(PaymentLedgerService $ledger, RazorpayGateway $razorpayGateway)
    {
        $this->ledger = $ledger;
        $this->razorpayGateway = $razorpayGateway;
    }

    /**
     * Resolve the requested gateway implementation.
     */
    public function getGateway(?string $gateway = null): PaymentGatewayInterface
    {
        $gatewayName = $gateway ?: config('payments.default_gateway', 'razorpay');

        if ($gatewayName === 'razorpay') {
            return $this->razorpayGateway;
        }

        if ($gatewayName === 'cashfree') {
            throw new RuntimeException("Cashfree gateway is configured but not implemented yet.");
        }

        throw new InvalidArgumentException("Payment gateway driver [{$gatewayName}] is not supported.");
    }

    /**
     * Initiate a generic polymorphic payment sequence.
     */
    public function initiatePayment(
        ?Model $payable = null,
        float|string $amount,
        string $purpose,
        ?User $user = null,
        ?string $gateway = null,
        array $context = []
    ): array {
        // 1. Resolve gateway and defaults
        $gatewayName = $gateway ?: config('payments.default_gateway', 'razorpay');
        $currency = $context['currency'] ?? config('payments.default_currency', 'INR');

        // 2. Create internal Payment row
        $payment = $this->ledger->createPayment([
            'user_id' => $user ? $user->id : null,
            'payable_type' => $payable ? get_class($payable) : null,
            'payable_id' => $payable ? $payable->getKey() : null,
            'purpose' => $purpose,
            'gateway' => $gatewayName,
            'amount' => $amount,
            'currency' => $currency,
            'status' => PaymentStatus::INITIATED,
            'meta' => $context['meta'] ?? []
        ]);

        // 3. Delegate order creation to gateway driver
        $driver = $this->getGateway($gatewayName);

        try {
            $result = $driver->createOrder($payment, $context);
        } catch (\RuntimeException $e) {
            // Rethrow if configuration is broken (missing credentials)
            if (str_contains($e->getMessage(), 'Missing key_id') || 
                str_contains($e->getMessage(), 'not configured')) {
                throw $e;
            }

            // Mark payment as failed inside ledger
            $payment = $this->ledger->markFailed($payment, 'gateway_error', $e->getMessage());

            // Build failed DTO
            $result = PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'gateway_error',
                failureMessage: $e->getMessage(),
                gateway: $gatewayName,
            );

            return [
                'payment' => $payment->fresh(),
                'result' => $result->toArray(),
                'checkout' => null,
            ];
        }

        // 4. Save gateway_order_id and transition status to pending
        if ($result->gatewayOrderId !== null) {
            $payment->update([
                'gateway_order_id' => $result->gatewayOrderId
            ]);
        }

        if ($result->success || $result->status === PaymentStatus::PENDING) {
            $payment = $this->ledger->markPending($payment, $result->checkout);
        }

        return [
            'payment' => $payment->fresh(),
            'result' => $result->toArray(),
            'checkout' => $result->checkout,
        ];
    }

    /**
     * Verify payment status using driver-specific logic.
     */
    public function verifyPayment(Payment $payment, PaymentVerificationData|array $data): array
    {
        // 1. Normalize input array into PaymentVerificationData if needed
        if (is_array($data)) {
            $data = PaymentVerificationData::fromArray($data);
        }

        // 2. Resolve gateway from payment or verification data
        $gatewayName = $payment->gateway ?: $data->gateway ?: config('payments.default_gateway', 'razorpay');
        $driver = $this->getGateway($gatewayName);

        // 3. Call driver verification
        $result = $driver->verifyPayment($payment, $data);

        // 4. Transition states inside database ledger
        if ($result->success && $result->status === PaymentStatus::PAID) {
            $payment = $this->ledger->markPaid($payment, $result->gatewayPaymentId, $result->raw);
        } elseif ($result->status === PaymentStatus::FAILED) {
            $payment = $this->ledger->markFailed($payment, $result->failureCode, $result->failureMessage, $result->raw);
        }

        return [
            'payment' => $payment,
            'result' => $result,
        ];
    }

    /**
     * Helper to record gateway events directly.
     */
    public function recordGatewayEvent(array $data): \App\Models\PaymentEvent
    {
        return $this->ledger->recordEvent($data);
    }
}
