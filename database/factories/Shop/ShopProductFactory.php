<?php

namespace Database\Factories\Shop;

use App\Models\Shop\ShopProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopProductFactory extends Factory
{
    protected $model = ShopProduct::class;

    public function definition()
    {
        return [
            'title' => $this->faker->words(3, true),
            'slug' => $this->faker->slug,
            'description' => $this->faker->paragraph,
            'base_price' => $this->faker->randomFloat(2, 10, 1000),
            'is_active' => true,
            'is_featured' => false,
        ];
    }
}
