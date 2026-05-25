<?php

namespace Tests\Feature;

use App\Models\MembershipApplication;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\Gateways\RazorpayGateway;
use App\Services\Payments\DTO\PaymentVerificationData;
use App\Support\Payments\PaymentStatus;
use App\Support\Payments\PaymentPurpose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RazorpayGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Dynamically set mock configurations for tests
        config([
            'payments.gateways.razorpay' => [
                'driver' => \App\Services\Payments\Gateways\RazorpayGateway::class,
                'enabled' => true,
                'key_id' => 'rzp_test_key_123',
                'key_secret' => 'rzp_test_secret_abc',
                'webhook_secret' => 'rzp_webhook_secret_xyz',
                'mode' => 'test',
            ]
        ]);
    }

    /** @test */
    public function test_create_order_success()
    {
        $user = User::factory()->create();
        $application = MembershipApplication::create([
            'user_id' => $user->id,
            'personal_details_json' => ['first_name' => 'John'],
            'cricket_profile_json' => [],
            'collector_intent_json' => [],
            'payment_status' => 'unpaid',
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'payable_type' => get_class($application),
            'payable_id' => $application->id,
            'purpose' => PaymentPurpose::MEMBERSHIP_UPGRADE,
            'gateway' => 'razorpay',
            'amount' => 2000.00,
            'currency' => 'INR',
            'status' => PaymentStatus::INITIATED,
        ]);

        // Mock Razorpay Order API response
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_ABC123xyz',
                'entity' => 'order',
                'amount' => 200000,
                'amount_paid' => 0,
                'amount_due' => 200000,
                'currency' => 'INR',
                'receipt' => (string) $payment->id,
                'status' => 'created',
                'attempts' => 0,
                'notes' => [],
                'created_at' => 1716182400
            ], 201)
        ]);

        $gateway = app(RazorpayGateway::class);
        $result = $gateway->createOrder($payment);

        $this->assertFalse($result->success); // Pending is success = false
        $this->assertEquals(PaymentStatus::PENDING, $result->status);
        $this->assertEquals('order_ABC123xyz', $result->gatewayOrderId);
        $this->assertEquals('razorpay', $result->gateway);

        // Verify standard checkout array structure is correct
        $this->assertEquals('rzp_test_key_123', $result->checkout['key']);
        $this->assertEquals(200000, $result->checkout['amount']);
        $this->assertEquals('order_ABC123xyz', $result->checkout['order_id']);
        $this->assertEquals('INR', $result->checkout['currency']);
        $this->assertEquals($payment->id, $result->checkout['notes']['payment_id']);

        // Assert HTTP call details
        Http::assertSent(function ($request) use ($payment) {
            return $request->url() === 'https://api.razorpay.com/v1/orders' &&
                $request['amount'] === 200000 &&
                $request['currency'] === 'INR' &&
                $request['receipt'] === 'ecc_payment_' . $payment->id;
        });
    }

    /** @test */
    public function test_create_order_failure_throws_exception()
    {
        $payment = Payment::create([
            'amount' => 500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::INITIATED,
        ]);

        // Mock bad request response
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'The amount must be at least 100 paise.',
                    'source' => 'business',
                    'step' => 'payment_initiation',
                    'reason' => 'input_validation_failed',
                ]
            ], 400)
        ]);

        $gateway = app(RazorpayGateway::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Razorpay order creation failed: The amount must be at least 100 paise.');

        $gateway->createOrder($payment);
    }

    /** @test */
    public function test_verify_payment_signature_success()
    {
        $payment = Payment::create([
            'gateway_order_id' => 'order_XYZ987',
            'amount' => 2000.00,
        ]);

        $gatewayPaymentId = 'pay_GHI456';
        $keySecret = 'rzp_test_secret_abc';
        // Compute correct signature matching Razorpay's format
        $signature = hash_hmac('sha256', 'order_XYZ987|pay_GHI456', $keySecret);

        $verificationData = new PaymentVerificationData(
            gateway: 'razorpay',
            gatewayOrderId: 'order_XYZ987',
            gatewayPaymentId: $gatewayPaymentId,
            gatewaySignature: $signature
        );

        $gateway = app(RazorpayGateway::class);
        $result = $gateway->verifyPayment($payment, $verificationData);

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::PAID, $result->status);
        $this->assertEquals('order_XYZ987', $result->gatewayOrderId);
        $this->assertEquals($gatewayPaymentId, $result->gatewayPaymentId);
    }

    /** @test */
    public function test_verify_payment_signature_failure()
    {
        $payment = Payment::create([
            'gateway_order_id' => 'order_XYZ987',
            'amount' => 2000.00,
        ]);

        $verificationData = new PaymentVerificationData(
            gateway: 'razorpay',
            gatewayOrderId: 'order_XYZ987',
            gatewayPaymentId: 'pay_GHI456',
            gatewaySignature: 'bad_signature_mismatch'
        );

        $gateway = app(RazorpayGateway::class);
        $result = $gateway->verifyPayment($payment, $verificationData);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
        $this->assertEquals('invalid_signature', $result->failureCode);
        $this->assertStringContainsString('Signature mismatch', $result->failureMessage);
    }

    /** @test */
    public function test_fetch_payment_captured_success()
    {
        Http::fake([
            'https://api.razorpay.com/v1/payments/pay_XYZ123' => Http::response([
                'id' => 'pay_XYZ123',
                'entity' => 'payment',
                'amount' => 200000,
                'currency' => 'INR',
                'status' => 'captured',
                'order_id' => 'order_ABC123',
                'method' => 'card',
                'card_id' => 'card_123',
                'captured' => true,
                'error_code' => null,
                'error_description' => null,
            ], 200)
        ]);

        $gateway = app(RazorpayGateway::class);
        $result = $gateway->fetchPayment('pay_XYZ123');

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::PAID, $result->status);
        $this->assertEquals('order_ABC123', $result->gatewayOrderId);
        $this->assertEquals('pay_XYZ123', $result->gatewayPaymentId);
    }

    /** @test */
    public function test_fetch_payment_failed()
    {
        Http::fake([
            'https://api.razorpay.com/v1/payments/pay_XYZ123' => Http::response([
                'id' => 'pay_XYZ123',
                'entity' => 'payment',
                'amount' => 200000,
                'currency' => 'INR',
                'status' => 'failed',
                'order_id' => 'order_ABC123',
                'method' => 'card',
                'captured' => false,
                'error_code' => 'BAD_REQUEST_ERROR',
                'error_description' => 'Card was declined by bank',
            ], 200) // Razorpay details endpoint returns 200 with status=failed for failed transactions
        ]);

        $gateway = app(RazorpayGateway::class);
        $result = $gateway->fetchPayment('pay_XYZ123');

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
        $this->assertEquals('BAD_REQUEST_ERROR', $result->failureCode);
        $this->assertEquals('Card was declined by bank', $result->failureMessage);
    }

    /** @test */
    public function test_handle_webhook_valid_signature_success()
    {
        $payload = [
            'entity' => 'event',
            'account_id' => 'acc_test_123',
            'event' => 'order.paid',
            'contains' => ['payment', 'order'],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_XYZ123',
                        'amount' => 200000,
                        'order_id' => 'order_ABC123',
                    ]
                ]
            ],
            'created_at' => 1716182400
        ];

        $rawBody = json_encode($payload);
        $webhookSecret = 'rzp_webhook_secret_xyz';
        $signature = hash_hmac('sha256', $rawBody, $webhookSecret);

        $gateway = app(RazorpayGateway::class);
        $result = $gateway->handleWebhook($payload, $signature, $rawBody);

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::PAID, $result->status);
        $this->assertEquals('order_ABC123', $result->gatewayOrderId);
        $this->assertEquals('pay_XYZ123', $result->gatewayPaymentId);
    }

    /** @test */
    public function test_handle_webhook_invalid_signature_fails()
    {
        $payload = ['event' => 'order.paid'];
        $rawBody = json_encode($payload);

        $gateway = app(RazorpayGateway::class);
        $result = $gateway->handleWebhook($payload, 'invalid_sig', $rawBody);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
        $this->assertEquals('invalid_webhook_signature', $result->failureCode);
    }

    /** @test */
    public function test_create_order_invalid_amount_throws_exception()
    {
        $payment = Payment::create([
            'amount' => 0.00,
            'currency' => 'INR',
            'status' => PaymentStatus::INITIATED,
        ]);

        $gateway = app(RazorpayGateway::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amount must be greater than zero.');

        $gateway->createOrder($payment);
    }

    /** @test */
    public function test_create_order_invalid_currency_throws_exception()
    {
        $payment = Payment::create([
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => PaymentStatus::INITIATED,
        ]);

        $gateway = app(RazorpayGateway::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Razorpay gateway only supports payments in INR.');

        $gateway->createOrder($payment);
    }

    /** @test */
    public function test_payments_artisan_command_fails_without_credentials()
    {
        // Set credentials to empty
        config([
            'payments.gateways.razorpay' => [
                'driver' => \App\Services\Payments\Gateways\RazorpayGateway::class,
                'enabled' => true,
                'key_id' => '',
                'key_secret' => '',
            ]
        ]);

        $this->artisan('payments:test-razorpay-initiation')
            ->expectsOutputToContain('are missing in your environment configuration')
            ->assertExitCode(1);
    }

    /** @test */
    public function test_payments_artisan_command_succeeds_with_credentials()
    {
        config([
            'payments.gateways.razorpay' => [
                'driver' => \App\Services\Payments\Gateways\RazorpayGateway::class,
                'enabled' => true,
                'key_id' => 'rzp_test_key_123',
                'key_secret' => 'rzp_test_secret_abc',
            ]
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_ABC123xyz',
                'entity' => 'order',
                'amount' => 10000,
                'currency' => 'INR',
                'status' => 'created',
                'receipt' => 'ecc_payment_1',
            ], 201)
        ]);

        $this->artisan('payments:test-razorpay-initiation 100')
            ->expectsOutputToContain('Internal Payment ledger record created successfully!')
            ->assertExitCode(0);
    }
}
