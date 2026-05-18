<?php

namespace Tests\Unit\Services\Shipping;

use App\Models\Shipping\ShippingShipment;
use App\Models\User;
use App\Services\Shipping\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShipmentService();
    }

    public function test_create_draft_shipment()
    {
        $user = User::factory()->create();
        $shippable = new \stdClass();
        $shippable->id = 123;
        $shippable->user_id = $user->id;

        // Mocking a class for polymorphic test
        $shippableModel = \App\Models\Shop\ShopProduct::factory()->create();

        $shipment = $this->service->createDraftFor($shippableModel, [
            'delivery_pincode' => '110001',
            'payment_mode' => 'prepaid'
        ]);

        $this->assertInstanceOf(ShippingShipment::class, $shipment);
        $this->assertEquals('draft', $shipment->status);
        $this->assertEquals('110001', $shipment->delivery_pincode);
        $this->assertEquals($shippableModel->id, $shipment->shippable_id);
    }

    public function test_record_event()
    {
        $shipment = ShippingShipment::create([
            'shipping_provider' => 'shiprocket',
            'status' => 'draft'
        ]);

        $event = $this->service->recordEvent($shipment, [
            'event_status' => 'Picked Up',
            'location' => 'New Delhi'
        ]);

        $this->assertEquals('Picked Up', $event->event_status);
        $this->assertEquals($shipment->id, $event->shipping_shipment_id);
    }

    public function test_prepare_courier_selection_for_shop_order()
    {
        \Illuminate\Support\Facades\Config::set('shiprocket.pickup_pincode', '110001');
        \Illuminate\Support\Facades\Config::set('shiprocket.email', 'test@example.com');
        \Illuminate\Support\Facades\Config::set('shiprocket.password', 'secret');
        
        \Illuminate\Support\Facades\Http::fake([
            '*auth/login' => \Illuminate\Support\Facades\Http::response([
                'token' => 'dummy-token'
            ]),
            '*courier/serviceability*' => \Illuminate\Support\Facades\Http::response([
                'status' => 200,
                'data' => [
                    'available_courier_companies' => [
                        [
                            'courier_company_id' => '1',
                            'courier_name' => 'Delhivery',
                            'rating' => 4.5,
                            'total_charge' => 100,
                        ]
                    ]
                ]
            ])
        ]);

        $user = User::factory()->create();

        $order = \App\Models\Shop\ShopOrder::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-123',
            'status' => 'paid',
            'payment_status' => 'paid',
            'currency' => 'INR',
            'subtotal' => 100,
            'shipping_fee' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'shipping_address_snapshot' => ['pincode' => '400001', 'postal_code' => '400001'],
            'billing_address_snapshot' => ['pincode' => '400001', 'postal_code' => '400001'],
        ]);
        
        $product = \App\Models\Shop\ShopProduct::factory()->create(['weight_kg' => 0.5]);
        
        \App\Models\Shop\ShopOrderItem::create([
            'shop_order_id' => $order->id,
            'shop_product_id' => $product->id,
            'title_snapshot' => $product->title,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $shipment = $this->service->prepareCourierSelectionForShopOrder($order);

        $this->assertNotNull($shipment);
        $this->assertEquals('courier_selected', $shipment->status);
        $this->assertEquals('Delhivery', $shipment->courier_name);
        $this->assertEquals('1', $shipment->courier_company_id);
        
        // Verify rate quote exists
        $this->assertDatabaseHas('shipping_rate_quotes', [
            'shippable_id' => $order->id,
            'selected_courier_name' => 'Delhivery'
        ]);
    }
}
