<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\VaultRemovalRequest;
use App\Models\Archive\ArchiveProduct;
use App\Models\Shipping\ShippingShipment;
use App\Services\VaultService;
use App\Services\Shipping\ShipmentService;
use App\Services\Shipping\Shiprocket\ShiprocketOrderService;
use App\Livewire\Admin\Vault\RemovalRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VaultFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected UserVaultItem $vaultItem;
    protected VaultService $vaultService;

    public function setUp(): void
    {
        parent::setUp();

        // Setup role
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'ecc_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('ecc_admin');

        $this->user = User::factory()->create();
        
        $product = ArchiveProduct::factory()->create([
            'title' => 'Fulfillment Test Bat',
            'slug' => 'fulfillment-test-bat',
            'price_min_amount' => 500,
            'price_max_amount' => 500,
        ]);

        $this->vaultItem = UserVaultItem::create([
            'user_id' => $this->user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'item_title' => $product->title,
            'quantity' => 1,
            'status' => 'locked',
            'item_ref' => 'TEST-REF-99',
        ]);

        $this->vaultService = app(VaultService::class);
        $this->actingAs($this->admin);

        // Force Shiprocket to test mode for simulated behaviors
        config([
            'shiprocket.test_mode' => true,
            'shiprocket.pickup_location' => 'Primary Warehouse',
            'shiprocket.pickup_pincode' => '110001',
        ]);
    }

    /** @test */
    public function test_admin_can_initiate_shipment_in_test_mode()
    {
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_APPROVED,
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'delivery_fee' => 150.00,
            'delivery_currency' => 'INR',
            'selected_courier_company_id' => 10,
            'selected_courier_name' => 'FedEx Air',
            'selected_courier_rating' => 4.5,
            'package_weight_kg' => 0.8,
            'package_length_cm' => 12.0,
            'package_breadth_cm' => 12.0,
            'package_height_cm' => 12.0,
            'volumetric_weight_kg' => 0.35,
            'chargeable_weight_kg' => 0.8,
            'delivery_name' => 'John Doe',
            'delivery_phone' => '9876543210',
            'delivery_line1' => 'Flat 101, Test Appts',
            'delivery_line2' => 'Sector 4',
            'delivery_city' => 'New Delhi',
            'delivery_state' => 'Delhi',
            'delivery_postal_code' => '110048',
            'delivery_country' => 'India',
        ]);

        Livewire::test(RemovalRequests::class)
            ->call('initiateShipment', $request->id)
            ->assertHasNoErrors();

        // Verify ShippingShipment was created
        $shipment = ShippingShipment::where('shippable_type', VaultRemovalRequest::class)
            ->where('shippable_id', $request->id)
            ->first();

        $this->assertNotNull($shipment);
        $this->assertEquals('FedEx Air', $shipment->courier_name);
        $this->assertEquals('awb_assigned', $shipment->status);
        $this->assertNotNull($shipment->provider_order_id);
        $this->assertNotNull($shipment->provider_shipment_id);
        
        // Under test mode, an AWB is immediately mock assigned
        $this->assertNotNull($shipment->awb_code);
        $this->assertStringStartsWith('TEST-AWB-', $shipment->awb_code);
    }

    /** @test */
    public function test_admin_can_retry_assign_awb_and_refresh_tracking()
    {
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_APPROVED,
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'delivery_fee' => 150.00,
            'delivery_postal_code' => '110048',
            'delivery_name' => 'John Doe',
            'selected_courier_company_id' => 10,
        ]);

        // Directly draft a shipment with no awb
        $shipment = ShippingShipment::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shippable_type' => VaultRemovalRequest::class,
            'shippable_id' => $request->id,
            'user_id' => $this->user->id,
            'shipping_provider' => 'shiprocket',
            'status' => 'courier_selected',
            'provider_order_id' => '12345',
            'provider_shipment_id' => '67890',
            'courier_company_id' => 10,
            'delivery_pincode' => '110048',
        ]);

        Livewire::test(RemovalRequests::class)
            ->call('retryAssignAwb', $request->id)
            ->assertHasNoErrors();

        $shipment->refresh();
        $this->assertEquals('awb_assigned', $shipment->status);
        $this->assertNotNull($shipment->awb_code);

        // Verify tracking logs populate
        Livewire::test(RemovalRequests::class)
            ->call('refreshTracking', $request->id)
            ->assertHasNoErrors();

        $shipment->refresh();
        $this->assertGreaterThan(0, $shipment->events()->count());
    }

    /** @test */
    public function test_admin_can_generate_mock_documents_in_test_mode()
    {
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_APPROVED,
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'delivery_fee' => 150.00,
            'delivery_postal_code' => '110048',
            'delivery_name' => 'John Doe',
            'selected_courier_company_id' => 10,
        ]);

        $shipment = ShippingShipment::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shippable_type' => VaultRemovalRequest::class,
            'shippable_id' => $request->id,
            'user_id' => $this->user->id,
            'shipping_provider' => 'shiprocket',
            'status' => 'awb_assigned',
            'provider_order_id' => '12345',
            'provider_shipment_id' => '67890',
            'courier_company_id' => 10,
            'delivery_pincode' => '110048',
            'awb_code' => 'TEST-AWB-1234',
        ]);

        // Generate mock label
        Livewire::test(RemovalRequests::class)
            ->call('generateDocument', $request->id, 'label')
            ->assertHasNoErrors();

        // Generate mock invoice
        Livewire::test(RemovalRequests::class)
            ->call('generateDocument', $request->id, 'invoice')
            ->assertHasNoErrors();

        // Generate mock manifest
        Livewire::test(RemovalRequests::class)
            ->call('generateDocument', $request->id, 'manifest')
            ->assertHasNoErrors();

        $shipment->refresh();
        $this->assertNotEmpty($shipment->metadata['documents']);
        $this->assertTrue($shipment->metadata['documents']['label']['simulated']);
        $this->assertTrue($shipment->metadata['documents']['invoice']['simulated']);
        $this->assertTrue($shipment->metadata['documents']['manifest']['simulated']);
    }

    /** @test */
    public function test_admin_can_complete_delivered_simulated_vault_delivery()
    {
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_APPROVED,
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'delivery_fee' => 150.00,
            'delivery_postal_code' => '110048',
            'delivery_name' => 'John Doe',
            'selected_courier_company_id' => 10,
        ]);

        $shipment = ShippingShipment::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shippable_type' => VaultRemovalRequest::class,
            'shippable_id' => $request->id,
            'user_id' => $this->user->id,
            'shipping_provider' => 'shiprocket',
            'status' => 'delivered',
            'provider_order_id' => '12345',
            'provider_shipment_id' => '67890',
            'courier_company_id' => 10,
            'delivery_pincode' => '110048',
            'awb_code' => 'TEST-AWB-1234',
        ]);

        Livewire::test(RemovalRequests::class)
            ->call('completeDelivery', $request->id)
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertEquals(VaultRemovalRequest::STATUS_COMPLETED, $request->status);

        $this->vaultItem->refresh();
        $this->assertEquals('removed', $this->vaultItem->status);
    }
}
