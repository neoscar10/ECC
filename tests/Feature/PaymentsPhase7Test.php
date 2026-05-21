<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\MembershipTier;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\VaultRemovalRequest;
use App\Models\Archive\ArchiveProduct;
use App\Services\Payments\PaymentFinalizationService;
use App\Services\Payments\PaymentLedgerService;
use App\Services\Payments\PaymentManager;
use App\Support\Payments\PaymentPurpose;
use App\Support\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentsPhase7Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure credentials for Razorpay in tests
        config([
            'payments.gateways.razorpay' => [
                'key_id' => 'rzp_test_key_123',
                'key_secret' => 'rzp_test_secret_abc',
                'webhook_secret' => 'rzp_webhook_secret_xyz',
                'mode' => 'test',
            ]
        ]);
    }

    /** @test */
    public function test_api_membership_upgrade_payment_initiation_and_verification()
    {
        $user = User::factory()->create();

        // 1. Create two membership tiers with different price and sort order
        $bronzeTier = MembershipTier::create([
            'name' => 'Bronze Tier',
            'code' => 'bronze',
            'level' => 1,
            'price' => 1000.00,
            'sort_order' => 1,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $goldTier = MembershipTier::create([
            'name' => 'Gold Tier',
            'code' => 'gold',
            'level' => 2,
            'price' => 5000.00,
            'sort_order' => 2,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        // 2. Attach an active Bronze membership to the user
        $initialMembership = Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $bronzeTier->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);

        // Mock Razorpay order creation response
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_upg123',
                'entity' => 'order',
                'amount' => 400000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        // 3. Initiate payment via endpoint: POST /api/v1/membership/upgrade
        $response = $this->actingAs($user, 'api')->postJson('/api/v1/membership/upgrade', [
            'tier_id' => $goldTier->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'payment' => [
                    'id',
                    'gateway',
                    'status',
                    'amount',
                    'currency',
                    'gateway_order_id',
                    'checkout',
                ]
            ]
        ]);

        $paymentId = $response->json('data.payment.id');
        $gatewayOrderId = $response->json('data.payment.gateway_order_id');

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'user_id' => $user->id,
            'payable_type' => MembershipApplication::class,
            'purpose' => PaymentPurpose::MEMBERSHIP_UPGRADE,
            'status' => PaymentStatus::PENDING,
            'gateway_order_id' => $gatewayOrderId,
        ]);

        // 4. Verify the payment via signature verification endpoint: POST /api/v1/shop/payments/razorpay/verify
        $gatewayPaymentId = 'pay_upg999';
        $signature = hash_hmac('sha256', "{$gatewayOrderId}|{$gatewayPaymentId}", 'rzp_test_secret_abc');

        $verifyResponse = $this->actingAs($user, 'api')->postJson('/api/v1/shop/payments/razorpay/verify', [
            'payment_id' => $paymentId,
            'razorpay_order_id' => $gatewayOrderId,
            'razorpay_payment_id' => $gatewayPaymentId,
            'razorpay_signature' => $signature,
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'payment' => [
                    'id',
                    'gateway',
                    'status',
                    'amount',
                    'currency',
                    'gateway_order_id',
                    'gateway_payment_id',
                    'paid_at',
                ],
                'upgrade' => [
                    'application_id',
                    'status',
                    'payment_status',
                    'new_tier_id',
                    'membership' => [
                        'id',
                        'tier_id',
                        'status',
                        'expires_at',
                    ]
                ]
            ]
        ]);

        // Ensure user membership is upgraded in database
        $user->refresh();
        $this->assertEquals($goldTier->id, $user->currentMembership->membership_tier_id);
        $this->assertEquals('active', $user->currentMembership->status);

        // Ensure old membership is expired
        $initialMembership->refresh();
        $this->assertEquals('expired', $initialMembership->status);

        // Ensure draft application is updated
        $draft = MembershipApplication::findOrFail($verifyResponse->json('data.upgrade.application_id'));
        $this->assertEquals('upgrade_completed', $draft->status);
        $this->assertEquals('paid', $draft->payment_status);
    }

    /** @test */
    public function test_vault_delivery_payment_finalization_updates_status()
    {
        $user = User::factory()->create();

        // Setup a vault item and removal request
        $product = ArchiveProduct::factory()->create([
            'title' => 'Test Shield',
            'slug' => 'test-shield',
        ]);

        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'item_title' => $product->title,
            'quantity' => 1,
            'status' => 'locked',
        ]);

        $vaultRequest = VaultRemovalRequest::create([
            'user_id' => $user->id,
            'vault_item_id' => $vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_PENDING,
            'delivery_fee' => 150.00,
        ]);

        // Initiate payment for the vault removal request
        $paymentManager = app(PaymentManager::class);
        
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_vlt123',
                'entity' => 'order',
                'amount' => 15000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $vaultRequest,
            amount: 150.00,
            purpose: PaymentPurpose::VAULT_DELIVERY,
            user: $user,
            gateway: 'razorpay'
        );

        $payment = $paymentInitiation['payment'];
        $gatewayOrderId = $payment->gateway_order_id;

        // Verify the payment via verify endpoint
        $gatewayPaymentId = 'pay_vlt999';
        $signature = hash_hmac('sha256', "{$gatewayOrderId}|{$gatewayPaymentId}", 'rzp_test_secret_abc');

        $verifyResponse = $this->actingAs($user, 'api')->postJson('/api/v1/shop/payments/razorpay/verify', [
            'payment_id' => $payment->id,
            'razorpay_order_id' => $gatewayOrderId,
            'razorpay_payment_id' => $gatewayPaymentId,
            'razorpay_signature' => $signature,
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonStructure([
            'success',
            'data' => [
                'payment',
                'vault_request' => [
                    'id',
                    'status',
                    'payment_status',
                    'delivery_fee',
                ]
            ]
        ]);

        // Assert Vault Removal Request is paid and status is pending (Pending Review)
        $vaultRequest->refresh();
        $this->assertEquals(VaultRemovalRequest::PAYMENT_PAID, $vaultRequest->payment_status);
        $this->assertEquals(VaultRemovalRequest::STATUS_PENDING, $vaultRequest->status);

        // Verify that NO Shiprocket shipment is created (must not be auto-initiated)
        $this->assertNull($vaultRequest->shippingShipment);
    }

    /** @test */
    public function test_new_membership_registration_payment_submits_application()
    {
        $user = User::factory()->create();

        $tier = MembershipTier::create([
            'name' => 'Gold Membership',
            'code' => 'gold_web',
            'level' => 2,
            'price' => 5000.00,
            'duration_days' => 365,
            'is_active' => true,
            'requires_approval' => true,
        ]);

        $application = MembershipApplication::create([
            'user_id' => $user->id,
            'selected_tier_id' => $tier->id,
            'personal_details_json' => ['first_name' => 'Alex'],
            'cricket_profile_json' => [],
            'collector_intent_json' => [],
            'payment_status' => 'unpaid',
            'status' => 'draft',
        ]);

        $paymentManager = app(PaymentManager::class);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_mem123',
                'entity' => 'order',
                'amount' => 500000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        $paymentInitiation = $paymentManager->initiatePayment(
            payable: $application,
            amount: 5000.00,
            purpose: PaymentPurpose::MEMBERSHIP_RENEWAL,
            user: $user,
            gateway: 'razorpay'
        );

        $payment = $paymentInitiation['payment'];
        $gatewayOrderId = $payment->gateway_order_id;

        // Verify the payment
        $gatewayPaymentId = 'pay_mem999';
        $signature = hash_hmac('sha256', "{$gatewayOrderId}|{$gatewayPaymentId}", 'rzp_test_secret_abc');

        $verifyResponse = $this->actingAs($user, 'api')->postJson('/api/v1/shop/payments/razorpay/verify', [
            'payment_id' => $payment->id,
            'razorpay_order_id' => $gatewayOrderId,
            'razorpay_payment_id' => $gatewayPaymentId,
            'razorpay_signature' => $signature,
        ]);

        $verifyResponse->assertStatus(200);

        // Ensure the application is submitted
        $application->refresh();
        $this->assertEquals('paid', $application->payment_status);
        $this->assertEquals('submitted', $application->status);

        // Ensure a pending membership record is created
        $membership = Membership::where('source_application_id', $application->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals('pending', $membership->status);
        $this->assertEquals($tier->id, $membership->membership_tier_id);
    }

    /** @test */
    public function test_payment_finalization_is_idempotent_across_all_flows()
    {
        $user = User::factory()->create();

        // 1. Setup Membership Upgrade Idempotency
        $tierA = MembershipTier::create([
            'name' => 'Tier A',
            'code' => 'tier_a',
            'level' => 1,
            'price' => 1000.00,
            'sort_order' => 1,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $tierB = MembershipTier::create([
            'name' => 'Tier B',
            'code' => 'tier_b',
            'level' => 2,
            'price' => 2000.00,
            'sort_order' => 2,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tierA->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);

        $upgradeApp = MembershipApplication::create([
            'user_id' => $user->id,
            'selected_tier_id' => $tierB->id,
            'status' => 'draft',
        ]);

        $upgradePayment = Payment::create([
            'user_id' => $user->id,
            'payable_type' => MembershipApplication::class,
            'payable_id' => $upgradeApp->id,
            'purpose' => PaymentPurpose::MEMBERSHIP_UPGRADE,
            'gateway' => 'razorpay',
            'amount' => 1000.00,
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
            'gateway_payment_id' => 'pay_idemp_upg',
            'meta' => [
                'upgrade_context' => [
                    'payable_amount' => 1000.00,
                ]
            ]
        ]);

        $finalizer = app(PaymentFinalizationService::class);

        // Finalize 1st time
        $result1 = $finalizer->finalizePaidPayment($upgradePayment);
        $this->assertNotNull($result1);
        $upgradeApp->refresh();
        $this->assertEquals('upgrade_completed', $upgradeApp->status);

        // Finalize 2nd time - should return null and make no database changes
        $result2 = $finalizer->finalizePaidPayment($upgradePayment);
        $this->assertNull($result2);

        // 2. Setup Vault Delivery Idempotency
        $product = ArchiveProduct::factory()->create(['title' => 'Vault Bat', 'slug' => 'vault-bat']);
        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'item_title' => $product->title,
            'quantity' => 1,
            'status' => 'locked',
        ]);
        $vaultRequest = VaultRemovalRequest::create([
            'user_id' => $user->id,
            'vault_item_id' => $vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_PENDING,
            'delivery_fee' => 150.00,
        ]);

        $vaultPayment = Payment::create([
            'user_id' => $user->id,
            'payable_type' => VaultRemovalRequest::class,
            'payable_id' => $vaultRequest->id,
            'purpose' => PaymentPurpose::VAULT_DELIVERY,
            'gateway' => 'razorpay',
            'amount' => 150.00,
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
            'gateway_payment_id' => 'pay_idemp_vlt',
        ]);

        // Finalize 1st time
        $finalizer->finalizePaidPayment($vaultPayment);
        $vaultRequest->refresh();
        $this->assertEquals(VaultRemovalRequest::PAYMENT_PAID, $vaultRequest->payment_status);

        // Finalize 2nd time - should just return the request without exceptions
        $vResult2 = $finalizer->finalizePaidPayment($vaultPayment);
        $this->assertInstanceOf(VaultRemovalRequest::class, $vResult2);
        $this->assertEquals(VaultRemovalRequest::PAYMENT_PAID, $vResult2->payment_status);
    }
}
