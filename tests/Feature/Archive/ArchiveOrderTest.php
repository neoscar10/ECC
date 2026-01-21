<?php

namespace Tests\Feature\Archive;

use App\Models\Archive\ArchiveOrder;
use App\Models\Archive\ArchiveProduct;
use App\Models\User;
use App\Services\Archive\ArchiveOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ArchiveOrderTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $admin;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ArchiveOrderService();
        
        // Create Role
        \Spatie\Permission\Models\Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        
        // Setup Product with stock
        $this->product = ArchiveProduct::factory()->create([
            'quantity' => 10,
            'price_min_amount' => 1000
        ]);
    }

    public function test_create_order_deducts_stock()
    {
        $orderData = [
            'archive_product_id' => $this->product->id,
            'qty' => 2,
            'unit_price_inr' => 1200,
            'buyer_type' => 'registered',
            'user_id' => $this->admin->id, // Buying for self
        ];

        $order = $this->service->createOrder($orderData, $this->admin);

        $this->assertDatabaseHas('archive_orders', [
            'id' => $order->id,
            'qty' => 2,
            'subtotal_inr' => 2400
        ]);

        $this->assertEquals(8, $this->product->fresh()->quantity);
    }

    public function test_insufficient_stock_prevents_order()
    {
        $orderData = [
            'archive_product_id' => $this->product->id,
            'qty' => 11, // Requesting more than 10
            'unit_price_inr' => 1200,
            'buyer_type' => 'external',
            'external_name' => 'John Doe'
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->createOrder($orderData, $this->admin);

        // Stock remains same
        $this->assertEquals(10, $this->product->fresh()->quantity);
    }

    public function test_cancel_order_restores_stock()
    {
        // 1. Create order first
        $order = $this->service->createOrder([
            'archive_product_id' => $this->product->id,
            'qty' => 3,
            'unit_price_inr' => 1000,
            'buyer_type' => 'external',
            'external_name' => 'Jane'
        ], $this->admin);

        $this->assertEquals(7, $this->product->fresh()->quantity);

        // 2. Cancel
        $this->service->cancelOrder($order, $this->admin);

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(10, $this->product->fresh()->quantity);
    }
}
