<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopProductVariationGroup extends Model
{
    protected $fillable = [
        'shop_product_id',
        'name',
        'presentation_type',
        'has_images',
        'sort_order',
    ];

    protected $casts = [
        'has_images' => 'boolean',
        'sort_order' => 'integer',
        'shop_product_id' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ShopProductVariationValue::class, 'group_id')->orderBy('sort_order', 'asc');
    }
}
