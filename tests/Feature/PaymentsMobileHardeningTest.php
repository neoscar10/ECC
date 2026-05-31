<?php

namespace Tests\Feature;

use App\Models\MembershipApplication;
use App\Models\MembershipTier;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\VaultRemovalRequest;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\UserAddress;
use App\Support\Payments\PaymentPurpose;
use App\Support\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class PaymentsMobileHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected User $admin;
    protected UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Setup Spatie roles for Admin
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->address = UserAddress::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function test_vault_removal_request_payment_initiation()
    {
        $vaultItem = UserVaultItem::create([
            'user_id' => $this->user->id,
            'item_title' => 'Vintage Bat',
            'item_ref' => 'REF123',
            'status' => 'locked',
            'source_type' => 'archive_product',
            'source_id' => 1,
        ]);

        $removalRequest = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $vaultItem->id,
            'status' => 'pending',
            'payment_status' => 'pending_payment',
            'delivery_fee' => 150.00,
            'delivery_name' => 'User Name',
            'delivery_phone' => '1234567890',
            'delivery_line1' => 'Line 1',
            'delivery_city' => 'City',
            'delivery_state' => 'State',
            'delivery_postal_code' => '123456',
            'delivery_country' => 'India',
        ]);

        // Mock Razorpay order creation
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_vault123',
                'entity' => 'order',
                'amount' => 15000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        // Try initiating with other user (Unauthorized)
        $response = $this->actingAs($this->otherUser, 'api')
            ->postJson("/api/v1/vault/removal-requests/{$removalRequest->id}/payment/initiate");
        $response->assertStatus(404);

        // Initiate with owner
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/vault/removal-requests/{$removalRequest->id}/payment/initiate", [
                'payment_gateway' => 'razorpay'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'vault_request' => [
                    'id',
                    'status',
                    'payment_status',
                    'delivery_fee',
                ],
                'payment' => [
                    'id',
                    'gateway',
                    'status',
                    'amount',
                    'currency',
                    'purpose',
                    'verify_endpoint',
                    'checkout',
                    'gateway_order_id',
                ]
            ]
        ]);
        
        $this->assertEquals('razorpay', $response->json('data.payment.gateway'));
        $this->assertEquals(150.00, $response->json('data.payment.amount'));

        // Try initiating when already paid
        $removalRequest->update(['payment_status' => 'paid']);
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/vault/removal-requests/{$removalRequest->id}/payment/initiate");
        $response->assertStatus(400);
    }

    /** @test */
    public function test_payment_status_retrieval()
    {
        $payment = Payment::create([
            'user_id' => $this->user->id,
            'purpose' => 'shop_order',
            'gateway' => 'razorpay',
            'amount' => 500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'gateway_order_id' => 'order_status123',
        ]);

        // Unauthenticated check
        $response = $this->getJson("/api/v1/payments/{$payment->id}");
        $response->assertStatus(401);

        // Other user cannot retrieve (Unauthorized)
        $response = $this->actingAs($this->otherUser, 'api')->getJson("/api/v1/payments/{$payment->id}");
        $response->assertStatus(403);

        // Owner can retrieve
        $response = $this->actingAs($this->user, 'api')->getJson("/api/v1/payments/{$payment->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'status' => PaymentStatus::PENDING,
                'amount' => 500.00,
                'gateway_order_id' => 'order_status123'
            ]
        ]);

        // Admin can retrieve
        $response = $this->actingAs($this->admin, 'api')->getJson("/api/v1/payments/{$payment->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function test_payment_retry_logic()
    {
        $order = ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-RETRY-01',
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'currency' => 'INR',
            'subtotal' => 300.00,
            'total_amount' => 300.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot' => $this->address->toArray(),
        ]);

        $failedPayment = Payment::create([
            'user_id' => $this->user->id,
            'payable_type' => ShopOrder::class,
            'payable_id' => $order->id,
            'purpose' => 'shop_order',
            'gateway' => 'razorpay',
            'amount' => 300.00,
            'currency' => 'INR',
            'status' => PaymentStatus::FAILED,
        ]);

        // Mock Razorpay order creation
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_retry123',
                'entity' => 'order',
                'amount' => 30000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        // Try retrying other user's payment (Unauthorized)
        $response = $this->actingAs($this->otherUser, 'api')->postJson("/api/v1/payments/{$failedPayment->id}/retry");
        $response->assertStatus(403);

        // Retry with owner (Success, returns a new payment)
        $response = $this->actingAs($this->user, 'api')->postJson("/api/v1/payments/{$failedPayment->id}/retry");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'gateway',
                'status',
                'amount',
                'verify_endpoint',
                'checkout',
                'gateway_order_id',
            ]
        ]);

        $newPaymentId = $response->json('data.id');
        $this->assertNotEquals($failedPayment->id, $newPaymentId);

        // Try retrying a paid payment
        $paidPayment = Payment::create([
            'user_id' => $this->user->id,
            'payable_type' => ShopOrder::class,
            'payable_id' => $order->id,
            'purpose' => 'shop_order',
            'gateway' => 'razorpay',
            'amount' => 300.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PAID,
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson("/api/v1/payments/{$paidPayment->id}/retry");
        $response->assertStatus(400);
    }
}
