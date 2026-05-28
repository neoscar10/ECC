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
            'presentation_type' => 'text', // text, image, color
            'has_images' => false,
            'sort_order' => 0,
        ];
    }
}
