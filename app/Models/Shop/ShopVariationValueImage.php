<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopVariationValueImage extends Model
{
    protected $fillable = [
        'shop_product_variation_value_id',
        'image_path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'shop_product_variation_value_id' => 'integer',
    ];

    public function value(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariationValue::class, 'shop_product_variation_value_id');
    }
}
