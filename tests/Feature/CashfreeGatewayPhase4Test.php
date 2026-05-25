<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\UserAddress;
use App\Support\Payments\PaymentPurpose;
use App\Support\Payments\PaymentStatus;
use App\Services\Payments\Gateways\CashfreeGateway;
use App\Services\Payments\Gateways\RazorpayGateway;
use App\Services\Payments\DTO\PaymentVerificationData;
use App\Services\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CashfreeGatewayPhase4Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected UserAddress $address;
    protected string $clientId = 'cf_test_client_id_123';
    protected string $clientSecret = 'cf_test_client_secret_xyz';

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
                'enabled'        => true,
                'client_id'      => $this->clientId,
                'client_secret'  => $this->clientSecret,
                'webhook_secret' => 'cf_webhook_secret_abc',
                'mode'           => 'sandbox',
                'api_version'    => '2023-08-01',
                'return_url'     => null,
                'notify_url'     => null,
            ],
            'payments.supported_gateways' => ['razorpay', 'cashfree'],
            'payments.default_gateway'    => 'razorpay',
        ]);

        $this->user = User::factory()->create();
        $this->address = UserAddress::factory()->create(['user_id' => $this->user->id]);
    }

    // =========================================================================
    // A. CashfreeGateway unit/integration verification
    // =========================================================================

    /** @test */
    public function test_gateway_verification_success_when_order_is_paid()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY100',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY100' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY100',
                'order_status'   => 'PAID',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
                'cf_payment_id'  => 'cf_payment_abc123',
            ], 200),
        ]);

        $gateway = app(CashfreeGateway::class);
        $data = new PaymentVerificationData(
            gateway: 'cashfree',
            gatewayOrderId: 'ECCPAY100'
        );

        $result = $gateway->verifyPayment($payment, $data);

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::PAID, $result->status);
        $this->assertEquals('cf_payment_abc123', $result->gatewayPaymentId);
    }

    /** @test */
    public function test_gateway_verification_pending_when_order_is_active()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY101',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY101' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY101',
                'order_status'   => 'ACTIVE',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
            ], 200),
        ]);

        $gateway = app(CashfreeGateway::class);
        $data = new PaymentVerificationData(
            gateway: 'cashfree',
            gatewayOrderId: 'ECCPAY101'
        );

        $result = $gateway->verifyPayment($payment, $data);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::PENDING, $result->status);
    }

    /** @test */
    public function test_gateway_verification_failed_when_order_failed_or_cancelled()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY102',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY102' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY102',
                'order_status'   => 'FAILED',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
            ], 200),
        ]);

        $gateway = app(CashfreeGateway::class);
        $data = new PaymentVerificationData(
            gateway: 'cashfree',
            gatewayOrderId: 'ECCPAY102'
        );

        $result = $gateway->verifyPayment($payment, $data);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
    }

    /** @test */
    public function test_gateway_verification_fails_on_amount_mismatch()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY103',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY103' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY103',
                'order_status'   => 'PAID',
                'order_amount'   => 900.00, // mismatch
                'order_currency' => 'INR',
            ], 200),
        ]);

        $gateway = app(CashfreeGateway::class);
        $data = new PaymentVerificationData(
            gateway: 'cashfree',
            gatewayOrderId: 'ECCPAY103'
        );

        $result = $gateway->verifyPayment($payment, $data);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
        $this->assertEquals('amount_or_currency_mismatch', $result->failureCode);
    }

    /** @test */
    public function test_gateway_verification_fails_on_currency_mismatch()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY104',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY104' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY104',
                'order_status'   => 'PAID',
                'order_amount'   => 1000.00,
                'order_currency' => 'USD', // mismatch
            ], 200),
        ]);

        $gateway = app(CashfreeGateway::class);
        $data = new PaymentVerificationData(
            gateway: 'cashfree',
            gatewayOrderId: 'ECCPAY104'
        );

        $result = $gateway->verifyPayment($payment, $data);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
        $this->assertEquals('amount_or_currency_mismatch', $result->failureCode);
    }

    /** @test */
    public function test_gateway_verification_fails_on_404_not_found()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY105',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY105' => Http::response([
                'code'    => 'order_not_found',
                'message' => 'Order not found.',
            ], 404),
        ]);

        $gateway = app(CashfreeGateway::class);
        $data = new PaymentVerificationData(
            gateway: 'cashfree',
            gatewayOrderId: 'ECCPAY105'
        );

        $result = $gateway->verifyPayment($payment, $data);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
        $this->assertEquals('order_not_found', $result->failureCode);
    }

    /** @test */
    public function test_gateway_verification_fails_on_401_auth_error()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY106',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY106' => Http::response([
                'code'    => 'authentication_failed',
                'message' => 'Authentication failed.',
            ], 401),
        ]);

        $gateway = app(CashfreeGateway::class);
        $data = new PaymentVerificationData(
            gateway: 'cashfree',
            gatewayOrderId: 'ECCPAY106'
        );

        $result = $gateway->verifyPayment($payment, $data);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::FAILED, $result->status);
        $this->assertEquals('gateway_auth_error', $result->failureCode);
    }

    // =========================================================================
    // B. Web Controller verification tests
    // =========================================================================

    /** @test */
    public function test_web_verification_ajax_success()
    {
        $order = ShopOrder::create([
            'user_id'                   => $this->user->id,
            'order_number'              => 'ORD-CF-100',
            'status'                    => 'pending_payment',
            'payment_status'            => 'unpaid',
            'currency'                  => 'INR',
            'subtotal'                  => 1000.00,
            'total_amount'              => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot'  => $this->address->toArray(),
            'placed_at'                 => now(),
        ]);

        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'payable_type'     => ShopOrder::class,
            'payable_id'       => $order->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY200',
            'purpose'          => 'shop_order',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY200' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY200',
                'order_status'   => 'PAID',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
                'cf_payment_id'  => 'cf_payment_abc123',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('payments.cashfree.verify'), [
            'payment_id'  => $payment->id,
            'order_id'    => 'ECCPAY200',
            'cf_order_id' => 'cf_order_9999',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'      => true,
            'redirect_url' => route('shop.order-success', ['orderId' => $order->id]),
        ]);

        $this->assertEquals(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    /** @test */
    public function test_web_verification_ajax_pending()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY201',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY201' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY201',
                'order_status'   => 'ACTIVE',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('payments.cashfree.verify'), [
            'payment_id' => $payment->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status'  => 'pending',
        ]);

        $this->assertEquals(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    /** @test */
    public function test_web_verification_ajax_unauthorized_if_different_user()
    {
        $anotherUser = User::factory()->create();

        $payment = Payment::create([
            'user_id'          => $anotherUser->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY202',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('payments.cashfree.verify'), [
            'payment_id' => $payment->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_web_return_redirect_success()
    {
        $order = ShopOrder::create([
            'user_id'                   => $this->user->id,
            'order_number'              => 'ORD-CF-101',
            'status'                    => 'pending_payment',
            'payment_status'            => 'unpaid',
            'currency'                  => 'INR',
            'subtotal'                  => 1000.00,
            'total_amount'              => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot'  => $this->address->toArray(),
            'placed_at'                 => now(),
        ]);

        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'payable_type'     => ShopOrder::class,
            'payable_id'       => $order->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY300',
            'purpose'          => 'shop_order',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY300' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY300',
                'order_status'   => 'PAID',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
                'cf_payment_id'  => 'cf_payment_abc123',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->get(route('payments.cashfree.return', $payment->id));

        $response->assertRedirect(route('shop.order-success', ['orderId' => $order->id]));

        $this->assertEquals(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->status);
    }

    /** @test */
    public function test_web_return_redirect_pending()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY301',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY301' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY301',
                'order_status'   => 'ACTIVE',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->get(route('payments.cashfree.return', $payment->id));

        $response->assertRedirect(route('payments.cashfree.pay', $payment->id));
        $this->assertEquals(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    /** @test */
    public function test_web_return_redirect_failed()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY302',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY302' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY302',
                'order_status'   => 'FAILED',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->get(route('payments.cashfree.return', $payment->id));

        $response->assertRedirect(route('payments.failed', ['payment_id' => $payment->id]));
        $this->assertEquals(PaymentStatus::FAILED, $payment->fresh()->status);
    }

    // =========================================================================
    // C. API Controller verification tests
    // =========================================================================

    /** @test */
    public function test_api_verification_success()
    {
        $order = ShopOrder::create([
            'user_id'                   => $this->user->id,
            'order_number'              => 'ORD-CF-102',
            'status'                    => 'pending_payment',
            'payment_status'            => 'unpaid',
            'currency'                  => 'INR',
            'subtotal'                  => 1000.00,
            'total_amount'              => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot'  => $this->address->toArray(),
            'placed_at'                 => now(),
        ]);

        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'payable_type'     => ShopOrder::class,
            'payable_id'       => $order->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY400',
            'purpose'          => 'shop_order',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY400' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY400',
                'order_status'   => 'PAID',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
                'cf_payment_id'  => 'cf_payment_abc123',
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/shop/payments/cashfree/verify', [
            'payment_id'  => $payment->id,
            'order_id'    => 'ECCPAY400',
            'cf_order_id' => 'cf_order_9999',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'payment' => [
                    'id'               => $payment->id,
                    'status'           => 'paid',
                    'gateway_payment_id'=> 'cf_payment_abc123',
                ]
            ]
        ]);

        $this->assertEquals(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->status);
    }

    /** @test */
    public function test_api_verification_pending()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY401',
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY401' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY401',
                'order_status'   => 'ACTIVE',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/shop/payments/cashfree/verify', [
            'payment_id' => $payment->id,
        ]);

        $response->assertStatus(202);
        $response->assertJson([
            'success' => false,
            'message' => 'Payment is still pending.',
        ]);

        $this->assertEquals(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    /** @test */
    public function test_api_verification_failed()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY402',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
        ]);

        Http::fake([
            'sandbox.cashfree.com/pg/orders/ECCPAY402' => Http::response([
                'cf_order_id'    => 'cf_order_9999',
                'order_id'       => 'ECCPAY402',
                'order_status'   => 'FAILED',
                'order_amount'   => 1000.00,
                'order_currency' => 'INR',
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/shop/payments/cashfree/verify', [
            'payment_id' => $payment->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $this->assertEquals(PaymentStatus::FAILED, $payment->fresh()->status);
    }

    /** @test */
    public function test_api_verification_unauthorized()
    {
        $anotherUser = User::factory()->create();

        $payment = Payment::create([
            'user_id'          => $anotherUser->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY403',
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/shop/payments/cashfree/verify', [
            'payment_id' => $payment->id,
        ]);

        $response->assertStatus(403);
    }
}
