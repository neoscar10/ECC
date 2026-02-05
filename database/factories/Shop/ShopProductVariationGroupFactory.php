<?php

namespace Database\Factories\Shop;

use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariationGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopProductVariationGroupFactory extends Factory
{
    protected $model = ShopProductVariationGroup::class;

    public function definition()
    {
        return [
            'shop_product_id' => ShopProduct::factory(),
            'name' => $this->faker->word,
            'type' => 'radio', // radio, select, image
            'order' => 0,
            'is_required' => true,
        ];
    }
}
