<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop\UserAddress;
use App\Models\Shop\ShopOrder;
use App\Models\Payment;
use App\Services\Shop\CheckoutService;
use App\Services\Payments\PaymentManager;
use App\Support\Payments\PaymentPurpose;
use App\Support\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery\MockInterface;

class PaymentsPhase6Test extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected string $token;
    protected UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
        $this->address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        // Default configurations
        config([
            'payments.default_gateway' => 'razorpay',
            'payments.supported_gateways' => ['razorpay', 'cashfree'],
            'payments.gateways.razorpay' => [
                'driver'  => \App\Services\Payments\Gateways\RazorpayGateway::class,
                'enabled' => true,
            ],
            'payments.gateways.cashfree' => [
                'driver'  => \App\Services\Payments\Gateways\CashfreeGateway::class,
                'enabled' => false,
            ],
        ]);
    }

    // =========================================================================
    // 1. Gateway Options Endpoint tests
    // =========================================================================

    /** @test */
    public function test_gateways_options_endpoint_respects_cashfree_disabled()
    {
        config(['payments.gateways.cashfree.enabled' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson('/api/v1/payments/gateways');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Payment gateways retrieved successfully.',
                     'data' => [
                         'default_gateway' => 'razorpay',
                         'gateways' => [
                             [
                                 'key' => 'razorpay',
                                 'label' => 'Razorpay',
                                 'enabled' => true,
                             ]
                         ]
                     ]
                 ]);

        // Ensure cashfree is not present
        $this->assertCount(1, $response->json('data.gateways'));
    }

    /** @test */
    public function test_gateways_options_endpoint_respects_cashfree_enabled()
    {
        config(['payments.gateways.cashfree.enabled' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson('/api/v1/payments/gateways');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'default_gateway' => 'razorpay',
                         'gateways' => [
                             [
                                 'key' => 'razorpay',
                                 'label' => 'Razorpay',
                                 'enabled' => true,
                             ],
                             [
                                 'key' => 'cashfree',
                                 'label' => 'Cashfree',
                                 'enabled' => true,
                             ]
                         ]
                     ]
                 ]);

        $this->assertCount(2, $response->json('data.gateways'));
    }

    /** @test */
    public function test_gateways_options_endpoint_with_include_disabled_flag()
    {
        config(['payments.gateways.cashfree.enabled' => false]);

        // Request with include_disabled = true
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson('/api/v1/payments/gateways?include_disabled=1');

        $response->assertStatus(200);
        $gateways = $response->json('data.gateways');
        $this->assertCount(2, $gateways);

        $cashfreeOpt = collect($gateways)->firstWhere('key', 'cashfree');
        $this->assertNotNull($cashfreeOpt);
        $this->assertFalse($cashfreeOpt['enabled']);
    }

    // =========================================================================
    // 2. Checkout Validation & Exception Formatting tests
    // =========================================================================

    /** @test */
    public function test_checkout_validation_rejects_unsupported_gateway()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/checkout/place-order', [
                             'shipping_address_id' => $this->address->id,
                             'payment_gateway' => 'stripe' // unsupported
                         ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid payment gateway selected.',
                     'data' => null,
                     'errors' => [
                         'payment_gateway' => ['Invalid payment gateway selected.']
                     ]
                 ]);
    }

    /** @test */
    public function test_checkout_validation_rejects_disabled_gateway()
    {
        config(['payments.gateways.cashfree.enabled' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/checkout/place-order', [
                             'shipping_address_id' => $this->address->id,
                             'payment_gateway' => 'cashfree' // disabled
                         ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Selected payment gateway is not available.',
                     'data' => null,
                     'errors' => [
                         'payment_gateway' => ['Cashfree is not available for checkout yet.']
                     ]
                 ]);
    }

    /** @test */
    public function test_checkout_validation_falls_back_to_default_gateway_if_none_provided()
    {
        $mockOrder = new ShopOrder([
            'id' => 123,
            'order_number' => 'ORD-123456',
            'total_amount' => 100.00,
            'status' => 'pending_payment',
        ]);

        $this->mock(CheckoutService::class, function (MockInterface $mock) use ($mockOrder) {
            $mock->shouldReceive('placeOrder')
                 ->once()
                 ->andReturn($mockOrder);
        });

        // Razorpay is default
        $this->mock(PaymentManager::class, function (MockInterface $mock) use ($mockOrder) {
            $mock->shouldReceive('initiatePayment')
                 ->once()
                 ->with(
                     \Mockery::on(fn($payable) => $payable->id === $mockOrder->id),
                     100.00,
                     'shop_order',
                     \Mockery::on(fn($user) => $user->id === $this->user->id),
                     'razorpay' // Assert default fallback
                 )
                 ->andReturn([
                     'payment' => (object)[
                         'id' => 999,
                         'gateway' => 'razorpay',
                         'status' => 'pending',
                         'amount' => 100.00,
                         'currency' => 'INR',
                         'purpose' => 'shop_order',
                     ],
                     'checkout' => [
                         'gateway' => 'razorpay',
                         'key' => 'rzp_test_key',
                         'amount' => 10000,
                     ]
                 ]);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/checkout/place-order', [
                             'shipping_address_id' => $this->address->id,
                             // payment_gateway is omitted
                         ]);

        $response->assertStatus(201);
    }

    // =========================================================================
    // 3. Unified Checkout Response Structure tests
    // =========================================================================

    /** @test */
    public function test_checkout_response_structure_for_razorpay()
    {
        $mockOrder = new ShopOrder([
            'id' => 124,
            'order_number' => 'ORD-RZP',
            'total_amount' => 150.00,
            'status' => 'pending_payment',
        ]);

        $this->mock(CheckoutService::class, function (MockInterface $mock) use ($mockOrder) {
            $mock->shouldReceive('placeOrder')->once()->andReturn($mockOrder);
        });

        $this->mock(PaymentManager::class, function (MockInterface $mock) use ($mockOrder) {
            $mock->shouldReceive('initiatePayment')
                 ->once()
                 ->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::any(), 'razorpay')
                 ->andReturn([
                     'payment' => (object)[
                         'id' => 999,
                         'gateway' => 'razorpay',
                         'status' => 'initiated',
                         'amount' => 150.00,
                         'currency' => 'INR',
                         'purpose' => 'shop_order',
                     ],
                     'checkout' => [
                         'gateway' => 'razorpay',
                         'key' => 'rzp_test_key',
                         'amount' => 15000, // paise
                         'currency' => 'INR',
                         'order_id' => 'order_rzp_mock',
                     ]
                 ]);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/checkout/place-order', [
                             'shipping_address_id' => $this->address->id,
                             'payment_gateway' => 'razorpay'
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'order_number',
                         'payment' => [
                             'id',
                             'gateway',
                             'status',
                             'amount',
                             'currency',
                             'purpose',
                             'verify_endpoint',
                             'checkout' => [
                                 'gateway',
                                 'key',
                                 'amount',
                                 'currency',
                                 'order_id',
                             ]
                         ]
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         'payment' => [
                             'gateway' => 'razorpay',
                             'amount' => 150.00,
                             'verify_endpoint' => url('/api/v1/payments/razorpay/verify'),
                             'checkout' => [
                                 'gateway' => 'razorpay',
                                 'amount' => 15000, // paise
                             ]
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function test_checkout_response_structure_for_cashfree()
    {
        config(['payments.gateways.cashfree.enabled' => true]);

        $mockOrder = new ShopOrder([
            'id' => 125,
            'order_number' => 'ORD-CF',
            'total_amount' => 250.00,
            'status' => 'pending_payment',
        ]);

        $this->mock(CheckoutService::class, function (MockInterface $mock) use ($mockOrder) {
            $mock->shouldReceive('placeOrder')->once()->andReturn($mockOrder);
        });

        $this->mock(PaymentManager::class, function (MockInterface $mock) use ($mockOrder) {
            $mock->shouldReceive('initiatePayment')
                 ->once()
                 ->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::any(), 'cashfree')
                 ->andReturn([
                     'payment' => (object)[
                         'id' => 888,
                         'gateway' => 'cashfree',
                         'status' => 'initiated',
                         'amount' => 250.00,
                         'currency' => 'INR',
                         'purpose' => 'shop_order',
                     ],
                     'checkout' => [
                         'gateway' => 'cashfree',
                         'cf_order_id' => 'cf_order_mock',
                         'payment_session_id' => 'session_mock',
                         'environment' => 'sandbox'
                     ]
                 ]);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/checkout/place-order', [
                             'shipping_address_id' => $this->address->id,
                             'payment_gateway' => 'cashfree'
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'order_number',
                         'payment' => [
                             'id',
                             'gateway',
                             'status',
                             'amount',
                             'currency',
                             'purpose',
                             'verify_endpoint',
                             'checkout' => [
                                 'gateway',
                                 'cf_order_id',
                                 'payment_session_id',
                                 'environment'
                             ]
                         ]
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         'payment' => [
                             'gateway' => 'cashfree',
                             'amount' => 250.00,
                             'verify_endpoint' => url('/api/v1/payments/cashfree/verify'),
                             'checkout' => [
                                 'gateway' => 'cashfree',
                             ]
                         ]
                     ]
                 ]);
    }

    // =========================================================================
    // 4. Generic Web Pay/Retry Routing restrictions
    // =========================================================================

    /** @test */
    public function test_generic_web_pay_redirects_to_failed_when_gateway_disabled()
    {
        config(['payments.gateways.cashfree.enabled' => false]);

        $payment = Payment::create([
            'user_id' => $this->user->id,
            'amount' => 100.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'cashfree', // cashfree is disabled
            'purpose' => 'shop_order',
        ]);

        $response = $this->actingAs($this->user)
                         ->get("/payments/{$payment->id}/pay");

        // Should redirect to payments.failed
        $response->assertRedirect(route('payments.failed', ['payment_id' => $payment->id]));
        $response->assertSessionHas('error', 'Selected payment gateway is not available.');
    }

    /** @test */
    public function test_generic_web_retry_redirects_to_failed_when_gateway_disabled()
    {
        config(['payments.gateways.cashfree.enabled' => false]);

        $payment = Payment::create([
            'user_id' => $this->user->id,
            'amount' => 100.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'cashfree', // cashfree is disabled
            'purpose' => 'shop_order',
        ]);

        $response = $this->actingAs($this->user)
                         ->get("/payments/{$payment->id}/retry");

        $response->assertRedirect(route('payments.failed', ['payment_id' => $payment->id]));
        $response->assertSessionHas('error', 'Selected payment gateway is not available.');
    }
}
