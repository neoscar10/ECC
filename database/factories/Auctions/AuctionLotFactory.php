<?php

namespace Database\Factories\Auctions;

use App\Models\Auctions\AuctionLot;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuctionLotFactory extends Factory
{
    protected $model = AuctionLot::class;

    public function definition(): array
    {
        return [
            'lot_no' => $this->faker->unique()->numberBetween(1000, 9999),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => 'live',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(2),
            'starting_price' => 100.00,
            'min_selling_price' => 150.00,
            'current_highest_bid' => 100.00,
            'min_increment' => 10.00,
            'anti_sniping_enabled' => true,
            'blur_enabled' => false,
            'early_access_enabled' => false,
        ];
    }
}
