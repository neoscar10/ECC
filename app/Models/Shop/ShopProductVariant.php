<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_product_id',
        'sku',
        'price',
        'stock_qty',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_qty' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the product that owns the variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    /**
     * Get the options (variation values) that define this variant.
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ShopProductVariationValue::class,
            'shop_product_variant_options',
            'shop_product_variant_id',
            'shop_product_variation_value_id'
        );
    }
}
