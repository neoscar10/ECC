<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopProductVariationValue extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id',
        'caption',
        'price',
        'stock_qty',
        'is_default',
        'presentation_image_path',
        'color_hex',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_qty' => 'integer',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'group_id' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariationGroup::class, 'group_id');
    }

    // Variation Gallery Images
    public function images(): HasMany
    {
        return $this->hasMany(ShopVariationValueImage::class, 'shop_product_variation_value_id')->orderBy('sort_order', 'asc');
    }
}
