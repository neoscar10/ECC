<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\Payments\Gateways\CashfreeGateway;
use App\Services\Payments\Gateways\RazorpayGateway;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\DTO\PaymentVerificationData;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * CashfreeGatewayPhase2Test
 *
 * Validates Phase 2 acceptance criteria:
 * - CashfreeGateway class exists and implements PaymentGatewayInterface
 * - gatewayName() returns 'cashfree'
 * - All payment methods throw controlled not-implemented RuntimeExceptions
 * - extractIdentifiers() is safe, never throws, and returns normalized keys
 * - PaymentManager resolves CashfreeGateway from config
 * - PaymentManager still resolves RazorpayGateway (regression)
 * - PaymentManager throws for unknown gateways
 * - Config flags are correct
 */
class CashfreeGatewayPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Provide mock config so tests are self-contained
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
                'enabled'        => false,  // Not active until Phase 3
                'client_id'      => null,
                'client_secret'  => null,
                'webhook_secret' => null,
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
    // A. Config Tests
    // =========================================================================

    /** @test */
    public function cashfree_driver_config_points_to_cashfree_gateway_class(): void
    {
        $this->assertEquals(
            CashfreeGateway::class,
            config('payments.gateways.cashfree.driver')
        );
    }

    /** @test */
    public function cashfree_mode_defaults_to_sandbox(): void
    {
        $this->assertEquals('sandbox', config('payments.gateways.cashfree.mode'));
    }

    /** @test */
    public function cashfree_api_version_is_correct(): void
    {
        $this->assertEquals('2023-08-01', config('payments.gateways.cashfree.api_version'));
    }

    /** @test */
    public function cashfree_is_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('payments.gateways.cashfree.enabled'));
    }

    /** @test */
    public function razorpay_is_enabled_by_default(): void
    {
        $this->assertTrue((bool) config('payments.gateways.razorpay.enabled'));
    }

    /** @test */
    public function cashfree_is_in_supported_gateways(): void
    {
        $this->assertContains('cashfree', config('payments.supported_gateways'));
    }

    // =========================================================================
    // B. PaymentManager Resolution Tests
    // =========================================================================

    /** @test */
    public function payment_manager_resolves_razorpay_gateway(): void
    {
        $manager = app(PaymentManager::class);
        $gateway = $manager->getGateway('razorpay');
        $this->assertInstanceOf(RazorpayGateway::class, $gateway);
    }

    /** @test */
    public function payment_manager_resolves_cashfree_gateway(): void
    {
        $manager = app(PaymentManager::class);
        $gateway = $manager->getGateway('cashfree');
        $this->assertInstanceOf(CashfreeGateway::class, $gateway);
    }

    /** @test */
    public function payment_manager_throws_for_unknown_gateway(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not supported/');

        app(PaymentManager::class)->getGateway('stripe');
    }

    // =========================================================================
    // C. CashfreeGateway Unit Tests
    // =========================================================================

    /** @test */
    public function cashfree_gateway_name_returns_cashfree(): void
    {
        $gateway = app(CashfreeGateway::class);
        $this->assertEquals('cashfree', $gateway->gatewayName());
    }

    /** @test */
    public function cashfree_gateway_name_matches_constant(): void
    {
        $gateway = app(CashfreeGateway::class);
        $this->assertEquals(PaymentGateway::CASHFREE, $gateway->gatewayName());
    }

    /** @test */
    public function cashfree_create_order_throws_credential_error_when_unconfigured(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/credentials are not configured/');

        $payment = Payment::create([
            'amount'   => 1000.00,
            'currency' => 'INR',
            'status'   => PaymentStatus::INITIATED,
        ]);

        // Phase 2 config has null client_id/secret — createOrder now correctly
        // throws a credentials RuntimeException instead of the old placeholder message
        app(CashfreeGateway::class)->createOrder($payment);
    }



    /** @test */
    public function cashfree_handle_webhook_returns_failed_result_when_secret_missing(): void
    {
        $result = app(CashfreeGateway::class)->handleWebhook(['type' => 'PAYMENT_SUCCESS_WEBHOOK']);
        $this->assertFalse($result->success);
        $this->assertEquals('cashfree_webhook_secret_missing', $result->failureCode);
    }

    // =========================================================================
    // D. extractIdentifiers() Tests
    // =========================================================================

    /** @test */
    public function extract_identifiers_returns_normalized_keys_on_empty_payload(): void
    {
        $gateway = app(CashfreeGateway::class);
        $result  = $gateway->extractIdentifiers([]);

        $this->assertArrayHasKey('internal_payment_id', $result);
        $this->assertArrayHasKey('gateway_order_id', $result);
        $this->assertArrayHasKey('gateway_payment_id', $result);
        $this->assertArrayHasKey('gateway_event_id', $result);
        $this->assertArrayHasKey('event_type', $result);

        $this->assertNull($result['internal_payment_id']);
        $this->assertNull($result['gateway_order_id']);
        $this->assertNull($result['gateway_payment_id']);
        $this->assertNull($result['gateway_event_id']);
        $this->assertNull($result['event_type']);
    }

    /** @test */
    public function extract_identifiers_does_not_throw_on_malformed_payload(): void
    {
        $gateway = app(CashfreeGateway::class);

        // Should not throw regardless of weird payload shapes
        $result = $gateway->extractIdentifiers(['unexpected_key' => ['nested' => 'value']]);
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
    }

    /** @test */
    public function extract_identifiers_parses_basic_cashfree_test_payload(): void
    {
        $gateway = app(CashfreeGateway::class);

        $payload = [
            'order_id'      => 'order_123',
            'cf_payment_id' => 'pay_123',
            'event_id'      => 'event_123',
            'type'          => 'PAYMENT_SUCCESS_WEBHOOK',
        ];

        $result = $gateway->extractIdentifiers($payload);

        $this->assertEquals('order_123', $result['gateway_order_id']);
        $this->assertEquals('pay_123', $result['gateway_payment_id']);
        $this->assertEquals('event_123', $result['gateway_event_id']);
        $this->assertEquals('PAYMENT_SUCCESS_WEBHOOK', $result['event_type']);
    }

    /** @test */
    public function extract_identifiers_supports_cf_order_id_alias(): void
    {
        $gateway = app(CashfreeGateway::class);

        $result = $gateway->extractIdentifiers(['cf_order_id' => 'cf_ord_abc']);
        $this->assertEquals('cf_ord_abc', $result['gateway_order_id']);
    }

    /** @test */
    public function extract_identifiers_supports_nested_data_keys(): void
    {
        $gateway = app(CashfreeGateway::class);

        $result = $gateway->extractIdentifiers([
            'data' => [
                'order'   => ['order_id' => 'nested_order_456'],
                'payment' => ['cf_payment_id' => 'nested_pay_789'],
            ],
        ]);

        $this->assertEquals('nested_order_456', $result['gateway_order_id']);
        $this->assertEquals('nested_pay_789', $result['gateway_payment_id']);
    }

    /** @test */
    public function extract_identifiers_supports_event_type_alias(): void
    {
        $gateway = app(CashfreeGateway::class);

        $result1 = $gateway->extractIdentifiers(['event_type' => 'PAYMENT_FAILED_WEBHOOK']);
        $this->assertEquals('PAYMENT_FAILED_WEBHOOK', $result1['event_type']);

        $result2 = $gateway->extractIdentifiers(['event' => 'order.paid']);
        $this->assertEquals('order.paid', $result2['event_type']);
    }

    // =========================================================================
    // E. PaymentManager Cashfree Placeholder Handling
    // =========================================================================

    /** @test */
    public function initiating_payment_via_cashfree_with_no_credentials_rethrows_config_error(): void
    {
        // Phase 2 config has no client_id/secret.
        // PaymentManager rethrows credentials RuntimeException as a hard config error.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/credentials are not configured/');

        $manager = app(PaymentManager::class);
        $manager->initiatePayment(
            payable: null,
            amount: 500.00,
            purpose: 'test_cashfree_phase2',
            user: null,
            gateway: 'cashfree',
        );
    }

    // =========================================================================
    // F. Razorpay Regression Tests
    // =========================================================================

    /** @test */
    public function razorpay_gateway_resolves_correctly_after_cashfree_addition(): void
    {
        $manager = app(PaymentManager::class);
        $gateway = $manager->getGateway('razorpay');

        $this->assertInstanceOf(RazorpayGateway::class, $gateway);
        $this->assertEquals('razorpay', $gateway->gatewayName());
    }

    /** @test */
    public function razorpay_verify_payment_still_works_with_valid_signature(): void
    {
        $keySecret = 'rzp_test_secret_abc';
        $orderId   = 'order_REG001';
        $paymentId = 'pay_REG001';
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

        $gateway = app(RazorpayGateway::class);
        $result  = $gateway->verifyPayment($payment, $data);

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::PAID, $result->status);
        $this->assertEquals('razorpay', $result->gateway);
    }
}
