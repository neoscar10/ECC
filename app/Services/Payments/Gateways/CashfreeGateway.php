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

/**
 * CashfreeGateway — Phase 3: Order / Payment Session Creation
 *
 * Phase 3 implements CashfreeGateway::createOrder() to:
 *  - Call the Cashfree Create Order API (POST /pg/orders)
 *  - Return a payment_session_id for frontend/mobile SDK checkout
 *  - Store gateway identifiers in the PaymentResult for persistence by PaymentManager
 *
 * Phase 4: verifyPayment(), fetchPayment() (Cashfree payment verification and fetch)
 * Phase 5: handleWebhook() (Cashfree webhook event processing)
 *
 * SECURITY: client_secret is NEVER included in PaymentResult, checkout data,
 * API responses, or logs.
 */
class CashfreeGateway implements PaymentGatewayInterface
{
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $webhookSecret;
    protected string $mode;
    protected string $apiVersion;
    protected ?string $returnUrl;
    protected ?string $notifyUrl;

    /**
     * Create a new Cashfree gateway adapter instance.
     *
     * Loads all configuration from config/payments.php under the 'cashfree' key.
     */
    public function __construct()
    {
        $config = config('payments.gateways.cashfree', []);

        $this->clientId      = $config['client_id'] ?? null;
        $this->clientSecret  = $config['client_secret'] ?? null;
        $this->webhookSecret = $config['webhook_secret'] ?? null;
        $this->mode          = $config['mode'] ?? 'sandbox';
        $this->apiVersion    = $config['api_version'] ?? '2023-08-01';
        $this->returnUrl     = $config['return_url'] ?? null;
        $this->notifyUrl     = $config['notify_url'] ?? null;
    }

    // =========================================================================
    // Interface: Identity
    // =========================================================================

    /**
     * Get the gateway identifier name.
     *
     * Must match config key 'cashfree' and PaymentGateway::CASHFREE constant.
     */
    public function gatewayName(): string
    {
        return 'cashfree';
    }

    // =========================================================================
    // Interface: Order / Session Creation (Phase 3)
    // =========================================================================

    /**
     * Create a Cashfree order and payment session.
     *
     * Calls POST /pg/orders on the Cashfree API and returns a PaymentResult
     * containing the payment_session_id needed by the frontend/mobile SDK.
     *
     * Mapping to internal Payment fields:
     *   - gateway_order_id        ← Cashfree order_id (e.g. "ECCPAY{$payment->id}")
     *   - meta.cf_order_id        ← Cashfree numeric cf_order_id
     *   - meta.payment_session_id ← Cashfree payment_session_id
     *   - meta.checkout           ← full checkout payload (set by PaymentManager via markPending)
     *
     * Payment status remains PENDING after this call — never PAID.
     *
     * @throws RuntimeException if credentials are missing (hard configuration error).
     */
    public function createOrder(Payment $payment, array $context = []): PaymentResult
    {
        // 1. Validate credentials are present
        $this->ensureConfigured();

        // 2. Validate payment input
        $amount   = (float) $payment->amount;
        $currency = strtoupper($payment->currency ?: 'INR');

        if ($amount <= 0) {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'invalid_amount',
                failureMessage: 'Payment amount must be greater than zero.',
                gateway: 'cashfree',
                raw: ['amount' => $amount]
            );
        }

        if ($currency !== 'INR') {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'unsupported_currency',
                failureMessage: "Cashfree gateway only supports INR payments. Received: {$currency}.",
                gateway: 'cashfree',
                raw: ['currency' => $currency]
            );
        }

        // 3. Build Cashfree order_id — unique, stable identifier for this payment attempt
        //    Must use only alphanumeric characters and underscores (Cashfree requirement)
        $cashfreeOrderId = 'ECCPAY' . $payment->id;

        // 4. Build customer details safely (required by Cashfree)
        $customerDetails = $this->buildCustomerDetails($payment, $context);
        if ($customerDetails === null) {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'missing_customer_details',
                failureMessage: 'Customer email and phone are required for Cashfree payment.',
                gateway: 'cashfree',
                raw: ['payment_id' => $payment->id]
            );
        }

        // 5. Build return / notify URLs
        $returnUrl = $this->resolveReturnUrl($payment);
        $notifyUrl = $this->resolveNotifyUrl();

        // 6. Build request payload
        $payload = [
            'order_id'       => $cashfreeOrderId,
            'order_amount'   => round($amount, 2),   // Cashfree uses decimal INR, not paise
            'order_currency' => $currency,
            'customer_details' => $customerDetails,
            'order_note'     => $context['description'] ?? $this->buildDefaultDescription($payment),
            'order_tags'     => [
                'internal_payment_id' => (string) $payment->id,
                'purpose'             => (string) ($payment->purpose ?? ''),
                'payable_type'        => (string) ($payment->payable_type ? class_basename($payment->payable_type) : ''),
                'payable_id'          => (string) ($payment->payable_id ?? ''),
                'user_id'             => (string) ($payment->user_id ?? ''),
            ],
            'order_meta'     => array_filter([
                'return_url' => $returnUrl,
                'notify_url' => $notifyUrl,
            ]),
        ];

        Log::info('CashfreeGateway: Order creation started.', [
            'payment_id'       => $payment->id,
            'payable_type'     => $payment->payable_type,
            'payable_id'       => $payment->payable_id,
            'purpose'          => $payment->purpose,
            'user_id'          => $payment->user_id,
            'amount'           => $amount,
            'currency'         => $currency,
            'cashfree_order_id' => $cashfreeOrderId,
            'mode'             => $this->mode,
        ]);

        // 7. Call Cashfree Create Order API
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->retry(
                    times: 2,
                    sleepMilliseconds: 500,
                    when: function ($exception) {
                        // Only retry on network/connection failures — never on 4xx/5xx API responses.
                        return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                    },
                    throw: false  // Do NOT throw on exhausted retries; return response so we can inspect it
                )
                ->post($this->baseUrl() . '/orders', $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('CashfreeGateway: Connection timeout/failure.', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'cashfree_connection_failed',
                failureMessage: 'Could not connect to Cashfree API: ' . $e->getMessage(),
                gateway: 'cashfree',
                raw: ['error' => $e->getMessage()]
            );
        }

        // 8. Handle API failure responses
        if (!$response->successful()) {
            $errorBody = $response->json() ?? [];
            $errorCode    = $errorBody['code'] ?? $errorBody['type'] ?? 'cashfree_order_creation_failed';
            $errorMessage = $errorBody['message'] ?? 'Cashfree order creation failed.';

            Log::error('CashfreeGateway: Order creation failed.', [
                'payment_id'    => $payment->id,
                'http_status'   => $response->status(),
                'error_code'    => $errorCode,
                'error_message' => $errorMessage,
            ]);

            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: $errorCode,
                failureMessage: $errorMessage,
                gateway: 'cashfree',
                raw: ['http_status' => $response->status(), 'response' => $errorBody]
            );
        }

        // 9. Parse success response
        $responseData = $response->json();

        // Extract Cashfree identifiers
        $cfOrderId        = $responseData['cf_order_id'] ?? null;
        $orderId          = $responseData['order_id'] ?? $cashfreeOrderId;
        $paymentSessionId = $responseData['payment_session_id'] ?? null;
        $orderStatus      = $responseData['order_status'] ?? 'ACTIVE';

        // 10. Validate payment_session_id is present (required for SDK checkout)
        if (empty($paymentSessionId)) {
            Log::error('CashfreeGateway: Missing payment_session_id in response.', [
                'payment_id'    => $payment->id,
                'cf_order_id'   => $cfOrderId,
                'order_id'      => $orderId,
                'response_keys' => array_keys($responseData),
            ]);

            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'missing_payment_session_id',
                failureMessage: 'Cashfree did not return payment_session_id.',
                gateway: 'cashfree',
                raw: $responseData
            );
        }

        Log::info('CashfreeGateway: Order creation succeeded.', [
            'payment_id'        => $payment->id,
            'gateway_order_id'  => $orderId,
            'cf_order_id'       => $cfOrderId,
            'order_status'      => $orderStatus,
        ]);

        // 11. Build the checkout payload (safe — no secrets)
        $checkout = [
            'gateway'             => 'cashfree',
            'order_id'            => $orderId,
            'cf_order_id'         => $cfOrderId,
            'payment_session_id'  => $paymentSessionId,
            'amount'              => $amount,
            'currency'            => $currency,
            'display_amount'      => $amount,
            'mode'                => $this->mode,
            'environment'         => $this->isLiveMode() ? 'production' : 'sandbox',
            'return_url'          => $returnUrl,
            'notify_url'          => $notifyUrl,
            'name'                => 'Executive Cricket Club',
            'description'         => $context['description'] ?? $this->buildDefaultDescription($payment),
            'customer'            => [
                'id'    => $customerDetails['customer_id'],
                'name'  => $customerDetails['customer_name'],
                'email' => $customerDetails['customer_email'],
                'phone' => $customerDetails['customer_phone'],
            ],
            // Cashfree-specific fields for Phase 5 webhook matching
            'cf_order_id_raw'     => $cfOrderId,
            'order_status'        => $orderStatus,
        ];

        // 12. Build raw (includes full response for audit, but no secrets)
        $safeRaw = array_merge($responseData, [
            'cf_order_id'         => $cfOrderId,
            'payment_session_id'  => $paymentSessionId,
            // These are also stored in meta for direct access during Phase 4/5
            '_meta_cf_order_id'          => $cfOrderId,
            '_meta_payment_session_id'   => $paymentSessionId,
        ]);

        return PaymentResult::pending(
            status: PaymentStatus::PENDING,
            gateway: 'cashfree',
            gatewayOrderId: $orderId,
            checkout: $checkout,
            raw: $safeRaw
        );
    }

    // =========================================================================
    // Interface: Verification (Phase 4)
    // =========================================================================

    /**
     * Verify a Cashfree payment.
     *
     * Validates payment status directly with Cashfree's GET order API.
     */
    public function verifyPayment(Payment $payment, PaymentVerificationData $data): PaymentResult
    {
        // 1. Ensure configured
        $this->ensureConfigured();

        // 2. Validate payment input
        if ($payment->gateway !== 'cashfree') {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'invalid_gateway',
                failureMessage: 'Invalid payment gateway for this verification endpoint.',
                gateway: 'cashfree',
                gatewayOrderId: $payment->gateway_order_id
            );
        }

        if (empty($payment->gateway_order_id)) {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'missing_gateway_order_id',
                failureMessage: 'Gateway order ID is missing.',
                gateway: 'cashfree'
            );
        }

        // If already paid, return success idempotently
        if ($payment->status === PaymentStatus::PAID) {
            return PaymentResult::success(
                status: PaymentStatus::PAID,
                gateway: 'cashfree',
                gatewayOrderId: $payment->gateway_order_id,
                gatewayPaymentId: $payment->gateway_payment_id,
                raw: $payment->meta ?? []
            );
        }

        if ((float) $payment->amount <= 0) {
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'invalid_amount',
                failureMessage: 'Payment amount must be greater than zero.',
                gateway: 'cashfree',
                gatewayOrderId: $payment->gateway_order_id
            );
        }

        // 3. Determine and trust Cashfree order id
        $trustedOrderId = $payment->gateway_order_id;
        $providedOrderId = $data->gatewayOrderId;

        if (!empty($providedOrderId) && $providedOrderId !== $trustedOrderId) {
            Log::warning('CashfreeGateway: Order ID mismatch.', [
                'payment_id' => $payment->id,
                'trusted_order_id' => $trustedOrderId,
                'provided_order_id' => $providedOrderId,
            ]);
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'gateway_order_mismatch',
                failureMessage: 'Cashfree order ID mismatch.',
                gateway: 'cashfree',
                gatewayOrderId: $trustedOrderId
            );
        }

        Log::info('CashfreeGateway: Verifying payment.', [
            'payment_id' => $payment->id,
            'gateway_order_id' => $trustedOrderId,
        ]);

        try {
            $response = $this->fetchOrder($trustedOrderId);
        } catch (\Exception $e) {
            Log::error('CashfreeGateway: Connection failed during verification.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return PaymentResult::failed(
                status: PaymentStatus::PENDING, // keep pending if API is temporarily unavailable
                failureCode: 'cashfree_verification_unavailable',
                failureMessage: 'Could not connect to Cashfree API during verification: ' . $e->getMessage(),
                gateway: 'cashfree',
                gatewayOrderId: $trustedOrderId
            );
        }

        // 5. Handle HTTP status failures
        if (!$response->successful()) {
            $errorBody = $response->json() ?? [];
            $status = $response->status();
            $errorCode = $errorBody['code'] ?? $errorBody['type'] ?? 'unknown_error';
            $errorMessage = $errorBody['message'] ?? 'Failed to retrieve order details.';

            Log::error('CashfreeGateway: Verification fetch failed.', [
                'payment_id' => $payment->id,
                'http_status' => $status,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            if ($status === 401 || $status === 403) {
                return PaymentResult::failed(
                    status: PaymentStatus::FAILED,
                    failureCode: 'gateway_auth_error',
                    failureMessage: 'Unable to verify payment with gateway (authentication failure).',
                    gateway: 'cashfree',
                    gatewayOrderId: $trustedOrderId,
                    raw: $errorBody
                );
            }

            if ($status === 404) {
                return PaymentResult::failed(
                    status: PaymentStatus::FAILED,
                    failureCode: 'order_not_found',
                    failureMessage: 'Payment order was not found.',
                    gateway: 'cashfree',
                    gatewayOrderId: $trustedOrderId,
                    raw: $errorBody
                );
            }

            // Treat other status codes (e.g. 500) as temporary failure (keep pending)
            return PaymentResult::failed(
                status: PaymentStatus::PENDING,
                failureCode: 'cashfree_verification_unavailable',
                failureMessage: 'Cashfree payment verification is temporarily unavailable.',
                gateway: 'cashfree',
                gatewayOrderId: $trustedOrderId,
                raw: $errorBody
            );
        }

        // 6. Parse success response
        $responseData = $response->json();
        $cfOrderId = $responseData['cf_order_id'] ?? null;
        $orderId = $responseData['order_id'] ?? $trustedOrderId;
        $orderStatus = $responseData['order_status'] ?? 'PENDING';
        $orderAmount = $responseData['order_amount'] ?? 0;
        $orderCurrency = $responseData['order_currency'] ?? 'INR';

        // 7. Validate amount and currency (safe decimal comparison)
        $expectedAmount = round((float) $payment->amount, 2);
        $actualAmount = round((float) $orderAmount, 2);

        if ($actualAmount !== $expectedAmount || strtoupper($orderCurrency) !== strtoupper($payment->currency)) {
            Log::error('CashfreeGateway: Amount or currency mismatch.', [
                'payment_id' => $payment->id,
                'expected_amount' => $expectedAmount,
                'actual_amount' => $actualAmount,
                'expected_currency' => $payment->currency,
                'actual_currency' => $orderCurrency,
            ]);
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'amount_or_currency_mismatch',
                failureMessage: 'Payment amount verification failed.',
                gateway: 'cashfree',
                gatewayOrderId: $trustedOrderId,
                raw: $responseData
            );
        }

        // 8. Mapping Cashfree order_status
        if ($orderStatus === 'PAID') {
            // Determine gatewayPaymentId
            // - use latest payment transaction id if available
            // - else cf_order_id
            // - else order_id
            $gatewayPaymentId = data_get($responseData, 'payments.0.cf_payment_id') 
                ?? data_get($responseData, 'cf_payment_id') 
                ?? $cfOrderId 
                ?? $orderId;

            Log::info('CashfreeGateway: Verification succeeded.', [
                'payment_id' => $payment->id,
                'gateway_order_id' => $orderId,
                'gateway_payment_id' => $gatewayPaymentId,
            ]);

            // Save some details in payment meta
            $rawMeta = array_merge($responseData, [
                'cf_order_id' => $cfOrderId,
                'cashfree_payment_details' => $responseData,
            ]);

            return PaymentResult::success(
                status: PaymentStatus::PAID,
                gateway: 'cashfree',
                gatewayOrderId: $orderId,
                gatewayPaymentId: $gatewayPaymentId,
                raw: $rawMeta
            );
        }

        if (in_array($orderStatus, ['ACTIVE', 'PENDING'], true)) {
            Log::info('CashfreeGateway: Payment is pending.', [
                'payment_id' => $payment->id,
                'order_status' => $orderStatus,
            ]);

            return PaymentResult::pending(
                status: PaymentStatus::PENDING,
                gateway: 'cashfree',
                gatewayOrderId: $orderId,
                raw: $responseData
            );
        }

        if (in_array($orderStatus, ['EXPIRED', 'TERMINATED', 'CANCELLED', 'FAILED'], true)) {
            Log::warning('CashfreeGateway: Payment was not completed.', [
                'payment_id' => $payment->id,
                'order_status' => $orderStatus,
            ]);

            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'cashfree_payment_not_completed',
                failureMessage: 'Payment was not completed.',
                gateway: 'cashfree',
                gatewayOrderId: $orderId,
                raw: $responseData
            );
        }

        // Unknown status - return pending conservatively
        Log::warning('CashfreeGateway: Unknown order status received.', [
            'payment_id' => $payment->id,
            'order_status' => $orderStatus,
        ]);

        return PaymentResult::failed(
            status: PaymentStatus::PENDING,
            failureCode: 'unknown_status',
            failureMessage: 'Cashfree payment status is not final yet.',
            gateway: 'cashfree',
            gatewayOrderId: $orderId,
            raw: $responseData
        );
    }

    // =========================================================================
    // Interface: Fetch (Phase 4)
    // =========================================================================

    /**
     * Fetch payment/order details from Cashfree.
     *
     * Calls GET /pg/orders/{order_id} to retrieve order/payment status.
     */
    public function fetchPayment(string $gatewayPaymentId): PaymentResult
    {
        $this->ensureConfigured();

        Log::info('CashfreeGateway: Fetching payment details.', [
            'gateway_payment_id' => $gatewayPaymentId,
        ]);

        try {
            $response = $this->fetchOrder($gatewayPaymentId);
        } catch (\Exception $e) {
            Log::error('CashfreeGateway: Connection failed during fetch.', [
                'gateway_payment_id' => $gatewayPaymentId,
                'error' => $e->getMessage(),
            ]);
            return PaymentResult::failed(
                status: PaymentStatus::PENDING,
                failureCode: 'cashfree_verification_unavailable',
                failureMessage: 'Could not connect to Cashfree API: ' . $e->getMessage(),
                gateway: 'cashfree',
                gatewayOrderId: $gatewayPaymentId
            );
        }

        if (!$response->successful()) {
            $errorBody = $response->json() ?? [];
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'fetch_failed',
                failureMessage: $errorBody['message'] ?? 'Failed to fetch payment status from gateway.',
                gateway: 'cashfree',
                gatewayOrderId: $gatewayPaymentId,
                raw: $errorBody
            );
        }

        $responseData = $response->json();
        $orderStatus = $responseData['order_status'] ?? 'PENDING';
        $cfOrderId = $responseData['cf_order_id'] ?? null;
        $orderId = $responseData['order_id'] ?? $gatewayPaymentId;

        if ($orderStatus === 'PAID') {
            $resolvedPaymentId = data_get($responseData, 'payments.0.cf_payment_id') 
                ?? data_get($responseData, 'cf_payment_id') 
                ?? $cfOrderId 
                ?? $orderId;

            return PaymentResult::success(
                status: PaymentStatus::PAID,
                gateway: 'cashfree',
                gatewayOrderId: $orderId,
                gatewayPaymentId: $resolvedPaymentId,
                raw: $responseData
            );
        }

        if (in_array($orderStatus, ['ACTIVE', 'PENDING'], true)) {
            return PaymentResult::pending(
                status: PaymentStatus::PENDING,
                gateway: 'cashfree',
                gatewayOrderId: $orderId,
                raw: $responseData
            );
        }

        return PaymentResult::failed(
            status: PaymentStatus::FAILED,
            failureCode: 'cashfree_payment_not_completed',
            failureMessage: 'Cashfree payment was not completed.',
            gateway: 'cashfree',
            gatewayOrderId: $orderId,
            raw: $responseData
        );
    }

    /**
     * Fetch Cashfree order details from API.
     */
    protected function fetchOrder(string $orderId): \Illuminate\Http\Client\Response
    {
        $this->ensureConfigured();

        return Http::withHeaders($this->headers())
            ->timeout(30)
            ->retry(
                times: 2,
                sleepMilliseconds: 500,
                when: function ($exception) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                },
                throw: false
            )
            ->get($this->baseUrl() . '/orders/' . urlencode($orderId));
    }

    // =========================================================================
    // Interface: Webhook (Phase 5)
    // =========================================================================

    /**
     * Verify the Cashfree webhook signature.
     *
     * Webhook signature format for version 2023-08-01:
     *   Signature = Base64(HMAC-SHA256(timestamp + rawBody, webhookSecret))
     *
     * x-webhook-timestamp header is concatenated directly with the raw JSON payload body.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature, ?string $timestamp = null): bool
    {
        if (empty($this->webhookSecret)) {
            Log::error('CashfreeGateway: Webhook secret is not configured.');
            throw new RuntimeException('Cashfree webhook secret is not configured.');
        }

        if (empty($signature)) {
            Log::warning('CashfreeGateway: Webhook signature is missing.');
            return false;
        }

        if (empty($timestamp)) {
            Log::warning('CashfreeGateway: Webhook timestamp is missing.');
            return false;
        }

        $signStr = $timestamp . $rawBody;
        $expectedSignature = base64_encode(hash_hmac('sha256', $signStr, $this->webhookSecret, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle an incoming Cashfree webhook event.
     */
    public function handleWebhook(array $payload, ?string $signature = null, ?string $rawBody = null): PaymentResult
    {
        // 1. Extract signature and timestamp
        $timestamp = null;
        if ($signature && str_contains($signature, ':')) {
            [$timestamp, $signature] = explode(':', $signature, 2);
        } else {
            $timestamp = request()->header('x-webhook-timestamp') 
                ?? request()->header('X-Webhook-Timestamp');
        }

        // 2. Verify webhook signature
        try {
            $signatureValid = $this->verifyWebhookSignature($rawBody ?? '', $signature, $timestamp);
        } catch (RuntimeException $e) {
            Log::error('CashfreeGateway: Webhook verification failed due to missing config: ' . $e->getMessage());
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'cashfree_webhook_secret_missing',
                failureMessage: $e->getMessage(),
                gateway: 'cashfree',
                raw: $payload
            );
        }

        if (!$signatureValid) {
            Log::warning('CashfreeGateway: Webhook signature verification failed.', [
                'signature' => $signature,
                'timestamp' => $timestamp,
            ]);
            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: 'invalid_webhook_signature',
                failureMessage: 'Invalid Cashfree webhook signature.',
                gateway: 'cashfree',
                raw: $payload
            );
        }

        // 3. Extract identifiers
        $identifiers = $this->extractIdentifiers($payload);
        $gatewayOrderId = $identifiers['gateway_order_id'];
        $gatewayPaymentId = $identifiers['gateway_payment_id'];

        // 4. Determine status from payload
        $eventType = $identifiers['event_type'];
        
        $paymentStatus = data_get($payload, 'data.payment.payment_status');
        $orderStatus = data_get($payload, 'data.order.order_status');

        $isSuccess = ($eventType === 'PAYMENT_SUCCESS_WEBHOOK') 
            || ($paymentStatus === 'SUCCESS') 
            || ($orderStatus === 'PAID');
            
        $isFailed = ($eventType === 'PAYMENT_FAILED_WEBHOOK') 
            || in_array($paymentStatus, ['FAILED', 'USER_DROPPED', 'CANCELLED', 'EXPIRED', 'TERMINATED']) 
            || in_array($orderStatus, ['FAILED', 'CANCELLED', 'EXPIRED', 'TERMINATED']);

        if ($isSuccess) {
            Log::info('CashfreeGateway: Webhook success parsed.', [
                'gateway_order_id' => $gatewayOrderId,
                'gateway_payment_id' => $gatewayPaymentId,
            ]);

            return PaymentResult::success(
                status: PaymentStatus::PAID,
                gateway: 'cashfree',
                gatewayOrderId: $gatewayOrderId,
                gatewayPaymentId: $gatewayPaymentId ?? $gatewayOrderId,
                raw: $payload
            );
        }

        if ($isFailed) {
            $failureMessage = data_get($payload, 'data.payment.payment_message') 
                ?? data_get($payload, 'data.order.order_status') 
                ?? 'Cashfree payment failed.';
            $failureCode = data_get($payload, 'data.payment.payment_status') 
                ?? data_get($payload, 'data.order.order_status') 
                ?? 'PAYMENT_FAILED';

            Log::warning('CashfreeGateway: Webhook failure parsed.', [
                'gateway_order_id' => $gatewayOrderId,
                'failure_code' => $failureCode,
                'failure_message' => $failureMessage,
            ]);

            return PaymentResult::failed(
                status: PaymentStatus::FAILED,
                failureCode: $failureCode,
                failureMessage: $failureMessage,
                gateway: 'cashfree',
                gatewayOrderId: $gatewayOrderId,
                gatewayPaymentId: $gatewayPaymentId,
                raw: $payload
            );
        }

        Log::info('CashfreeGateway: Webhook pending or unsupported parsed.', [
            'gateway_order_id' => $gatewayOrderId,
            'event_type' => $eventType,
        ]);

        return PaymentResult::pending(
            status: PaymentStatus::PENDING,
            gateway: 'cashfree',
            gatewayOrderId: $gatewayOrderId,
            raw: $payload
        );
    }

    // =========================================================================
    // Interface: Identifier Extraction (Phase 2+)
    // =========================================================================

    /**
     * Extract normalized identifiers from a Cashfree webhook or callback payload.
     *
     * Safe and never throws even on malformed or empty payloads.
     */
    public function extractIdentifiers(array $payload): array
    {
        $identifiers = [
            'internal_payment_id' => null,
            'gateway_order_id'    => null,
            'gateway_payment_id'  => null,
            'gateway_event_id'    => null,
            'event_type'          => null,
        ];

        // A. Event Type
        $identifiers['event_type'] = data_get($payload, 'type')
            ?? data_get($payload, 'event_type')
            ?? data_get($payload, 'event');

        // B. Gateway Event ID
        $identifiers['gateway_event_id'] = data_get($payload, 'event_id')
            ?? data_get($payload, 'id')
            ?? data_get($payload, 'gateway_event_id')
            ?? data_get($payload, 'data.event_id');

        // C. Gateway Order ID
        $identifiers['gateway_order_id'] = data_get($payload, 'order_id')
            ?? data_get($payload, 'cf_order_id')
            ?? data_get($payload, 'gateway_order_id')
            ?? data_get($payload, 'data.order.order_id')
            ?? data_get($payload, 'data.order.cf_order_id')
            ?? data_get($payload, 'data.payment.order_id');

        // D. Gateway Payment ID
        $identifiers['gateway_payment_id'] = data_get($payload, 'cf_payment_id')
            ?? data_get($payload, 'payment_id')
            ?? data_get($payload, 'gateway_payment_id')
            ?? data_get($payload, 'data.payment.cf_payment_id')
            ?? data_get($payload, 'data.payment.payment_id')
            ?? data_get($payload, 'payment_session_id');

        // E. Internal Payment ID
        $internalId = data_get($payload, 'internal_payment_id')
            ?? data_get($payload, 'payment_id')
            ?? data_get($payload, 'data.order.order_tags.internal_payment_id')
            ?? data_get($payload, 'data.order.order_tags.ecc_payment_id');

        // Try extracting from order_note
        if (empty($internalId)) {
            $note = data_get($payload, 'data.order.order_note')
                ?? data_get($payload, 'order_note');
            if ($note && preg_match('/ecc_payment_(\d+)/i', $note, $matches)) {
                $internalId = $matches[1];
            }
        }

        // Try extracting from order_id (from ECCPAY123 or ecc_payment_123)
        if (empty($internalId)) {
            $orderId = $identifiers['gateway_order_id'];
            if ($orderId && preg_match('/^(?:ECCPAY|ecc_payment_)(\d+)$/i', $orderId, $matches)) {
                $internalId = $matches[1];
            }
        }

        if ($internalId && is_numeric($internalId)) {
            $identifiers['internal_payment_id'] = (int) $internalId;
        }

        return $identifiers;
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Resolve the Cashfree API base URL based on mode.
     *
     * Mode mapping:
     *  - live / production  => https://api.cashfree.com/pg
     *  - sandbox / test / local / anything else => https://sandbox.cashfree.com/pg
     */
    protected function baseUrl(): string
    {
        return $this->isLiveMode()
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    /**
     * Determine if we're in live/production mode.
     */
    protected function isLiveMode(): bool
    {
        return in_array(strtolower($this->mode), ['live', 'production'], true);
    }

    /**
     * Build standard Cashfree API request headers.
     *
     * NOTE: x-client-secret is deliberately included here for the HTTP call only
     * and must never be forwarded to any response, log, or user-facing payload.
     */
    protected function headers(): array
    {
        return [
            'x-client-id'     => $this->clientId,
            'x-client-secret' => $this->clientSecret,  // Backend-only — NEVER expose
            'x-api-version'   => $this->apiVersion,
            'Content-Type'    => 'application/json',
            'Accept'          => 'application/json',
        ];
    }

    /**
     * Validate that required Cashfree credentials are configured.
     *
     * @throws RuntimeException if any required credential is missing.
     */
    protected function ensureConfigured(): void
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            Log::error('CashfreeGateway: Missing credentials.', [
                'client_id_set'     => !empty($this->clientId),
                'client_secret_set' => !empty($this->clientSecret),
                'mode'              => $this->mode,
                'api_version'       => $this->apiVersion,
            ]);
            throw new RuntimeException(
                'Cashfree credentials are not configured. Set CASHFREE_CLIENT_ID and CASHFREE_CLIENT_SECRET in your .env file.'
            );
        }

        if (empty($this->apiVersion)) {
            throw new RuntimeException(
                'Cashfree API version is not configured. Set CASHFREE_API_VERSION in your .env file.'
            );
        }
    }

    /**
     * Build Cashfree customer_details from Payment user, with safe fallbacks.
     *
     * Returns null if required fields (email + phone) cannot be satisfied.
     *
     * @param Payment $payment
     * @param array   $context  Optional context overrides from the initiating service
     * @return array|null
     */
    protected function buildCustomerDetails(Payment $payment, array $context = []): ?array
    {
        $user = $payment->user;

        // customer_id — required; use user ID prefix or guest prefix
        $customerId = $payment->user_id
            ? 'user_' . $payment->user_id
            : 'guest_' . $payment->id;

        // customer_name — recommended; fallback to context or generic
        $customerName = $context['customer_name']
            ?? ($user ? $user->name : null)
            ?? 'ECC Customer';

        // customer_email — required by Cashfree
        $customerEmail = $context['customer_email']
            ?? ($user ? $user->email : null);

        // customer_phone — required by Cashfree (10-digit Indian mobile)
        $customerPhone = $context['customer_contact']
            ?? $context['customer_phone']
            ?? ($user ? ($user->phone ?? null) : null);

        // Both email and phone must be present for Cashfree orders
        if (empty($customerEmail) || empty($customerPhone)) {
            Log::warning('CashfreeGateway: Missing customer email or phone.', [
                'payment_id'     => $payment->id,
                'user_id'        => $payment->user_id,
                'has_email'      => !empty($customerEmail),
                'has_phone'      => !empty($customerPhone),
            ]);
            return null;
        }

        return [
            'customer_id'    => $customerId,
            'customer_name'  => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => (string) $customerPhone,
        ];
    }

    /**
     * Build a default human-readable description for the order.
     */
    protected function buildDefaultDescription(Payment $payment): string
    {
        $purpose = $payment->purpose ? str_replace('_', ' ', $payment->purpose) : 'payment';
        return 'Executive Cricket Club - ' . ucwords($purpose);
    }

    /**
     * Resolve the return URL for post-payment redirect.
     *
     * Phase 3: Uses CASHFREE_RETURN_URL from config if set.
     * Phase 4 will wire this to a proper verification/return route.
     */
    protected function resolveReturnUrl(Payment $payment): ?string
    {
        if (!empty($this->returnUrl)) {
            // Append payment ID for Phase 4 verification routing
            return rtrim($this->returnUrl, '/') . '?payment_id=' . $payment->id;
        }

        // Attempt to use the named route if it exists (added in Phase 4)
        if (app('router')->has('payments.cashfree.return')) {
            return route('payments.cashfree.return', ['payment' => $payment->id]);
        }

        return null;
    }

    /**
     * Resolve the notify URL for server-side webhook callbacks.
     *
     * Phase 3: Uses CASHFREE_NOTIFY_URL from config if set.
     * Phase 5 will wire this to the Cashfree webhook route.
     */
    protected function resolveNotifyUrl(): ?string
    {
        if (!empty($this->notifyUrl)) {
            return $this->notifyUrl;
        }

        // Use the Cashfree webhook route if it exists (added in Phase 5)
        if (app('router')->has('webhooks.cashfree')) {
            return route('webhooks.cashfree');
        }

        return null;
    }
}
