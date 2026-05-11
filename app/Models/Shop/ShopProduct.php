<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopProduct extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'base_price',
        'stock_qty',
        'currency',
        'is_active',
        'deactivation_reason',
        'computed_min_price',
        'computed_max_price',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
        'stock_qty' => 'integer',
        'computed_min_price' => 'decimal:2',
        'computed_max_price' => 'decimal:2',
    ];

    // --- Relationships ---

    // Categories (Pivot)
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ShopCategory::class, 'shop_product_categories', 'shop_product_id', 'shop_category_id');
    }

    // Tags (Pivot with Group ID)
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ShopTag::class, 'shop_product_tags', 'shop_product_id', 'shop_tag_id')
            ->withPivot('shop_tag_group_id');
    }

    // Variation Groups
    public function variationGroups()
    {
        return $this->hasMany(ShopProductVariationGroup::class, 'shop_product_id');
    }

    public function variants()
    {
        return $this->hasMany(ShopProductVariant::class, 'shop_product_id');
    }

    public function getPriceMinAmountAttribute()
    {
        if ($this->variants()->exists()) {
            return $this->variants()->min('price');
        }
        return $this->base_price;
    }

    public function getPriceMaxAmountAttribute()
    {
        if ($this->variants()->exists()) {
            return $this->variants()->max('price');
        }
        return $this->base_price;
    }

    public function getPriceAttribute()
    {
        return $this->base_price;
    }

    // Flattened Variation Values (useful for eager loading or counting)
    public function variationValues()
    {
         return $this->hasManyThrough(ShopProductVariationValue::class, ShopProductVariationGroup::class, 'shop_product_id', 'group_id');
    }

    // Base Gallery Images
    public function images(): HasMany
    {
        return $this->hasMany(ShopProductImage::class, 'shop_product_id')->orderBy('sort_order', 'asc');
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            // Include products that have at least one variant with stock > 0
            $q->whereHas('variants', function ($v) {
                $v->where('stock_qty', '>', 0);
            })
            // OR products that have NO variation groups (Simple products)
            ->orWhere(function($sub) {
                $sub->whereDoesntHave('variationGroups')->where('stock_qty', '>', 0);
            });
        });
    }

    // --- Accessors ---

    public function getComputedStockAttribute(): int
    {
        // 1. Variable Product
        if ($this->variants()->exists()) {
            return (int) $this->variants()->sum('stock_qty');
        }

        // 2. Simple Product
        return (int) ($this->stock_qty ?? 0);
    }

    public function getDisplayIdAttribute(): string
    {
        $hash = strtoupper(substr(md5('shop' . $this->id), 0, 5));
        return "SHP-{$hash}-{$this->id}";
    }
}
