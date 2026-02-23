<?php

namespace App\Services\Archive;

use App\Models\Archive\ArchiveProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ArchiveProductService
{
    /**
     * Get paginated archive products with filters.
     * 
     * @param $user Actor (User)
     * @param $userTier Tier
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProducts($user, $userTier, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ArchiveProduct::query()
            ->with(['category', 'images', 'restrictedMinTier', 'clearViewTiers', 'visibilityTiers'])
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->visibleTo($user, $userTier);

        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('archive_category_id', (int) $filters['category_id']);
        }

        // Sorting
        $query->orderBy('sort_order')->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }
}
