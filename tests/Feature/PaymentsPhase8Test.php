<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\MembershipTier;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\VaultRemovalRequest;
use App\Models\Archive\ArchiveProduct;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\UserAddress;
use App\Support\Payments\PaymentPurpose;
use App\Support\Payments\PaymentStatus;
use App\Services\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentsPhase8Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected UserAddress $address;
    protected string $webhookSecret = 'rzp_webhook_secret_xyz';

    protected function setUp(): void
    {
        parent::setUp();

        // Configure credentials for Razorpay in tests
        config([
            'payments.gateways.razorpay' => [
                'key_id' => 'rzp_test_key_123',
                'key_secret' => 'rzp_test_secret_abc',
                'webhook_secret' => $this->webhookSecret,
                'mode' => 'test',
            ]
        ]);

        $this->user = User::factory()->create();
        $this->address = UserAddress::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * Helper to create an unsigned/signed webhook payload and call the webhook endpoint.
     */
    protected function postWebhook(array $payload, ?string $signature = null)
    {
        $rawBody = json_encode($payload);
        if ($signature === null) {
            $signature = hash_hmac('sha256', $rawBody, $this->webhookSecret);
        }

        return $this->postJson('/webhooks/razorpay', $payload, [
            'X-Razorpay-Signature' => $signature,
        ]);
    }

    /** @test */
    public function test_successful_web_shop_checkout_payment_flow_and_signature_verification()
    {
        $order = ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-WEB-001',
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'currency' => 'INR',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot' => $this->address->toArray(),
            'placed_at' => now(),
        ]);

        // Mock Razorpay order creation response
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_web123',
                'entity' => 'order',
                'amount' => 100000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentManager = app(PaymentManager::class);
        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $order,
            amount: 1000.00,
            purpose: 'shop_order',
            user: $this->user,
            gateway: 'razorpay'
        );

        $payment = $paymentInitiation['payment'];
        $gatewayOrderId = $payment->gateway_order_id;

        $this->assertEquals(PaymentStatus::PENDING, $payment->status);
        $this->assertEquals('order_web123', $gatewayOrderId);

        // Verify web verify endpoint
        $gatewayPaymentId = 'pay_web999';
        $signature = hash_hmac('sha256', "{$gatewayOrderId}|{$gatewayPaymentId}", 'rzp_test_secret_abc');

        $response = $this->actingAs($this->user)->postJson(route('payments.razorpay.verify'), [
            'internal_payment_id' => $payment->id,
            'razorpay_order_id' => $gatewayOrderId,
            'razorpay_payment_id' => $gatewayPaymentId,
            'razorpay_signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $payment->refresh();
        $order->refresh();

        $this->assertTrue($payment->isPaid());
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('paid', $order->status);
    }

    /** @test */
    public function test_failed_checkout_payment_flow_and_retry_flow()
    {
        $order = ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-WEB-002',
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'currency' => 'INR',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot' => $this->address->toArray(),
            'placed_at' => now(),
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_web124',
                'entity' => 'order',
                'amount' => 100000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentManager = app(PaymentManager::class);
        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $order,
            amount: 1000.00,
            purpose: 'shop_order',
            user: $this->user,
            gateway: 'razorpay'
        );

        $payment1 = $paymentInitiation['payment'];
        $this->assertCount(1, $order->payments);

        // Verify with invalid signature to simulate failure
        $response = $this->actingAs($this->user)->postJson(route('payments.razorpay.verify'), [
            'internal_payment_id' => $payment1->id,
            'razorpay_order_id' => $payment1->gateway_order_id,
            'razorpay_payment_id' => 'pay_web998',
            'razorpay_signature' => 'invalid_signature_here',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => false]);

        $payment1->refresh();
        $this->assertTrue($payment1->isFailed());
        $this->assertEquals('pending_payment', $order->fresh()->status);

        // Re-initiate payment using the retry route
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_web125',
                'entity' => 'order',
                'amount' => 100000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $retryResponse = $this->actingAs($this->user)->get(route('payments.razorpay.retry', $payment1->id));

        // The retry endpoint redirects back to the pay page of the newly initiated payment
        $order->refresh();
        $this->assertCount(2, $order->payments);

        $payment2 = $order->payments()->orderBy('id', 'desc')->first();
        $this->assertNotEquals($payment1->id, $payment2->id);
        $this->assertEquals(PaymentStatus::PENDING, $payment2->status);

        $retryResponse->assertRedirect(route('payments.razorpay.pay', $payment2->id));
    }

    /** @test */
    public function test_invalid_verification_signature_rejection()
    {
        $order = ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-WEB-003',
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'currency' => 'INR',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot' => $this->address->toArray(),
            'placed_at' => now(),
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_web126',
                'entity' => 'order',
                'amount' => 100000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentManager = app(PaymentManager::class);
        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $order,
            amount: 1000.00,
            purpose: 'shop_order',
            user: $this->user,
            gateway: 'razorpay'
        );

        $payment = $paymentInitiation['payment'];

        // Web verification with incorrect signature
        $webResponse = $this->actingAs($this->user)->postJson(route('payments.razorpay.verify'), [
            'internal_payment_id' => $payment->id,
            'razorpay_order_id' => $payment->gateway_order_id,
            'razorpay_payment_id' => 'pay_web999',
            'razorpay_signature' => 'wrong_signature_123',
        ]);
        $webResponse->assertStatus(200);
        $webResponse->assertJson(['success' => false]);
        $this->assertTrue($payment->fresh()->isFailed());

        // Reset payment to pending
        $payment->update(['status' => PaymentStatus::PENDING]);

        // API verification with incorrect signature
        $apiResponse = $this->actingAs($this->user, 'api')->postJson('/api/v1/shop/payments/razorpay/verify', [
            'payment_id' => $payment->id,
            'razorpay_order_id' => $payment->gateway_order_id,
            'razorpay_payment_id' => 'pay_web999',
            'razorpay_signature' => 'wrong_signature_456',
        ]);
        $apiResponse->assertStatus(422);
        $this->assertTrue($payment->fresh()->isFailed());
    }

    /** @test */
    public function test_invalid_webhook_signature_rejection()
    {
        $order = ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-WEB-004',
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'currency' => 'INR',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot' => $this->address->toArray(),
            'placed_at' => now(),
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_web127',
                'entity' => 'order',
                'amount' => 100000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentManager = app(PaymentManager::class);
        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $order,
            amount: 1000.00,
            purpose: 'shop_order',
            user: $this->user,
            gateway: 'razorpay'
        );

        $payment = $paymentInitiation['payment'];

        $payload = [
            'id' => 'evt_captured_999',
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_web999',
                        'entity' => 'payment',
                        'amount' => 100000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'order_id' => $payment->gateway_order_id,
                        'notes' => [
                            'internal_payment_id' => (string) $payment->id,
                        ],
                    ],
                ],
            ],
        ];

        // Call the webhook endpoint with an invalid signature
        $response = $this->postWebhook($payload, 'totally_invalid_signature');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid webhook signature.',
        ]);

        $payment->refresh();
        $order->refresh();

        // Verify the payment status was NOT modified to failed or paid because signature failed
        $this->assertEquals(PaymentStatus::PENDING, $payment->status);
        $this->assertEquals('unpaid', $order->payment_status);

        // Verify the event was logged with signature_valid = false
        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id,
            'event_type' => 'payment.captured',
            'signature_valid' => 0,
        ]);
    }

    /** @test */
    public function test_duplicate_idempotency_webhook_checks()
    {
        $order = ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-WEB-005',
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'currency' => 'INR',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot' => $this->address->toArray(),
            'placed_at' => now(),
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_web128',
                'entity' => 'order',
                'amount' => 100000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentManager = app(PaymentManager::class);
        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $order,
            amount: 1000.00,
            purpose: 'shop_order',
            user: $this->user,
            gateway: 'razorpay'
        );

        $payment = $paymentInitiation['payment'];

        $payload = [
            'id' => 'evt_captured_555',
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_web999',
                        'entity' => 'payment',
                        'amount' => 100000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'order_id' => $payment->gateway_order_id,
                        'notes' => [
                            'internal_payment_id' => (string) $payment->id,
                        ],
                    ],
                ],
            ],
        ];

        // 1. Send the first webhook event
        $response1 = $this->postWebhook($payload);
        $response1->assertStatus(200);

        $payment->refresh();
        $order->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertEquals('paid', $order->payment_status);

        // 2. Send the second webhook (duplicate)
        $response2 = $this->postWebhook($payload);
        $response2->assertStatus(200);

        // Ensure status is still paid and no errors occurred
        $payment->refresh();
        $order->refresh();
        $this->assertTrue($payment->isPaid());

        // 3. Send a failed webhook event for this payment and verify protection against downgrade
        $failPayload = [
            'id' => 'evt_failed_555',
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_web999',
                        'entity' => 'payment',
                        'amount' => 100000,
                        'currency' => 'INR',
                        'status' => 'failed',
                        'error_code' => 'payment_failed',
                        'error_description' => 'Late failure description',
                        'order_id' => $payment->gateway_order_id,
                        'notes' => [
                            'internal_payment_id' => (string) $payment->id,
                        ],
                    ],
                ],
            ],
        ];

        $response3 = $this->postWebhook($failPayload);
        $response3->assertStatus(200);

        // Verify status remains PAID
        $payment->refresh();
        $order->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertEquals('paid', $order->payment_status);
    }

    /** @test */
    public function test_closed_browser_simulation()
    {
        $order = ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-WEB-006',
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'currency' => 'INR',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot' => $this->address->toArray(),
            'placed_at' => now(),
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_web129',
                'entity' => 'order',
                'amount' => 100000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentManager = app(PaymentManager::class);
        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $order,
            amount: 1000.00,
            purpose: 'shop_order',
            user: $this->user,
            gateway: 'razorpay'
        );

        $payment = $paymentInitiation['payment'];

        // Simulate user closing the browser (no call to verify controller)
        // Webhook comes directly from Razorpay
        $payload = [
            'id' => 'evt_captured_777',
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_web999',
                        'entity' => 'payment',
                        'amount' => 100000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'order_id' => $payment->gateway_order_id,
                        'notes' => [
                            'internal_payment_id' => (string) $payment->id,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postWebhook($payload);
        $response->assertStatus(200);

        $payment->refresh();
        $order->refresh();

        // The order and payment should successfully transition to paid
        $this->assertTrue($payment->isPaid());
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('paid', $order->status);
    }

    /** @test */
    public function test_membership_upgrade_and_renewal_payment_flows()
    {
        $bronzeTier = MembershipTier::create([
            'name' => 'Bronze Tier',
            'code' => 'bronze_web',
            'level' => 1,
            'price' => 1000.00,
            'sort_order' => 1,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $goldTier = MembershipTier::create([
            'name' => 'Gold Tier',
            'code' => 'gold_web',
            'level' => 2,
            'price' => 5000.00,
            'sort_order' => 2,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        // Create Bronze membership for user
        $membership = Membership::create([
            'user_id' => $this->user->id,
            'membership_tier_id' => $bronzeTier->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);

        $upgradeApp = MembershipApplication::create([
            'user_id' => $this->user->id,
            'selected_tier_id' => $goldTier->id,
            'personal_details_json' => ['first_name' => 'John'],
            'cricket_profile_json' => [],
            'collector_intent_json' => [],
            'payment_status' => 'unpaid',
            'status' => 'draft',
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_upg999',
                'entity' => 'order',
                'amount' => 400000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentManager = app(PaymentManager::class);
        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $upgradeApp,
            amount: 4000.00,
            purpose: PaymentPurpose::MEMBERSHIP_UPGRADE,
            user: $this->user,
            gateway: 'razorpay'
        );

        $payment = $paymentInitiation['payment'];

        $payload = [
            'id' => 'evt_captured_upg',
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_upg999',
                        'entity' => 'payment',
                        'amount' => 400000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'order_id' => $payment->gateway_order_id,
                        'notes' => [
                            'internal_payment_id' => (string) $payment->id,
                        ],
                    ],
                ],
            ],
        ];

        // Send payment success webhook
        $response = $this->postWebhook($payload);
        $response->assertStatus(200);

        $this->user->refresh();
        $upgradeApp->refresh();

        // Verify membership upgraded
        $this->assertEquals($goldTier->id, $this->user->currentMembership->membership_tier_id);
        $this->assertEquals('upgrade_completed', $upgradeApp->status);
        $this->assertEquals('paid', $upgradeApp->payment_status);
    }

    /** @test */
    public function test_vault_delivery_payment_flow()
    {
        $product = ArchiveProduct::factory()->create([
            'title' => 'Test Item',
            'slug' => 'test-item',
        ]);

        $vaultItem = UserVaultItem::create([
            'user_id' => $this->user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'item_title' => $product->title,
            'quantity' => 1,
            'status' => 'locked',
        ]);

        $vaultRequest = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_PENDING,
            'delivery_fee' => 150.00,
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_vlt999',
                'entity' => 'order',
                'amount' => 15000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentManager = app(PaymentManager::class);
        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $vaultRequest,
            amount: 150.00,
            purpose: PaymentPurpose::VAULT_DELIVERY,
            user: $this->user,
            gateway: 'razorpay'
        );

        $payment = $paymentInitiation['payment'];

        $payload = [
            'id' => 'evt_captured_vlt',
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_vlt999',
                        'entity' => 'payment',
                        'amount' => 15000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'order_id' => $payment->gateway_order_id,
                        'notes' => [
                            'internal_payment_id' => (string) $payment->id,
                        ],
                    ],
                ],
            ],
        ];

        // Send payment success webhook
        $response = $this->postWebhook($payload);
        $response->assertStatus(200);

        $vaultRequest->refresh();

        // Assert Vault Removal Request is paid, status is pending (Pending Review), and no automatic shipment
        $this->assertEquals(VaultRemovalRequest::PAYMENT_PAID, $vaultRequest->payment_status);
        $this->assertEquals(VaultRemovalRequest::STATUS_PENDING, $vaultRequest->status);
        $this->assertNull($vaultRequest->shippingShipment);
    }
}
