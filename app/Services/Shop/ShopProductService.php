<?php

namespace App\Services\Shop;

use App\Models\Shop\ShopProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ShopProductService
{
    /**
     * Get paginated shop products with complex filters.
     */
    public function getProducts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ShopProduct::query()->active()->with('images', 'categories', 'tags.group');

        // Text Search
        if (isset($filters['q']) && $filters['q']) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['q'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['q'] . '%');
            });
        }

        // Category Filter (OR logic)
        if (isset($filters['category_ids']) && $filters['category_ids']) {
            $catIds = is_array($filters['category_ids']) 
                ? $filters['category_ids'] 
                : explode(',', $filters['category_ids']);
                
            $query->whereHas('categories', function (Builder $q) use ($catIds) {
                $q->whereIn('shop_categories.id', $catIds);
            });
        }

        // Tags Filter (AND across groups, OR within group)
        if (isset($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $groupSlug => $tagVal) {
                if (!$tagVal) continue;

                $tagIds = is_array($tagVal) ? $tagVal : explode(',', $tagVal);

                $query->whereHas('tags', function ($q) use ($tagIds) {
                    $q->whereIn('shop_tags.id', $tagIds);
                });
            }
        }

        // Price Range
        if (isset($filters['price_min'])) {
            $query->where('base_price', '>=', $filters['price_min']);
        }
        if (isset($filters['price_max'])) {
            $query->where('base_price', '<=', $filters['price_max']);
        }
        
        // In Stock Filter
        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->inStock();
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting(Builder $query, string $sort): void
    {
        switch ($sort) {
            case 'price_asc':
            case 'price_low':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_desc':
            case 'price_high':
                $query->orderBy('base_price', 'desc');
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }
    }
}
