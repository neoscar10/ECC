<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'shop_product_id',
        'quantity',
        'unit_price',
        'currency',
        'selection_signature',
        'shop_product_variant_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    // --- Relationships ---

    /**
     * Get the cart this item belongs to.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the associated product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    /**
     * Get the specific combination (variant) for this item.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariant::class, 'shop_product_variant_id');
    }

    /**
     * Get the pivot records for variation values.
     */
    public function variationValuePivots(): HasMany
    {
        return $this->hasMany(CartItemVariationValue::class, 'cart_item_id');
    }

    /**
     * Get the actual ShopProductVariationValue models through the pivot.
     */
    public function selectedVariations(): HasManyThrough
    {
        return $this->hasManyThrough(
            ShopProductVariationValue::class,
            CartItemVariationValue::class,
            'cart_item_id', // Foreign key on pivot table...
            'id', // Foreign key on target table...
            'id', // Local key on parent...
            'shop_product_variation_value_id' // Local key on pivot table...
        );
    }

    /**
     * Alias for selectedVariations to match service usage (or define as belongsToMany for cleaner pivot access)
     */
    public function variationValues(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            ShopProductVariationValue::class,
            'cart_item_variation_values',
            'cart_item_id',
            'shop_product_variation_value_id'
        )->withTimestamps();
    }

    // --- Helpers ---
    
    public function getLineTotalAttribute()
    {
        return $this->unit_price * $this->quantity;
    }
}
