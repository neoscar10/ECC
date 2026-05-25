<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\DTO\PaymentResult;
use App\Services\Payments\DTO\PaymentVerificationData;
use App\Support\Payments\PaymentStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RazorpayGateway implements PaymentGatewayInterface
{
    protected string $keyId;
    protected string $keySecret;
    protected ?string $webhookSecret;
    protected string $mode;

    /**
     * Create a new Razorpay adapter instance.
     */
    public function __construct()
    {
        $config = config('payments.gateways.razorpay', []);
        
        $this->keyId = $config['key_id'] ?? '';
        $this->keySecret = $config['key_secret'] ?? '';
        $this->webhookSecret = $config['webhook_secret'] ?? null;
        $this->mode = $config['mode'] ?? 'test';
    }

    /**
     * Get the payment payment gateway identifier name.
     */
    public function gatewayName(): string
    {
        return 'razorpay';
    }

    /**
     * Create an order on the gateway.
     */
    public function createOrder(Payment $payment, array $context = []): PaymentResult
    {
        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new RuntimeException("Razorpay integration error: Missing key_id or key_secret.");
        }

        // Amount in paise and input validation
        $amountInPaise = $this->convertToPaise($payment);

        $payload = [
            'amount' => $amountInPaise,
            'currency' => $payment->currency ?: 'INR',
            'receipt' => 'ecc_payment_' . $payment->id,
            'notes' => [
                'internal_payment_id' => (string) $payment->id,
                'purpose' => (string) $payment->purpose,
                'payable_type' => (string) $payment->payable_type,
                'payable_id' => (string) $payment->payable_id,
                'user_id' => (string) $payment->user_id,
            ],
        ];

        // Call Razorpay Orders API
        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->post('https://api.razorpay.com/v1/orders', $payload);

        if (!$response->successful()) {
            $errorMsg = $response->json('error.description') ?: 'Unknown Razorpay API Error';
            Log::error("Razorpay order creation failed: " . $errorMsg, [
                'payment_id' => $payment->id,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            throw new RuntimeException("Razorpay order creation failed: " . $errorMsg);
        }

        $responseData = $response->json();
        $orderId = $responseData['id'];

        // Return a pending PaymentResult with standardized checkout data
        return PaymentResult::pending(
            status: PaymentStatus::PENDING,
            gateway: 'razorpay',
            gatewayOrderId: $orderId,
            checkout: [
                'gateway' => 'razorpay',
                'key' => $this->keyId,
                'amount' => $amountInPaise,
                'display_amount' => (float) $payment->amount,
                'currency' => $payment->currency ?: 'INR',
                'order_id' => $orderId,
                'internal_payment_id' => $payment->id,
                'name' => 'Executive Cricket Club',
                'description' => $context['description'] ?? $this->buildDefaultDescription($payment),
                'prefill' => [
                    'name' => $payment->user ? $payment->user->name : null,
                    'email' => $payment->user ? $payment->user->email : null,
                    'contact' => $payment->user ? ($payment->user->phone ?? $payment->user->mobile) : null,
                ],
                'notes' => [
                    'internal_payment_id' => $payment->id,
                    'payment_id' => $payment->id, // For backward-compatibility with tests
                    'purpose' => $payment->purpose,
                ],
            ],
            raw: $responseData
        );
    }

    /**
     * Convert INR amount to paise and perform gateway input validations.
     *
     * @param Payment $payment
     * @return int
     * @throws \InvalidArgumentException
     */
    private function convertToPaise(Payment $payment): int
    {
        $amount = (float) $payment->amount;
        $currency = $payment->currency ?: 'INR';

        if ($amount <= 0) {
            throw new \InvalidArgumentException("Payment amount must be greater than zero.");
        }

        if (strtoupper($currency) !== 'INR') {
            throw new \InvalidArgumentException("Razorpay gateway only supports payments in INR.");
        }

        return (int) round($amount * 100);
    }

    /**
     * Build a default readable description for the checkout window.
     */
    private function buildDefaultDescription(Payment $payment): string
    {
        $purpose = $payment->purpose ? str_replace('_', ' ', $payment->purpose) : 'payment';
        return 'Executive Cricket Club - ' . ucwords($purpose);
    }

    /**
     * Verify payment authenticity using signature/payload.
     */
    public function verifyPayment(Payment $payment, PaymentVerificationData $data): PaymentResult
    {
        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new RuntimeException("Razorpay integration error: Missing key_id or key_secret.");
        }

        $orderId = $data->gatewayOrderId ?: $payment->gateway_order_id;
        $paymentId = $data->gatewayPaymentId;
        $signature = $data->gatewaySignature;

        if (empty($orderId) || empty($paymentId) || empty($signature)) {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'missing_attributes',
                failureMessage: 'Missing order_id, payment_id, or signature for verification.',
                gateway: 'razorpay',
                gatewayOrderId: $orderId,
                gatewayPaymentId: $paymentId,
                raw: $data->payload
            );
        }

        // Calculate signature: SHA256 HMAC of "order_id|payment_id" using keySecret
        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);

        if (!hash_equals($expectedSignature, $signature)) {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'invalid_signature',
                failureMessage: 'Payment verification failed: Signature mismatch.',
                gateway: 'razorpay',
                gatewayOrderId: $orderId,
                gatewayPaymentId: $paymentId,
                raw: array_merge($data->payload, ['expected_signature' => $expectedSignature])
            );
        }

        return PaymentResult::success(
            status: PaymentStatus::PAID,
            gateway: 'razorpay',
            gatewayOrderId: $orderId,
            gatewayPaymentId: $paymentId,
            raw: $data->payload
        );
    }

    /**
     * Fetch payment status and details from the gateway.
     */
    public function fetchPayment(string $gatewayPaymentId): PaymentResult
    {
        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new RuntimeException("Razorpay integration error: Missing key_id or key_secret.");
        }

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->get("https://api.razorpay.com/v1/payments/{$gatewayPaymentId}");

        if (!$response->successful()) {
            $errorMsg = $response->json('error.description') ?: 'Unknown Razorpay payment fetch API error';
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'fetch_failed',
                failureMessage: $errorMsg,
                gateway: 'razorpay',
                gatewayPaymentId: $gatewayPaymentId,
                raw: $response->json() ?: []
            );
        }

        $paymentData = $response->json();
        $status = $paymentData['status'] ?? 'unknown';

        if (in_array($status, ['captured', 'authorized'])) {
            return PaymentResult::success(
                status: PaymentStatus::PAID,
                gateway: 'razorpay',
                gatewayOrderId: $paymentData['order_id'] ?? null,
                gatewayPaymentId: $gatewayPaymentId,
                raw: $paymentData
            );
        }

        if ($status === 'failed') {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: $paymentData['error_code'] ?? 'payment_failed',
                failureMessage: $paymentData['error_description'] ?? 'Payment failed on gateway.',
                gateway: 'razorpay',
                gatewayOrderId: $paymentData['order_id'] ?? null,
                gatewayPaymentId: $gatewayPaymentId,
                raw: $paymentData
            );
        }

        // Return pending if it is still processing/refunded etc.
        return PaymentResult::pending(
            status: PaymentStatus::PENDING,
            gateway: 'razorpay',
            gatewayOrderId: $paymentData['order_id'] ?? null,
            raw: $paymentData
        );
    }

    /**
     * Handle incoming gateway webhook logic.
     */
    public function handleWebhook(array $payload, ?string $signature = null, ?string $rawBody = null): PaymentResult
    {
        if (empty($this->webhookSecret)) {
            throw new RuntimeException("Razorpay webhook integration error: Missing webhook_secret.");
        }

        if (empty($signature) || empty($rawBody)) {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'missing_webhook_signature',
                failureMessage: 'Missing signature or raw body for webhook verification.',
                gateway: 'razorpay',
                raw: $payload
            );
        }

        // Verify webhook signature
        $expectedSignature = hash_hmac('sha256', $rawBody, $this->webhookSecret);
        if (!hash_equals($expectedSignature, $signature)) {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'invalid_webhook_signature',
                failureMessage: 'Webhook signature verification failed.',
                gateway: 'razorpay',
                raw: array_merge($payload, ['expected_signature' => $expectedSignature])
            );
        }

        // Parse event type
        $event = $payload['event'] ?? 'unknown';
        $entity = $payload['payload']['payment']['entity'] ?? [];
        $gatewayPaymentId = $entity['id'] ?? null;
        $gatewayOrderId = $entity['order_id'] ?? null;

        if (in_array($event, ['payment.captured', 'order.paid'])) {
            return PaymentResult::success(
                status: PaymentStatus::PAID,
                gateway: 'razorpay',
                gatewayOrderId: $gatewayOrderId,
                gatewayPaymentId: $gatewayPaymentId,
                raw: $payload
            );
        }

        if ($event === 'payment.failed') {
            $errorMsg = $entity['error_description'] ?? 'Payment failed webhook event';
            $errorCode = $entity['error_code'] ?? 'webhook_failure';
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: $errorCode,
                failureMessage: $errorMsg,
                gateway: 'razorpay',
                gatewayOrderId: $gatewayOrderId,
                gatewayPaymentId: $gatewayPaymentId,
                raw: $payload
            );
        }

        return PaymentResult::pending(
            status: PaymentStatus::PENDING,
            gateway: 'razorpay',
            gatewayOrderId: $gatewayOrderId,
            raw: $payload
        );
    }

    /**
     * Extract normalized identifiers from the gateway's payload.
     *
     * @param array $payload
     * @return array
     */
    public function extractIdentifiers(array $payload): array
    {
        $identifiers = [
            'internal_payment_id' => null,
            'gateway_order_id' => null,
            'gateway_payment_id' => null,
            'gateway_event_id' => null,
            'event_type' => null,
        ];

        // A. Top-level event
        $identifiers['event_type'] = data_get($payload, 'event');
        $identifiers['gateway_event_id'] = data_get($payload, 'id');

        // B. Payment entity path
        $paymentEntity = data_get($payload, 'payload.payment.entity');
        if ($paymentEntity) {
            $identifiers['gateway_payment_id'] = data_get($paymentEntity, 'id');
            $identifiers['gateway_order_id'] = data_get($paymentEntity, 'order_id');

            // Try to extract internal_payment_id from notes
            $internalId = data_get($paymentEntity, 'notes.internal_payment_id')
                ?? data_get($paymentEntity, 'notes.payment_id')
                ?? data_get($paymentEntity, 'notes.ecc_payment_id');

            // Try to extract from receipt
            if (empty($internalId)) {
                $receipt = data_get($paymentEntity, 'receipt');
                if ($receipt && preg_match('/^ecc_payment_(\d+)$/', $receipt, $matches)) {
                    $internalId = $matches[1];
                }
            }

            if ($internalId) {
                $identifiers['internal_payment_id'] = $internalId;
            }
        }

        // C. Order entity path
        $orderEntity = data_get($payload, 'payload.order.entity');
        if ($orderEntity) {
            if (empty($identifiers['gateway_order_id'])) {
                $identifiers['gateway_order_id'] = data_get($orderEntity, 'id');
            }

            if (empty($identifiers['internal_payment_id'])) {
                $internalId = data_get($orderEntity, 'notes.internal_payment_id');
                if (empty($internalId)) {
                    $receipt = data_get($orderEntity, 'receipt');
                    if ($receipt && preg_match('/^ecc_payment_(\d+)$/', $receipt, $matches)) {
                        $internalId = $matches[1];
                    }
                }
                if ($internalId) {
                    $identifiers['internal_payment_id'] = $internalId;
                }
            }
        }

        // D. Fallback (e.g. from tests or manual simulation)
        if (empty($identifiers['internal_payment_id'])) {
            $identifiers['internal_payment_id'] = data_get($payload, 'internal_payment_id');
        }
        if (empty($identifiers['gateway_order_id'])) {
            $identifiers['gateway_order_id'] = data_get($payload, 'gateway_order_id');
        }
        if (empty($identifiers['gateway_payment_id'])) {
            $identifiers['gateway_payment_id'] = data_get($payload, 'gateway_payment_id');
        }
        if (empty($identifiers['gateway_event_id'])) {
            $identifiers['gateway_event_id'] = data_get($payload, 'gateway_event_id');
        }
        if (empty($identifiers['event_type'])) {
            $identifiers['event_type'] = data_get($payload, 'event_type');
        }

        return $identifiers;
    }
}

