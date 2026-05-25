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

    /**
     * Create a new PaymentManager instance.
     */
    public function __construct(PaymentLedgerService $ledger)
    {
        $this->ledger = $ledger;
    }

    /**
     * Resolve the requested gateway implementation.
     */
    public function getGateway(?string $gateway = null): PaymentGatewayInterface
    {
        $gatewayName = strtolower($gateway ?: config('payments.default_gateway', 'razorpay'));

        $supportedGateways = config('payments.supported_gateways', []);
        $gatewaysConfig = config('payments.gateways', []);

        if (!in_array($gatewayName, $supportedGateways) && !array_key_exists($gatewayName, $gatewaysConfig)) {
            throw new InvalidArgumentException("Payment gateway driver [{$gatewayName}] is not supported.");
        }

        $driverClass = config("payments.gateways.{$gatewayName}.driver");

        if (empty($driverClass)) {
            throw new RuntimeException(ucfirst($gatewayName) . " gateway is configured but has no driver class.");
        }

        if (!class_exists($driverClass)) {
            throw new RuntimeException("Payment gateway driver [{$driverClass}] does not exist.");
        }

        $driver = app($driverClass);

        if (!$driver instanceof PaymentGatewayInterface) {
            throw new RuntimeException("Payment gateway driver [{$driverClass}] must implement " . PaymentGatewayInterface::class);
        }

        return $driver;
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

            // Detect placeholder "not implemented" errors from Phase 2+ gateway shells
            // and assign a specific failure code for easier diagnosis and clean UX handling
            $isNotImplemented = str_contains($e->getMessage(), 'not implemented until Phase');
            $failureCode    = $isNotImplemented
                ? strtolower($gatewayName) . '_not_implemented'
                : 'gateway_error';
            $failureMessage = $e->getMessage();

            // Mark payment as failed inside ledger
            $payment = $this->ledger->markFailed($payment, $failureCode, $failureMessage);

            // Build failed DTO
            $result = PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: $failureCode,
                failureMessage: $failureMessage,
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
            // Extract any gateway-specific meta keys from the result's raw data
            // (e.g. Cashfree cf_order_id, payment_session_id) and merge them
            // into payment meta alongside the checkout payload.
            $extraMeta = [];
            if (!empty($result->raw['_meta_cf_order_id'])) {
                $extraMeta['cf_order_id'] = $result->raw['_meta_cf_order_id'];
            }
            if (!empty($result->raw['_meta_payment_session_id'])) {
                $extraMeta['payment_session_id'] = $result->raw['_meta_payment_session_id'];
            }

            $payment = $this->ledger->markPending($payment, $result->checkout);

            // Save additional gateway meta keys if present
            if (!empty($extraMeta)) {
                $payment->update([
                    'meta' => $this->ledger->mergeMeta($payment, $extraMeta),
                ]);
                $payment = $payment->fresh();
            }
        }

        return [
            'payment'  => $payment->fresh(),
            'result'   => $result->toArray(),
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
