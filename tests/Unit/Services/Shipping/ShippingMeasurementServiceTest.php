<?php

namespace Tests\Unit\Services\Shipping;

use App\Services\Shipping\ShippingMeasurementService;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingMeasurementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShippingMeasurementService();
    }

    public function test_volumetric_weight_calculation()
    {
        Config::set('shipping.volumetric_divisor', 5000);
        
        // 10 * 10 * 10 / 5000 = 0.2
        $this->assertEquals(0.2, $this->service->volumetricWeightKg(10, 10, 10));
        
        // 20 * 20 * 20 / 5000 = 1.6
        $this->assertEquals(1.6, $this->service->volumetricWeightKg(20, 20, 20));
    }

    public function test_chargeable_weight_calculation()
    {
        // Actual > Volumetric
        $this->assertEquals(5.0, $this->service->chargeableWeightKg(5.0, 3.0));
        
        // Volumetric > Actual
        $this->assertEquals(7.5, $this->service->chargeableWeightKg(4.0, 7.5));
        
        // Equal
        $this->assertEquals(2.0, $this->service->chargeableWeightKg(2.0, 2.0));
    }

    public function test_normalize_measurement_with_fallback()
    {
        Config::set('shipping.default_weight_kg', 0.5);
        Config::set('shipping.default_length_cm', 10);
        Config::set('shipping.default_breadth_cm', 10);
        Config::set('shipping.default_height_cm', 10);
        Config::set('shipping.volumetric_divisor', 5000);

        $result = $this->service->normalizeMeasurement([], true);

        $this->assertEquals(0.5, $result['weight_kg']);
        $this->assertEquals(10, $result['length_cm']);
        $this->assertEquals(10, $result['breadth_cm']);
        $this->assertEquals(10, $result['height_cm']);
        $this->assertEquals(0.2, $result['volumetric_weight_kg']);
        $this->assertEquals(0.5, $result['chargeable_weight_kg']);
        $this->assertTrue($result['is_fallback']);
        $this->assertEquals('fallback', $result['source']);
    }

    public function test_normalize_measurement_without_fallback()
    {
        $data = [
            'weight_kg' => 2.5,
            'length_cm' => 30,
            'breadth_cm' => 20,
            'height_cm' => 15,
            'source' => 'product'
        ];

        $result = $this->service->normalizeMeasurement($data, false);

        $this->assertEquals(2.5, $result['weight_kg']);
        $this->assertEquals(1.8, $result['volumetric_weight_kg']); // (30*20*15)/5000 = 1.8
        $this->assertEquals(2.5, $result['chargeable_weight_kg']);
        $this->assertFalse($result['is_fallback']);
        $this->assertEquals('product', $result['source']);
    }

    public function test_measurement_from_shop_order()
    {
        $user = \App\Models\User::factory()->create();

        $product = \App\Models\Shop\ShopProduct::factory()->create([
            'weight_kg' => 1.0,
            'length_cm' => 10,
            'breadth_cm' => 10,
            'height_cm' => 10,
        ]);

        $variant = \App\Models\Shop\ShopProductVariant::create([
            'shop_product_id' => $product->id,
            'sku' => 'TEST-VAR-1',
            'price' => 100,
            'stock' => 10,
            'weight_kg' => 0.5,
            'length_cm' => 20,
            'breadth_cm' => 20,
            'height_cm' => 20,
        ]);

        $order = \App\Models\Shop\ShopOrder::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-999',
            'status' => 'pending',
            'payment_status' => 'pending',
            'currency' => 'INR',
            'subtotal' => 200,
            'shipping_fee' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 200,
            'shipping_address_snapshot' => '{}',
            'billing_address_snapshot' => '{}',
        ]);
        
        $item1 = \App\Models\Shop\ShopOrderItem::create([
            'shop_order_id' => $order->id,
            'shop_product_id' => $product->id,
            'title_snapshot' => $product->title,
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        $item2 = \App\Models\Shop\ShopOrderItem::create([
            'shop_order_id' => $order->id,
            'shop_product_id' => $product->id,
            'shop_product_variant_id' => $variant->id,
            'title_snapshot' => $product->title . ' (Variant)',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $result = $this->service->measurementFromShopOrder($order);

        // (1.0 * 2) + (0.5 * 1) = 2.5
        $this->assertEquals(2.5, $result['weight_kg']);
        
        // Max dimensions: max(10, 20) = 20
        $this->assertEquals(20, $result['length_cm']);
        $this->assertEquals(20, $result['breadth_cm']);
        $this->assertEquals(40, $result['height_cm']);
        
        // Volumetric (20*20*40)/5000 = 3.2
        $this->assertEquals(3.2, $result['volumetric_weight_kg']);
        
        // Chargeable max(2.5, 3.2) = 3.2
        $this->assertEquals(3.2, $result['chargeable_weight_kg']);
    }

    public function test_measurement_from_vault_item_with_archive_product()
    {
        $product = \App\Models\Archive\ArchiveProduct::factory()->create([
            'weight_kg' => 2.0,
            'length_cm' => 15,
            'breadth_cm' => 15,
            'height_cm' => 15,
        ]);

        $vaultItem = \App\Models\UserVaultItem::create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'quantity' => 1,
            'status' => 'locked',
            'item_title' => 'Test Item',
        ]);

        $result = $this->service->measurementFromVaultItem($vaultItem);

        $this->assertEquals(2.0, $result['weight_kg']);
        $this->assertEquals(15.0, $result['length_cm']);
        $this->assertEquals(15.0, $result['breadth_cm']);
        $this->assertEquals(15.0, $result['height_cm']);
        // (15*15*15)/5000 = 0.675
        $this->assertEquals(0.675, $result['volumetric_weight_kg']);
        $this->assertEquals(2.0, $result['chargeable_weight_kg']);
        $this->assertEquals('archive_product', $result['source']);
        $this->assertFalse($result['has_fallback']);
    }

    public function test_measurement_from_vault_item_with_stacking_quantity()
    {
        $product = \App\Models\Archive\ArchiveProduct::factory()->create([
            'weight_kg' => 1.5,
            'length_cm' => 10,
            'breadth_cm' => 10,
            'height_cm' => 5,
        ]);

        $vaultItem = \App\Models\UserVaultItem::create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'quantity' => 3,
            'status' => 'locked',
            'item_title' => 'Test Stacked Item',
        ]);

        $result = $this->service->measurementFromVaultItem($vaultItem);

        // weight = 1.5 * 3 = 4.5
        $this->assertEquals(4.5, $result['weight_kg']);
        $this->assertEquals(10.0, $result['length_cm']);
        $this->assertEquals(10.0, $result['breadth_cm']);
        // height = 5 * 3 = 15
        $this->assertEquals(15.0, $result['height_cm']);
        // volumetric = (10 * 10 * 15) / 5000 = 0.3
        $this->assertEquals(0.3, $result['volumetric_weight_kg']);
        // chargeable = max(4.5, 0.3) = 4.5
        $this->assertEquals(4.5, $result['chargeable_weight_kg']);
    }

    public function test_measurement_from_vault_item_with_fallback()
    {
        Config::set('shipping.default_weight_kg', 0.5);
        Config::set('shipping.default_length_cm', 10);
        Config::set('shipping.default_breadth_cm', 10);
        Config::set('shipping.default_height_cm', 10);
        Config::set('shipping.volumetric_divisor', 5000);

        // Vault item with zero/empty source dimensions
        $product = \App\Models\Archive\ArchiveProduct::factory()->create([
            'weight_kg' => null,
            'length_cm' => null,
            'breadth_cm' => null,
            'height_cm' => null,
        ]);

        $vaultItem = \App\Models\UserVaultItem::create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'quantity' => 1,
            'status' => 'locked',
            'item_title' => 'Test Fallback Item',
        ]);

        $result = $this->service->measurementFromVaultItem($vaultItem);

        $this->assertEquals(0.5, $result['weight_kg']);
        $this->assertEquals(10.0, $result['length_cm']);
        $this->assertEquals(10.0, $result['breadth_cm']);
        $this->assertEquals(10.0, $result['height_cm']);
        $this->assertEquals(0.2, $result['volumetric_weight_kg']);
        $this->assertEquals(0.5, $result['chargeable_weight_kg']);
        $this->assertTrue($result['has_fallback']);
        $this->assertEquals('fallback', $result['source']);
    }
}
