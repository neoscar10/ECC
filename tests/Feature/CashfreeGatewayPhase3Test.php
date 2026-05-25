<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\MembershipApplication;
use App\Services\Payments\Gateways\CashfreeGateway;
use App\Services\Payments\Gateways\RazorpayGateway;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\DTO\PaymentVerificationData;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * CashfreeGatewayPhase3Test
 *
 * Validates Phase 3 acceptance criteria:
 * - CashfreeGateway::createOrder() calls Cashfree Create Order API
 * - Sandbox mode uses https://sandbox.cashfree.com/pg/orders
 * - Live mode uses https://api.cashfree.com/pg/orders
 * - Headers include x-client-id and x-api-version (not x-client-secret in output)
 * - Payload includes order_id, order_amount, order_currency, customer_details
 * - PaymentResult status is pending with checkout.payment_session_id
 * - client_secret is NEVER in PaymentResult, checkout, or raw (except as header)
 * - Missing credentials throws RuntimeException
 * - API failure returns clean PaymentResult::failed
 * - Missing payment_session_id returns failed result
 * - PaymentManager stores gateway_order_id, cf_order_id, payment_session_id
 * - Payment remains PENDING, never PAID
 * - Razorpay regression: still resolves and verifies correctly
 */
class CashfreeGatewayPhase3Test extends TestCase
{
    use RefreshDatabase;

    /** Standard mock Cashfree API response */
    protected array $mockSuccessResponse = [
        'cf_order_id'         => '1234567890',
        'order_id'            => 'ECCPAY1',
        'entity'              => 'order',
        'order_currency'      => 'INR',
        'order_amount'        => 9109.25,
        'order_status'        => 'ACTIVE',
        'payment_session_id'  => 'session_test_abc123xyz',
        'order_expiry_time'   => '2026-05-23T10:00:00+05:30',
        'order_note'          => 'Executive Cricket Club - Test',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.gateways.razorpay' => [
                'driver'         => RazorpayGateway::class,
                'enabled'        => true,
                'key_id'         => 'rzp_test_key_123',
                'key_secret'     => 'rzp_test_secret_abc',
                'webhook_secret' => 'rzp_webhook_secret_xyz',
                'mode'           => 'test',
            ],
            'payments.gateways.cashfree' => [
                'driver'         => CashfreeGateway::class,
                'enabled'        => true,  // Enabled for Phase 3 testing
                'client_id'      => 'cf_test_client_id_123',
                'client_secret'  => 'cf_test_client_secret_xyz',  // Only used in HTTP headers
                'webhook_secret' => 'cf_webhook_secret_abc',
                'mode'           => 'sandbox',
                'api_version'    => '2023-08-01',
                'return_url'     => null,
                'notify_url'     => null,
            ],
            'payments.supported_gateways' => ['razorpay', 'cashfree'],
            'payments.default_gateway'    => 'razorpay',
        ]);
    }

    // =========================================================================
    // A. createOrder() — Happy Path
    // =========================================================================

    /** @test */
    public function cashfree_create_order_calls_sandbox_url_in_sandbox_mode(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        $user = User::factory()->create([
            'email'  => 'test@ecc.com',
            'phone' => '9876543210',
        ]);

        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 9109.25,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
            'gateway'  => 'cashfree',
        ]);

        $gateway = app(CashfreeGateway::class);
        $result  = $gateway->createOrder($payment);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sandbox.cashfree.com/pg/orders');
        });

        $this->assertEquals(PaymentStatus::PENDING, $result->status);
    }

    /** @test */
    public function cashfree_create_order_calls_live_url_in_live_mode(): void
    {
        config(['payments.gateways.cashfree.mode' => 'live']);

        Http::fake([
            'api.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        $user = User::factory()->create([
            'email'  => 'test@ecc.com',
            'phone' => '9876543210',
        ]);

        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 1000.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
            'gateway'  => 'cashfree',
        ]);

        $gateway = app(CashfreeGateway::class);
        $gateway->createOrder($payment);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.cashfree.com/pg/orders');
        });
    }

    /** @test */
    public function cashfree_create_order_sends_correct_headers(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        $user = User::factory()->create(['email' => 'hdr@ecc.com', 'phone' => '9000000001']);
        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 500.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
            'gateway'  => 'cashfree',
        ]);

        app(CashfreeGateway::class)->createOrder($payment);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-client-id', 'cf_test_client_id_123')
                && $request->hasHeader('x-api-version', '2023-08-01')
                && $request->hasHeader('x-client-secret');  // header present for API call
        });
    }

    /** @test */
    public function cashfree_create_order_sends_correct_payload_structure(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        $user = User::factory()->create(['email' => 'payload@ecc.com', 'phone' => '9000000002']);
        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 9109.25,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
            'gateway'  => 'cashfree',
        ]);

        app(CashfreeGateway::class)->createOrder($payment);

        Http::assertSent(function ($request) use ($payment) {
            $body = $request->data();
            return isset($body['order_id'])
                && isset($body['order_amount'])
                && isset($body['order_currency'])
                && isset($body['customer_details'])
                && $body['order_id']     === 'ECCPAY' . $payment->id
                && $body['order_amount'] === 9109.25
                && $body['order_currency'] === 'INR'
                && isset($body['customer_details']['customer_email'])
                && isset($body['customer_details']['customer_phone']);
        });
    }

    /** @test */
    public function cashfree_create_order_returns_pending_result_with_payment_session_id(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        $user = User::factory()->create(['email' => 'sess@ecc.com', 'phone' => '9000000003']);
        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 9109.25,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
            'gateway'  => 'cashfree',
        ]);

        $gateway = app(CashfreeGateway::class);
        $result  = $gateway->createOrder($payment);

        $this->assertEquals(PaymentStatus::PENDING, $result->status);
        $this->assertEquals('cashfree', $result->gateway);
        $this->assertEquals('ECCPAY' . $payment->id, $result->gatewayOrderId);

        // Checkout must contain payment_session_id
        $this->assertArrayHasKey('payment_session_id', $result->checkout);
        $this->assertEquals('session_test_abc123xyz', $result->checkout['payment_session_id']);

        // Checkout must have correct gateway identifier
        $this->assertEquals('cashfree', $result->checkout['gateway']);

        // Checkout must have cf_order_id
        $this->assertEquals('1234567890', $result->checkout['cf_order_id']);

        // Checkout must have environment
        $this->assertArrayHasKey('environment', $result->checkout);
        $this->assertEquals('sandbox', $result->checkout['environment']);
    }

    /** @test */
    public function cashfree_create_order_checkout_does_not_expose_client_secret(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        $user = User::factory()->create(['email' => 'sec@ecc.com', 'phone' => '9000000004']);
        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 500.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
        ]);

        $result   = app(CashfreeGateway::class)->createOrder($payment);
        $checkoutJson = json_encode($result->checkout);
        $rawJson      = json_encode($result->raw);

        // client_secret must NOT appear anywhere in checkout or raw output
        $this->assertStringNotContainsString('cf_test_client_secret_xyz', $checkoutJson);
        $this->assertStringNotContainsString('cf_test_client_secret_xyz', $rawJson);
        $this->assertStringNotContainsString('x-client-secret', $checkoutJson);
    }

    /** @test */
    public function cashfree_create_order_sets_gateway_order_id_from_response(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        $user = User::factory()->create(['email' => 'oid@ecc.com', 'phone' => '9000000005']);
        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 500.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
        ]);

        $result = app(CashfreeGateway::class)->createOrder($payment);

        // gateway_order_id should be the Cashfree order_id returned in response
        $this->assertEquals('ECCPAY' . $payment->id, $result->gatewayOrderId);
    }

    // =========================================================================
    // B. createOrder() — Failure Cases
    // =========================================================================

    /** @test */
    public function cashfree_create_order_throws_when_credentials_missing(): void
    {
        config([
            'payments.gateways.cashfree.client_id'     => null,
            'payments.gateways.cashfree.client_secret' => null,
        ]);

        $payment = Payment::create([
            'amount'   => 1000.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/credentials are not configured/');

        app(CashfreeGateway::class)->createOrder($payment);
    }

    /** @test */
    public function cashfree_create_order_returns_failed_on_invalid_amount(): void
    {
        $payment = Payment::create([
            'amount'   => 0.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
        ]);

        $result = app(CashfreeGateway::class)->createOrder($payment);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
        $this->assertEquals('invalid_amount', $result->failureCode);
    }

    /** @test */
    public function cashfree_create_order_returns_failed_on_unsupported_currency(): void
    {
        $payment = Payment::create([
            'amount'   => 100.00,
            'currency' => 'USD',
            'status'   => PaymentStatus::INITIATED,
        ]);

        $result = app(CashfreeGateway::class)->createOrder($payment);

        $this->assertFalse($result->success);
        $this->assertEquals('unsupported_currency', $result->failureCode);
    }

    /** @test */
    public function cashfree_create_order_returns_failed_on_missing_customer_details(): void
    {
        // Payment with no user and no context customer details
        $payment = Payment::create([
            'amount'   => 500.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
            // No user_id
        ]);

        $result = app(CashfreeGateway::class)->createOrder($payment);

        $this->assertFalse($result->success);
        $this->assertEquals('missing_customer_details', $result->failureCode);
        $this->assertStringContainsString('email', strtolower($result->failureMessage));
    }

    /** @test */
    public function cashfree_create_order_returns_failed_on_401_auth_error(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response([
                'type'    => 'invalid_request_error',
                'code'    => 'authentication_failed',
                'message' => 'Authentication failed. Please provide valid credentials.',
            ], 401),
        ]);

        $user = User::factory()->create(['email' => 'auth@ecc.com', 'phone' => '9000000006']);
        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 500.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
        ]);

        $result = app(CashfreeGateway::class)->createOrder($payment);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
        $this->assertNotNull($result->failureCode);
        $this->assertStringContainsString('Authentication', $result->failureMessage);
    }

    /** @test */
    public function cashfree_create_order_returns_failed_on_400_validation_error(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response([
                'type'    => 'invalid_request_error',
                'code'    => 'order_id_duplicate',
                'message' => 'An order with this order_id already exists.',
            ], 400),
        ]);

        $user = User::factory()->create(['email' => 'dup@ecc.com', 'phone' => '9000000007']);
        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 500.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
        ]);

        $result = app(CashfreeGateway::class)->createOrder($payment);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
    }

    /** @test */
    public function cashfree_create_order_returns_failed_when_payment_session_id_missing(): void
    {
        // Response without payment_session_id
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response([
                'cf_order_id' => '9999',
                'order_id'    => 'ECCPAY1',
                'order_status' => 'ACTIVE',
                // No payment_session_id
            ], 200),
        ]);

        $user = User::factory()->create(['email' => 'nses@ecc.com', 'phone' => '9000000008']);
        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 500.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
        ]);

        $result = app(CashfreeGateway::class)->createOrder($payment);

        $this->assertFalse($result->success);
        $this->assertEquals('missing_payment_session_id', $result->failureCode);
    }

    // =========================================================================
    // C. PaymentManager Integration
    // =========================================================================

    /** @test */
    public function payment_manager_initiate_cashfree_stores_gateway_order_id_and_meta(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response(array_merge($this->mockSuccessResponse, [
                'payment_session_id' => 'session_manager_test_xyz',
            ]), 200),
        ]);

        $user = User::factory()->create(['email' => 'mgr@ecc.com', 'phone' => '9000000009']);

        $manager = app(PaymentManager::class);
        $result  = $manager->initiatePayment(
            payable: null,
            amount: 9109.25,
            purpose: 'shop_order',
            user: $user,
            gateway: 'cashfree',
        );

        $payment = $result['payment'];

        // Payment status must remain PENDING (not paid)
        $this->assertEquals(PaymentStatus::PENDING, $payment->status);
        $this->assertFalse($payment->isPaid());

        // gateway_order_id must be stored
        $this->assertNotNull($payment->gateway_order_id);
        $this->assertEquals('ECCPAY' . $payment->id, $payment->gateway_order_id);

        // meta must contain cf_order_id
        $this->assertArrayHasKey('cf_order_id', $payment->meta);
        $this->assertEquals('1234567890', $payment->meta['cf_order_id']);

        // meta must contain payment_session_id
        $this->assertArrayHasKey('payment_session_id', $payment->meta);
        $this->assertEquals('session_manager_test_xyz', $payment->meta['payment_session_id']);

        // checkout must be in meta
        $this->assertArrayHasKey('checkout', $payment->meta);
        $this->assertEquals('cashfree', $payment->meta['checkout']['gateway']);

        // checkout must expose payment_session_id
        $this->assertArrayHasKey('payment_session_id', $payment->meta['checkout']);
    }

    /** @test */
    public function payment_manager_cashfree_checkout_key_in_result(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        $user = User::factory()->create(['email' => 'chk@ecc.com', 'phone' => '9000000010']);

        $result = app(PaymentManager::class)->initiatePayment(
            payable: null,
            amount: 500.00,
            purpose: 'test',
            user: $user,
            gateway: 'cashfree',
        );

        $checkout = $result['checkout'];

        $this->assertNotNull($checkout);
        $this->assertEquals('cashfree', $checkout['gateway']);
        $this->assertArrayHasKey('payment_session_id', $checkout);
        $this->assertArrayHasKey('cf_order_id', $checkout);

        // client_secret must not be in checkout
        $this->assertStringNotContainsString('cf_test_client_secret_xyz', json_encode($checkout));
    }

    /** @test */
    public function payment_manager_cashfree_payment_is_never_marked_paid(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        $user = User::factory()->create(['email' => 'nopay@ecc.com', 'phone' => '9000000011']);

        $result  = app(PaymentManager::class)->initiatePayment(
            payable: null,
            amount: 500.00,
            purpose: 'shop_order',
            user: $user,
            gateway: 'cashfree',
        );

        $payment = $result['payment'];
        $this->assertNotEquals(PaymentStatus::PAID, $payment->status);
        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->gateway_payment_id);  // No payment ID yet — only set after actual payment
    }

    // =========================================================================
    // D. Context-based customer details (for mobile API)
    // =========================================================================

    /** @test */
    public function cashfree_create_order_accepts_context_customer_details(): void
    {
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response($this->mockSuccessResponse, 200),
        ]);

        // Payment with no user — customer details come from context
        $payment = Payment::create([
            'amount'   => 500.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
        ]);

        $result = app(CashfreeGateway::class)->createOrder($payment, [
            'customer_name'    => 'Rahul Sharma',
            'customer_email'   => 'rahul@test.com',
            'customer_contact' => '9123456789',
        ]);

        $this->assertEquals(PaymentStatus::PENDING, $result->status);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['customer_details']['customer_email'] === 'rahul@test.com'
                && $body['customer_details']['customer_phone'] === '9123456789';
        });
    }

    // =========================================================================
    // E. Razorpay Regression Tests
    // =========================================================================

    /** @test */
    public function razorpay_create_order_still_works_after_cashfree_implementation(): void
    {
        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id'       => 'order_REG_PHASE3_XYZ',
                'entity'   => 'order',
                'amount'   => 200000,
                'currency' => 'INR',
                'status'   => 'created',
                'receipt'  => 'ecc_payment_1',
            ], 201),
        ]);

        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => 2000.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
            'gateway'  => 'razorpay',
        ]);

        $gateway = app(RazorpayGateway::class);
        $result  = $gateway->createOrder($payment);

        $this->assertEquals(PaymentStatus::PENDING, $result->status);
        $this->assertEquals('razorpay', $result->gateway);
        $this->assertEquals('order_REG_PHASE3_XYZ', $result->gatewayOrderId);
        $this->assertArrayHasKey('key', $result->checkout);
    }

    /** @test */
    public function razorpay_verify_payment_still_works_after_cashfree_phase3(): void
    {
        $keySecret = 'rzp_test_secret_abc';
        $orderId   = 'order_PHASE3_001';
        $paymentId = 'pay_PHASE3_001';
        $signature = hash_hmac('sha256', "{$orderId}|{$paymentId}", $keySecret);

        $payment = Payment::create([
            'gateway_order_id' => $orderId,
            'amount'           => 1000.00,
            'gateway'          => 'razorpay',
        ]);

        $data = new PaymentVerificationData(
            gateway: 'razorpay',
            gatewayOrderId: $orderId,
            gatewayPaymentId: $paymentId,
            gatewaySignature: $signature,
        );

        $result = app(RazorpayGateway::class)->verifyPayment($payment, $data);

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::PAID, $result->status);
        $this->assertEquals('razorpay', $result->gateway);
    }

    /** @test */
    public function payment_manager_resolves_razorpay_unaffected_by_cashfree(): void
    {
        $gateway = app(PaymentManager::class)->getGateway('razorpay');
        $this->assertInstanceOf(RazorpayGateway::class, $gateway);
        $this->assertEquals('razorpay', $gateway->gatewayName());
    }

    /** @test */
    public function payment_manager_default_gateway_is_still_razorpay(): void
    {
        $this->assertEquals('razorpay', config('payments.default_gateway'));
    }

    // =========================================================================
    // F. Phase 3 — verifyPayment and fetchPayment still throw (Phase 4)
    // =========================================================================



    /** @test */
    public function cashfree_handle_webhook_returns_failed_result_when_signature_invalid(): void
    {
        $result = app(CashfreeGateway::class)->handleWebhook(['type' => 'PAYMENT_SUCCESS_WEBHOOK']);
        $this->assertFalse($result->success);
        $this->assertEquals('invalid_webhook_signature', $result->failureCode);
    }
}
