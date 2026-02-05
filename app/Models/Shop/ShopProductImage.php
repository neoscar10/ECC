<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopProductImage extends Model
{
    protected $fillable = [
        'shop_product_id',
        'image_path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'shop_product_id' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }
    
    // Accessor for full URL if needed, but usually handled in resource/API
}
