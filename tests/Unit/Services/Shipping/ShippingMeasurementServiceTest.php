<?php

namespace Tests\Unit\Services\Shipping;

use App\Services\Shipping\ShippingMeasurementService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ShippingMeasurementServiceTest extends TestCase
{
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
        $product = \App\Models\Shop\ShopProduct::factory()->create([
            'weight_kg' => 1.0,
            'length_cm' => 10,
            'breadth_cm' => 10,
            'height_cm' => 10,
        ]);

        $variant = \App\Models\Shop\ShopProductVariant::factory()->create([
            'shop_product_id' => $product->id,
            'weight_kg' => 0.5,
            'length_cm' => 20,
            'breadth_cm' => 20,
            'height_cm' => 20,
        ]);

        $order = \App\Models\Shop\ShopOrder::factory()->create();
        
        $item1 = \App\Models\Shop\ShopOrderItem::factory()->create([
            'shop_order_id' => $order->id,
            'shop_product_id' => $product->id,
            'quantity' => 2,
        ]);

        $item2 = \App\Models\Shop\ShopOrderItem::factory()->create([
            'shop_order_id' => $order->id,
            'shop_product_id' => $product->id,
            'shop_product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $result = $this->service->measurementFromShopOrder($order);

        // (1.0 * 2) + (0.5 * 1) = 2.5
        $this->assertEquals(2.5, $result['weight_kg']);
        
        // Max dimensions: max(10, 20) = 20
        $this->assertEquals(20, $result['length_cm']);
        $this->assertEquals(20, $result['breadth_cm']);
        $this->assertEquals(20, $result['height_cm']);
        
        // Volumetric (20*20*20)/5000 = 1.6
        $this->assertEquals(1.6, $result['volumetric_weight_kg']);
        
        // Chargeable max(2.5, 1.6) = 2.5
        $this->assertEquals(2.5, $result['chargeable_weight_kg']);
    }
}
