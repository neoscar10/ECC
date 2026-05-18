<?php

namespace Tests\Unit\Services\Shipping;

use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\Archive\ArchiveProduct;
use App\Models\Shop\UserAddress;
use App\Models\Shipping\ShippingRateQuote;
use App\Services\Shipping\VaultDeliveryQuoteService;
use App\Services\Shipping\ShippingMeasurementService;
use App\Services\Shipping\ShippingCourierSelectionService;
use App\Services\Shipping\Shiprocket\ShiprocketClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class VaultDeliveryQuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $quoteService;
    protected $measurementService;
    protected $courierService;
    protected $shiprocketClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Mock ShiprocketClient
        $this->shiprocketClientMock = $this->createMock(ShiprocketClient::class);

        // 2. Set config defaults
        Config::set('shiprocket.pickup_pincode', '110001');
        Config::set('shipping.default_weight_kg', 0.5);
        Config::set('shipping.default_length_cm', 10);
        Config::set('shipping.default_breadth_cm', 10);
        Config::set('shipping.default_height_cm', 10);

        // 3. Initialize Services
        $this->measurementService = new ShippingMeasurementService();
        $this->courierService = new ShippingCourierSelectionService($this->shiprocketClientMock);
        $this->quoteService = new VaultDeliveryQuoteService($this->measurementService, $this->courierService);
    }

    public function test_calculate_quote_successfully()
    {
        $user = User::factory()->create();
        
        $product = ArchiveProduct::factory()->create([
            'weight_kg' => 1.5,
            'length_cm' => 12,
            'breadth_cm' => 12,
            'height_cm' => 12,
        ]);

        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'quantity' => 1,
            'status' => 'locked',
            'item_title' => 'Vault Item',
        ]);

        $address = UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Home',
            'full_name' => 'John Doe',
            'phone' => '9999999999',
            'line1' => 'Test Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110055',
            'country' => 'India',
        ]);

        // Mock Shiprocket serviceability API response
        $mockResponse = [
            'success' => true,
            'data' => [
                'available_courier_companies' => [
                    [
                        'courier_company_id' => '12',
                        'courier_name' => 'Blue Dart Air',
                        'rating' => '4.8',
                        'freight_charge' => '72.00',
                        'cod_charges' => '0.00',
                        'total_charge' => '72.00',
                        'etd' => '2026-05-20',
                        'estimated_delivery_days' => 3,
                        'cod' => 0,
                        'prepaid' => 1,
                    ],
                    [
                        'courier_company_id' => '15',
                        'courier_name' => 'Delhivery',
                        'rating' => '4.0',
                        'freight_charge' => '50.00',
                        'cod_charges' => '0.00',
                        'total_charge' => '50.00',
                        'etd' => '2026-05-22',
                        'estimated_delivery_days' => 5,
                        'cod' => 0,
                        'prepaid' => 1,
                    ],
                ]
            ]
        ];

        $this->shiprocketClientMock->expects($this->once())
            ->method('get')
            ->with('/courier/serviceability/', $this->callback(function ($payload) {
                return $payload['delivery_postcode'] === '110055' 
                    && $payload['pickup_postcode'] === '110001'
                    && (float) $payload['weight'] === 1.5;
            }))
            ->willReturn($mockResponse);

        $result = $this->quoteService->quoteForVaultItem($vaultItem, $address, $user);

        // Assert Success Output Structure
        $this->assertTrue($result['success']);
        $this->assertEquals(72.00, $result['delivery_fee']);
        $this->assertEquals('INR', $result['currency']);
        $this->assertEquals('Blue Dart Air', $result['selected_courier']['courier_name']);
        $this->assertEquals(4.8, $result['selected_courier']['rating']);
        $this->assertEquals(3, $result['selected_courier']['estimated_delivery_days']);
        $this->assertEquals('110001', $result['pickup_pincode']);
        $this->assertEquals('110055', $result['delivery_pincode']);
        
        // Assert stored rate quote model exists
        $this->assertDatabaseHas('shipping_rate_quotes', [
            'shippable_type' => UserVaultItem::class,
            'shippable_id' => $vaultItem->id,
            'delivery_pincode' => '110055',
            'selected_courier_name' => 'Blue Dart Air',
            'selected_total_charge' => '72.00',
        ]);
    }

    public function test_calculate_quote_fails_for_missing_postal_code()
    {
        $user = User::factory()->create();
        
        $product = ArchiveProduct::factory()->create();
        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'status' => 'locked',
            'item_title' => 'Vault Item',
        ]);

        $address = UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Office',
            'full_name' => 'John Doe',
            'phone' => '9999999999',
            'line1' => 'Test Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '', // Empty
            'country' => 'India',
        ]);

        $result = $this->quoteService->quoteForVaultItem($vaultItem, $address, $user);

        $this->assertFalse($result['success']);
        $this->assertEquals('missing_pincode', $result['reason']);
        $this->assertEquals(0.00, $result['delivery_fee']);
    }

    public function test_calculate_quote_fails_for_missing_pickup_pincode()
    {
        Config::offsetUnset('shiprocket.pickup_pincode');

        $user = User::factory()->create();
        $product = ArchiveProduct::factory()->create();
        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'status' => 'locked',
            'item_title' => 'Vault Item',
        ]);

        $address = UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Office',
            'full_name' => 'John Doe',
            'phone' => '9999999999',
            'line1' => 'Test Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110055',
            'country' => 'India',
        ]);

        $result = $this->quoteService->quoteForVaultItem($vaultItem, $address, $user);

        $this->assertFalse($result['success']);
        $this->assertEquals('missing_pickup_pincode', $result['reason']);
    }

    public function test_calculate_quote_fails_when_unserviceable()
    {
        $user = User::factory()->create();
        $product = ArchiveProduct::factory()->create();
        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'status' => 'locked',
            'item_title' => 'Vault Item',
        ]);

        $address = UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Office',
            'full_name' => 'John Doe',
            'phone' => '9999999999',
            'line1' => 'Test Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '999999', // Out of bounds
            'country' => 'India',
        ]);

        // Mock Shiprocket returning unserviceable empty couriers response
        $mockResponse = [
            'success' => true,
            'data' => [
                'available_courier_companies' => []
            ]
        ];

        $this->shiprocketClientMock->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $result = $this->quoteService->quoteForVaultItem($vaultItem, $address, $user);

        $this->assertFalse($result['success']);
        $this->assertEquals('no_courier_available', $result['reason']);
        $this->assertEquals(0.00, $result['delivery_fee']);
    }
}
