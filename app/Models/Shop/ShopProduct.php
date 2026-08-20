<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasShippingDimensions;

class ShopProduct extends Model
{
    use SoftDeletes, HasFactory, HasShippingDimensions;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'base_price',
        'stock_qty',
        'currency',
        'is_active',
        'deactivation_reason',
        'low_stock_threshold',
        'computed_min_price',
        'computed_max_price',
        'weight_kg',
        'length_cm',
        'breadth_cm',
        'height_cm',
        'requires_shipping',
        'size_guide_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
        'stock_qty' => 'integer',
        'low_stock_threshold' => 'integer',
        'computed_min_price' => 'decimal:2',
        'computed_max_price' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'length_cm' => 'decimal:2',
        'breadth_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'requires_shipping' => 'boolean',
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

    public function collection()
    {
        return $this->belongsTo(ShopCollection::class, 'collection_id');
    }

    public function sizeGuide()
    {
        return $this->belongsTo(ShopSizeGuide::class, 'size_guide_id');
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

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            // Variants: at least one variant is low stock
            $q->whereHas('variants', function ($v) {
                $v->whereColumn('stock_qty', '<', 'shop_products.low_stock_threshold');
            })
            // OR Simple Product: stock is low
            ->orWhere(function($sub) {
                $sub->whereDoesntHave('variationGroups')
                    ->whereColumn('stock_qty', '<', 'low_stock_threshold');
            });
        });
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            // Variants: ALL variants are 0 stock (or no variants exist but group exists? No, if group exists variants should exist)
            $q->whereHas('variants')
              ->whereDoesntHave('variants', function ($v) {
                  $v->where('stock_qty', '>', 0);
              })
            // OR Simple Product: stock is 0
            ->orWhere(function($sub) {
                $sub->whereDoesntHave('variationGroups')
                    ->where(function($s) {
                        $s->where('stock_qty', '<=', 0)->orWhereNull('stock_qty');
                    });
            });
        });
    }

    // --- Accessors ---

    public function getComputedStockAttribute(): int
    {
        // 1. Variable Product
        if ($this->relationLoaded('variants')) {
            if ($this->variants->isNotEmpty()) {
                return (int) $this->variants->sum('stock_qty');
            }
        } else {
            if ($this->variants()->exists()) {
                return (int) $this->variants()->sum('stock_qty');
            }
        }

        // 2. Simple Product
        return (int) ($this->stock_qty ?? 0);
    }

    public function getIsOutOfStockAttribute(): bool
    {
        // 1. Variable Product
        if ($this->relationLoaded('variants')) {
            if ($this->variants->isNotEmpty()) {
                return !$this->variants->contains(fn($v) => $v->stock_qty > 0);
            }
        } else {
            if ($this->variants()->exists()) {
                return !$this->variants()->where('stock_qty', '>', 0)->exists();
            }
        }

        // 2. Simple Product
        return ($this->stock_qty ?? 0) <= 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        $threshold = $this->low_stock_threshold ?? 5;

        if ($this->is_out_of_stock) {
            return false;
        }

        // 1. Variable Product
        if ($this->relationLoaded('variants')) {
            if ($this->variants->isNotEmpty()) {
                return $this->variants->contains(fn($v) => $v->stock_qty < $threshold);
            }
        } else {
            if ($this->variants()->exists()) {
                return $this->variants()->where('stock_qty', '<', $threshold)->exists();
            }
        }

        // 2. Simple Product
        return $this->stock_qty < $threshold;
    }

    public function getDisplayIdAttribute(): string
    {
        $hash = strtoupper(substr(md5('shop' . $this->id), 0, 5));
        return "SHP-{$hash}-{$this->id}";
    }
}
