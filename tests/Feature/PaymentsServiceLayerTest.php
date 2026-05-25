<?php

namespace Tests\Feature;

use App\Models\MembershipApplication;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Services\Payments\PaymentLedgerService;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentWebhookService;
use App\Services\Payments\DTO\PaymentInitiationData;
use App\Services\Payments\DTO\PaymentVerificationData;
use App\Services\Payments\DTO\PaymentResult;
use App\Support\Payments\PaymentStatus;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\PaymentPurpose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsServiceLayerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_dtos_store_and_normalize_data_correctly()
    {
        $user = User::factory()->create();
        $application = MembershipApplication::create([
            'user_id' => $user->id,
            'personal_details_json' => ['first_name' => 'Jane'],
            'cricket_profile_json' => [],
            'collector_intent_json' => [],
            'payment_status' => 'unpaid',
        ]);

        // 1. PaymentInitiationData DTO test
        $initiation = new PaymentInitiationData(
            payable: $application,
            amount: '1250.50',
            purpose: PaymentPurpose::MEMBERSHIP_UPGRADE,
            user: $user,
            gateway: 'razorpay',
            context: ['currency' => 'INR', 'meta' => ['key' => 'val']]
        );

        $this->assertEquals($application->id, $initiation->payable->id);
        $this->assertEquals('1250.50', $initiation->amount);
        $this->assertEquals('INR', $initiation->currency);
        $this->assertEquals(PaymentPurpose::MEMBERSHIP_UPGRADE, $initiation->purpose);
        $this->assertEquals($user->id, $initiation->user->id);
        $this->assertEquals('razorpay', $initiation->gateway);

        // 2. PaymentVerificationData DTO test
        $verificationPayload = [
            'razorpay_order_id' => 'order_xyz',
            'razorpay_payment_id' => 'pay_abc',
            'razorpay_signature' => 'sig_123',
            'gateway' => 'razorpay'
        ];
        $verification = PaymentVerificationData::fromArray($verificationPayload);

        $this->assertEquals('razorpay', $verification->gateway);
        $this->assertEquals('order_xyz', $verification->gatewayOrderId);
        $this->assertEquals('pay_abc', $verification->gatewayPaymentId);
        $this->assertEquals('sig_123', $verification->gatewaySignature);

        // 3. PaymentResult DTO test
        $result = PaymentResult::success(
            status: PaymentStatus::PAID,
            gateway: 'razorpay',
            gatewayOrderId: 'order_xyz',
            gatewayPaymentId: 'pay_abc',
            checkout: ['color' => '#111'],
            raw: ['status' => 'captured']
        );

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::PAID, $result->status);
        $this->assertEquals('razorpay', $result->gateway);
        $this->assertEquals('#111', $result->checkout['color']);
        $this->assertEquals('captured', $result->raw['status']);

        $arrayRep = $result->toArray();
        $this->assertTrue($arrayRep['success']);
        $this->assertEquals(PaymentStatus::PAID, $arrayRep['status']);
    }

    /** @test */
    public function test_ledger_creates_payment_with_defaults()
    {
        $ledger = app(PaymentLedgerService::class);
        $user = User::factory()->create();
        $application = MembershipApplication::create([
            'user_id' => $user->id,
            'personal_details_json' => ['first_name' => 'Jane'],
            'cricket_profile_json' => [],
            'collector_intent_json' => [],
            'payment_status' => 'unpaid',
        ]);

        $payment = $ledger->createPayment([
            'user_id' => $user->id,
            'payable_type' => get_class($application),
            'payable_id' => $application->id,
            'purpose' => PaymentPurpose::MEMBERSHIP_UPGRADE,
            'amount' => 500, // missing gateway, currency, status defaults
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $user->id,
            'payable_type' => get_class($application),
            'payable_id' => $application->id,
            'amount' => '500.00',
            'currency' => 'INR',
            'status' => PaymentStatus::INITIATED,
            'gateway' => 'razorpay',
        ]);
    }

    /** @test */
    public function test_ledger_mark_paid_is_idempotent()
    {
        $ledger = app(PaymentLedgerService::class);
        $payment = $ledger->createPayment([
            'amount' => 150.00,
        ]);

        $this->assertFalse($payment->isPaid());

        // Mark paid 1st time
        $payment = $ledger->markPaid($payment, 'pay_001', ['original' => 'meta']);
        $this->assertTrue($payment->isPaid());
        $this->assertEquals('pay_001', $payment->gateway_payment_id);
        $this->assertEquals('meta', $payment->meta['original']);
        $paidAt = $payment->paid_at;
        $this->assertNotNull($paidAt);

        // Mark paid 2nd time (should return payment without changing paid_at or overwriting meta)
        $payment2 = $ledger->markPaid($payment, 'pay_002', ['second' => 'meta']);
        $this->assertEquals($paidAt->toIso8601String(), $payment2->paid_at->toIso8601String());
        $this->assertEquals('pay_001', $payment2->gateway_payment_id);
        $this->assertArrayNotHasKey('second', $payment2->meta ?? []);
    }

    /** @test */
    public function test_ledger_records_event_safely_with_null_payment_id()
    {
        $ledger = app(PaymentLedgerService::class);
        
        $event = $ledger->recordEvent([
            'payment_id' => null, // unmatched webhook event
            'gateway' => 'razorpay',
            'event_type' => 'payment.disputed',
            'payload' => ['dispute' => 'data'],
        ]);

        $this->assertDatabaseHas('payment_events', [
            'id' => $event->id,
            'payment_id' => null,
            'gateway' => 'razorpay',
            'event_type' => 'payment.disputed',
        ]);
    }

    /** @test */
    public function test_manager_resolves_supported_gateways_and_throws_exceptions()
    {
        $manager = app(PaymentManager::class);

        // 1. Resolve Razorpay
        $gateway = $manager->getGateway('razorpay');
        $this->assertInstanceOf(\App\Services\Payments\Gateways\RazorpayGateway::class, $gateway);
        $this->assertEquals('razorpay', $gateway->gatewayName());

        // 2. Try default config fallback
        config(['payments.default_gateway' => 'razorpay']);
        $gatewayDefault = $manager->getGateway();
        $this->assertInstanceOf(\App\Services\Payments\Gateways\RazorpayGateway::class, $gatewayDefault);

        // 3. Resolve Cashfree
        $gatewayCashfree = $manager->getGateway('cashfree');
        $this->assertInstanceOf(\App\Services\Payments\Gateways\CashfreeGateway::class, $gatewayCashfree);
        $this->assertEquals('cashfree', $gatewayCashfree->gatewayName());
    }

    /** @test */
    public function test_manager_throws_exception_for_unknown_gateway()
    {
        $manager = app(PaymentManager::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment gateway driver [unknown_driver] is not supported.');
        $manager->getGateway('unknown_driver');
    }

    /** @test */
    public function test_razorpay_gateway_adapter_throws_controlled_exceptions()
    {
        config([
            'payments.gateways.razorpay.key_id' => null,
            'payments.gateways.razorpay.key_secret' => null,
        ]);
        $gateway = new \App\Services\Payments\Gateways\RazorpayGateway();
        $payment = Payment::create(['amount' => 100]);

        $this->assertEquals('razorpay', $gateway->gatewayName());

        try {
            $gateway->createOrder($payment);
            $this->fail("Expected Exception was not thrown.");
        } catch (\RuntimeException $e) {
            $this->assertEquals("Razorpay integration error: Missing key_id or key_secret.", $e->getMessage());
        }

        try {
            $gateway->verifyPayment($payment, new PaymentVerificationData('razorpay'));
            $this->fail("Expected Exception was not thrown.");
        } catch (\RuntimeException $e) {
            $this->assertEquals("Razorpay integration error: Missing key_id or key_secret.", $e->getMessage());
        }
    }

    /** @test */
    public function test_webhook_service_gracefully_handles_placeholder_runtime_exceptions()
    {
        config([
            'payments.gateways.razorpay.webhook_secret' => null,
        ]);
        $webhookService = app(PaymentWebhookService::class);
        
        $payload = [
            'event' => 'payment.authorized',
            'id' => 'evt_test_123',
            'amount' => 50000
        ];

        // Call handle for razorpay which will trigger standard placeholder RuntimeException
        $response = $webhookService->handle('razorpay', $payload);

        $this->assertEquals('ignored', $response['status']);
        $this->assertStringContainsString('Controlled webhook placeholder error', $response['reason']);

        // Assert payment event is recorded with __error_caught logged inside payload
        $this->assertDatabaseHas('payment_events', [
            'gateway' => 'razorpay',
            'event_type' => 'payment.authorized',
            'gateway_event_id' => 'evt_test_123',
            'signature_valid' => false,
        ]);

        $event = PaymentEvent::where('gateway_event_id', 'evt_test_123')->first();
        $this->assertNotNull($event);
        $this->assertEquals('Razorpay webhook integration error: Missing webhook_secret.', $event->payload['__error_caught']);
    }

    /** @test */
    public function test_manager_initiate_payment_gracefully_handles_gateway_exception()
    {
        // Configure credentials so it doesn't throw a credentials exception
        config([
            'payments.gateways.razorpay' => [
                'driver' => \App\Services\Payments\Gateways\RazorpayGateway::class,
                'enabled' => true,
                'key_id' => 'rzp_test_key_123',
                'key_secret' => 'rzp_test_secret_abc',
            ]
        ]);

        $user = User::factory()->create();
        $application = MembershipApplication::create([
            'user_id' => $user->id,
            'personal_details_json' => ['first_name' => 'Jane'],
            'cricket_profile_json' => [],
            'collector_intent_json' => [],
            'payment_status' => 'unpaid',
        ]);

        // Mock a 400 Bad Request to simulate a gateway API failure
        \Illuminate\Support\Facades\Http::fake([
            'https://api.razorpay.com/v1/orders' => \Illuminate\Support\Facades\Http::response([
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'The amount must be at least 100 paise.',
                ]
            ], 400)
        ]);

        $manager = app(PaymentManager::class);

        // This should not throw an exception because the manager catches it and handles gracefully
        $response = $manager->initiatePayment(
            payable: $application,
            amount: 50.00,
            purpose: PaymentPurpose::MEMBERSHIP_UPGRADE,
            user: $user,
            gateway: 'razorpay'
        );

        $payment = $response['payment'];
        $result = $response['result'];

        $this->assertEquals(PaymentStatus::FAILED, $payment->status);
        $this->assertEquals('gateway_error', $payment->failure_code);
        $this->assertStringContainsString('The amount must be at least 100 paise.', $payment->failure_message);
        
        $this->assertFalse($result['success']);
        $this->assertEquals(PaymentStatus::FAILED, $result['status']);
        $this->assertEquals('gateway_error', $result['failure_code']);
    }
}
