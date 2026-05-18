<?php

namespace Tests\Feature;

use App\Models\Archive\ArchiveProduct;
use App\Models\Auctions\AuctionLot;
use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\VaultRemovalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class VaultShippingFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Standard default config values
        Config::set('shipping.volumetric_divisor', 5000);
    }

    /**
     * Test shipping dimensions, volumetric weights, and casts on ArchiveProduct.
     */
    public function test_archive_product_shipping_dimensions()
    {
        $product = ArchiveProduct::factory()->create([
            'title' => 'Test Archive Bat',
            'slug' => 'test-archive-bat',
            'weight_kg' => 1.250,
            'length_cm' => 85.00,
            'breadth_cm' => 12.00,
            'height_cm' => 8.00,
        ]);

        $this->assertEquals(1.250, $product->weight_kg);
        $this->assertEquals(85.00, $product->length_cm);
        $this->assertEquals(12.00, $product->breadth_cm);
        $this->assertEquals(8.00, $product->height_cm);

        // Volumetric weight: 85 * 12 * 8 / 5000 = 1.632
        $this->assertEquals(1.632, $product->volumetric_weight_kg);
        
        // Chargeable weight: max(1.250, 1.632) = 1.632
        $this->assertEquals(1.632, $product->chargeable_weight_kg);
    }

    /**
     * Test shipping dimensions, volumetric weights, and casts on AuctionLot.
     */
    public function test_auction_lot_shipping_dimensions()
    {
        $lot = AuctionLot::create([
            'title' => 'Test Vintage Helmet',
            'starting_price' => 500.00,
            'min_increment' => 50.00,
            'ends_at' => now()->addDays(7),
            'lot_no' => 'LOT-99999',
            'weight_kg' => 2.100,
            'length_cm' => 30.00,
            'breadth_cm' => 30.00,
            'height_cm' => 30.00,
        ]);

        $this->assertEquals(2.100, $lot->weight_kg);
        $this->assertEquals(30.00, $lot->length_cm);
        $this->assertEquals(30.00, $lot->breadth_cm);
        $this->assertEquals(30.00, $lot->height_cm);

        // Volumetric weight: 30 * 30 * 30 / 5000 = 5.400
        $this->assertEquals(5.400, $lot->volumetric_weight_kg);

        // Chargeable weight: max(2.100, 5.400) = 5.400
        $this->assertEquals(5.400, $lot->chargeable_weight_kg);
    }

    /**
     * Test that UserVaultItem delegates to polymorphic source models correctly.
     */
    public function test_user_vault_item_delegation()
    {
        $user = User::factory()->create();

        $product = ArchiveProduct::factory()->create([
            'title' => 'Test Deluxe Pad',
            'slug' => 'test-deluxe-pad',
            'weight_kg' => 0.850,
            'length_cm' => 60.00,
            'breadth_cm' => 25.00,
            'height_cm' => 15.00,
        ]);

        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'item_title' => $product->title,
            'quantity' => 1,
            'status' => 'locked',
        ]);

        // Access via delegation
        $this->assertNotNull($vaultItem->source_item);
        $this->assertInstanceOf(ArchiveProduct::class, $vaultItem->source_item);
        $this->assertEquals(0.850, $vaultItem->weight_kg);
        $this->assertEquals(60.00, $vaultItem->length_cm);
        $this->assertEquals(25.00, $vaultItem->breadth_cm);
        $this->assertEquals(15.00, $vaultItem->height_cm);

        // Volumetric: 60 * 25 * 15 / 5000 = 4.5
        $this->assertEquals(4.5, $vaultItem->volumetric_weight_kg);
        $this->assertEquals(4.5, $vaultItem->chargeable_weight_kg);
    }

    /**
     * Test VaultRemovalRequest helper logic and payment workflow transitions.
     */
    public function test_vault_removal_request_workflow()
    {
        $user = User::factory()->create();
        
        $product = ArchiveProduct::factory()->create([
            'title' => 'Test Gloves',
            'slug' => 'test-gloves',
        ]);

        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'item_title' => $product->title,
            'quantity' => 1,
            'status' => 'locked',
        ]);

        $request = VaultRemovalRequest::create([
            'user_id' => $user->id,
            'vault_item_id' => $vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_NONE,
            'delivery_fee' => 150.00,
            'delivery_currency' => 'INR',
            'selected_courier_company_id' => 'shiprocket_standard',
            'selected_courier_name' => 'Shiprocket Express',
        ]);

        // Check defaults and helper transitions
        $this->assertFalse($request->isPaid());
        $this->assertFalse($request->isPendingPayment());
        $this->assertTrue($request->hasCourierQuote());
        $this->assertFalse($request->canBeReviewedByAdmin());
        $this->assertFalse($request->canInitiateShipment());

        // Update to pending_payment
        $request->update(['payment_status' => VaultRemovalRequest::PAYMENT_PENDING]);
        $this->assertTrue($request->isPendingPayment());
        $this->assertFalse($request->isPaid());

        // Update to paid
        $request->update([
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'paid_at' => now(),
            'payment_reference' => 'TXN_99887766',
        ]);
        $this->assertTrue($request->isPaid());
        $this->assertTrue($request->canBeReviewedByAdmin());
        $this->assertFalse($request->canInitiateShipment()); // Approved is still pending!

        // Approve by Admin
        $admin = User::factory()->create();
        $request->update([
            'status' => VaultRemovalRequest::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => $admin->id,
        ]);

        // Now shipment can be initiated
        $this->assertTrue($request->canInitiateShipment());
    }
}
