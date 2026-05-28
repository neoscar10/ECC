<?php

namespace Database\Factories\Shop;

use App\Models\Shop\ShopProductVariationGroup;
use App\Models\Shop\ShopProductVariationValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopProductVariationValueFactory extends Factory
{
    protected $model = ShopProductVariationValue::class;

    public function definition()
    {
        return [
            'group_id' => ShopProductVariationGroup::factory(),
            'caption' => $this->faker->word,
            'price' => $this->faker->optional(0.5)->randomFloat(2, 1, 100),
            'stock_qty' => $this->faker->numberBetween(0, 100),
            'sort_order' => 0,
        ];
    }
}
