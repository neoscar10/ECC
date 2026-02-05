<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItemVariationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_item_id',
        'shop_product_variation_value_id',
    ];

    // --- Relationships ---

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    public function variationValue(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariationValue::class, 'shop_product_variation_value_id');
    }
}
