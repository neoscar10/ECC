<?php

namespace Tests\Unit\Services\Shipping;

use App\Services\Shipping\ShippingCourierSelectionService;
use App\Services\Shipping\Shiprocket\ShiprocketClient;
use Tests\TestCase;

class ShippingCourierSelectionServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $client = $this->createMock(ShiprocketClient::class);
        $this->service = new ShippingCourierSelectionService($client);
    }

    public function test_select_best_courier_by_rating()
    {
        $couriers = [
            ['courier_company_id' => 1, 'courier_name' => 'Bad Rating', 'rating' => 3.5, 'total_charge' => 100],
            ['courier_company_id' => 2, 'courier_name' => 'Good Rating', 'rating' => 4.8, 'total_charge' => 120],
            ['courier_company_id' => 3, 'courier_name' => 'Average Rating', 'rating' => 4.2, 'total_charge' => 80],
        ];

        $selected = $this->service->selectBestCourier($couriers);

        $this->assertEquals(2, $selected['courier_company_id']);
        $this->assertEquals(4.8, $selected['rating']);
    }

    public function test_select_best_courier_tie_break_by_price()
    {
        $couriers = [
            ['courier_company_id' => 1, 'courier_name' => 'Expensive', 'rating' => 4.5, 'total_charge' => 150],
            ['courier_company_id' => 2, 'courier_name' => 'Cheap', 'rating' => 4.5, 'total_charge' => 100],
        ];

        $selected = $this->service->selectBestCourier($couriers);

        $this->assertEquals(2, $selected['courier_company_id']);
        $this->assertEquals(100, $selected['total_charge']);
    }

    public function test_select_best_courier_empty_list()
    {
        $this->assertNull($this->service->selectBestCourier([]));
    }

    public function test_normalize_courier()
    {
        $raw = [
            'courier_company_id' => '10',
            'courier_name' => 'Delhivery',
            'rating' => '4.5',
            'freight_charge' => '50.00',
            'cod_charges' => '10.00',
            'total_charge' => '60.00',
            'etd' => '2026-05-20',
            'estimated_delivery_days' => 3,
        ];

        $normalized = $this->service->normalizeCourier($raw);

        $this->assertEquals('10', $normalized['courier_company_id']);
        $this->assertEquals('Delhivery', $normalized['courier_name']);
        $this->assertEquals(4.5, $normalized['rating']);
        $this->assertEquals(50.0, $normalized['freight_charge']);
        $this->assertEquals(60.0, $normalized['total_charge']);
    }

    public function test_extract_available_couriers()
    {
        $response = [
            'data' => [
                'available_courier_companies' => [
                    ['courier_name' => 'C1', 'rating' => 4.0],
                    ['courier_name' => 'C2', 'rating' => 4.5],
                ]
            ]
        ];

        $extracted = $this->service->extractAvailableCouriers($response);

        $this->assertCount(2, $extracted);
        $this->assertEquals('C1', $extracted[0]['courier_name']);
        $this->assertEquals(4.5, $extracted[1]['rating']);
    }
}
