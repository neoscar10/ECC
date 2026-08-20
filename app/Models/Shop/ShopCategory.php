<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopCategory extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'parent_id' => 'integer',
    ];

    // --- Relationships ---

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ShopCategory::class, 'parent_id')->orderBy('sort_order', 'asc');
    }

    public function sizeGuide(): BelongsTo
    {
        return $this->belongsTo(ShopSizeGuide::class, 'size_guide_id');
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    // --- Accessors ---

    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->exists();
    }
}
