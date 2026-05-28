<?php

namespace Tests\Feature;

use App\Models\Archive\ArchiveProduct;
use App\Models\Shipping\ShippingEvent;
use App\Models\Shipping\ShippingShipment;
use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\VaultRemovalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VaultUserTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_presenter_for_vault_delivery_request()
    {
        $user = User::factory()->create();
        $item = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => 1,
            'item_title' => 'Test Bat',
            'status' => 'locked',
        ]);

        $request = VaultRemovalRequest::create([
            'user_id' => $user->id,
            'vault_item_id' => $item->id,
            'status' => 'approved',
            'payment_status' => 'paid',
            'delivery_fee' => 500,
            'delivery_currency' => 'INR',
            'selected_courier_name' => 'Shiprocket Express',
            'requested_at' => now()->subDays(2),
            'paid_at' => now()->subDays(1),
        ]);

        $presenter = app(\App\Services\Shipping\ShipmentTrackingPresenter::class);
        $data = $presenter->forVaultDeliveryRequest($request);

        $this->assertEquals('approved', $data['status']);
        $this->assertEquals('paid', $data['payment_status']);
        $this->assertEquals(500, $data['delivery_fee']);
        $this->assertCount(3, $data['events']); // requested, paid, approved milestones

        // Attach a shipment
        $shipment = $request->shippingShipment()->create([
            'courier_name' => 'Shiprocket Express',
            'status' => 'awb_assigned',
            'awb_code' => 'AWB-12345',
            'tracking_url' => 'https://track.com/123',
            'metadata' => ['simulated' => true]
        ]);

        $shipment->events()->create([
            'event_status' => 'In Transit',
            'event_description' => 'Package has left the facility.',
            'event_time' => now(),
        ]);

        $data = $presenter->forVaultDeliveryRequest($request->fresh());

        $this->assertEquals('awb_assigned', $data['status']);
        $this->assertEquals('AWB Assigned', $data['status_label']);
        $this->assertEquals('AWB-12345', $data['awb_code']);
        $this->assertTrue($data['is_test_mode']);
        // Events: requested, paid, approved + In Transit = 4 events
        $this->assertCount(4, $data['events']);
        $this->assertEquals('In Transit', $data['events'][0]['status_label']);
    }

    protected function grantVaultAccess(User $user)
    {
        $tier = \App\Models\MembershipTier::create(['name' => 'Test Tier', 'code' => 'TEST', 'level' => 1, 'has_vault_access' => true, 'price' => 0, 'duration_days' => 30]);
        $user->memberships()->create(['membership_tier_id' => $tier->id, 'status' => 'active', 'started_at' => now(), 'expires_at' => now()->addDays(30)]);
    }

    public function test_api_vault_item_resource_includes_delivery_request()
    {
        $user = User::factory()->create();
        $this->grantVaultAccess($user);

        $item = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => 1,
            'item_title' => 'Test Bat',
            'status' => 'locked',
        ]);

        $request = VaultRemovalRequest::create([
            'user_id' => $user->id,
            'vault_item_id' => $item->id,
            'status' => 'pending',
            'payment_status' => 'pending_payment',
            'delivery_fee' => 150.00,
        ]);

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/me/vault/{$item->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.delivery_request.status', 'pending');
        $response->assertJsonPath('data.delivery_request.payment_status', 'pending_payment');
        $response->assertJsonPath('data.delivery_request.delivery_fee', 150);
    }

    public function test_livewire_vault_index_maps_delivery_badge()
    {
        $user = User::factory()->create();
        $this->grantVaultAccess($user);
        
        $item = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => 1,
            'item_title' => 'Test Bat',
            'status' => 'locked',
        ]);

        VaultRemovalRequest::create([
            'user_id' => $user->id,
            'vault_item_id' => $item->id,
            'status' => 'pending',
            'payment_status' => 'pending_payment',
            'delivery_fee' => 150.00,
        ]);

        $this->actingAs($user);
        
        Livewire::test(\App\Livewire\Vault\Index::class)
            ->assertSee('Payment Pending');
    }

    public function test_delivery_payment_page_unauthorized_access()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $item = UserVaultItem::create([
            'user_id' => $user1->id,
            'source_type' => 'archive_product',
            'source_id' => 1,
            'item_title' => 'Test Bat',
            'status' => 'locked',
        ]);

        $request = VaultRemovalRequest::create([
            'user_id' => $user1->id,
            'vault_item_id' => $item->id,
            'status' => 'pending',
            'payment_status' => 'pending_payment',
        ]);

        // User2 trying to access User1's request
        $this->actingAs($user2);
        
        Livewire::test(\App\Livewire\Shop\CheckoutPage::class, ['vaultRequestId' => $request->id])
            ->assertRedirect(route('vault.index'));
    }

    public function test_delivery_payment_page_successful_simulation()
    {
        $user = User::factory()->create();
        
        $item = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => 1,
            'item_title' => 'Test Bat',
            'status' => 'locked',
        ]);

        $request = VaultRemovalRequest::create([
            'user_id' => $user->id,
            'vault_item_id' => $item->id,
            'status' => 'pending',
            'payment_status' => 'pending_payment',
            'delivery_fee' => 200,
            'delivery_currency' => 'INR',
        ]);

        $address = \App\Models\Shop\UserAddress::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.razorpay.com/v1/orders' => \Illuminate\Support\Facades\Http::response([
                'id' => 'order_vlt999',
                'entity' => 'order',
                'amount' => 20000,
                'currency' => 'INR',
                'status' => 'created',
            ], 201)
        ]);

        config(['payments.default_gateway' => 'razorpay']);
        
        $lw = Livewire::test(\App\Livewire\Shop\CheckoutPage::class, ['vaultRequestId' => $request->id])
            ->set('selectedAddressId', $address->id);

        $lw->call('placeOrder');

        $payment = \App\Models\Payment::where('payable_type', VaultRemovalRequest::class)
            ->where('payable_id', $request->id)
            ->first();

        $this->assertNotNull($payment);
        $lw->assertRedirect(route('payments.pay', $payment->id));
    }
}
