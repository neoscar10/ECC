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

        if (isset($filters['q']) && $filters['q']) {
            $query->where('title', 'like', '%' . $filters['q'] . '%');
        }

        // Sorting
        $query->orderBy('sort_order')->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get the full product detail DTO for web and API show.
     */
    public function getProductDetailDto($user, $userTier, int $id): array
    {
        $product = ArchiveProduct::with([
            'category', 
            'images', 
            'images360', 
            'attachments',
            'restrictedMinTier',
            'restrictedPrivateTier',
            'clearViewTiers',
            'visibilityTiers'
        ])->find($id);
        
        if (!$product || !$product->is_active) {
            abort(404);
        }

        $resolver = app(\App\Services\Archive\ArchiveAccessResolver::class);
        $productAccess = $resolver->resolveProductAccess($product, $user, $userTier);

        if (($productAccess['view_mode'] ?? 'blocked') === 'blocked') {
            abort(403, 'Access Denied');
        }

        // Map facts
        $facts = [];
        if ($product->origin) $facts[] = ['label' => 'Origin', 'value' => $product->origin];
        if ($product->material) $facts[] = ['label' => 'Material', 'value' => $product->material];
        if ($product->condition) $facts[] = ['label' => 'Condition', 'value' => $product->condition];
        if ($product->dimensions) $facts[] = ['label' => 'Dimensions', 'value' => $product->dimensions];

        // Map chips
        $chips = [];
        if ($product->condition) $chips[] = ['label' => 'Condition: ' . $product->condition];
        if ($product->kicker) $chips[] = ['label' => $product->kicker];

        // Map sections from attachments
        $sections = [];
        
        // Add Imagery Section (Automatic from images)
        if ($product->images->count() > 1) {
            $sections[] = [
                'type' => 'gallery',
                'title' => 'Detailed Imagery',
                'content' => $product->images->map(fn($img) => ['url' => \Storage::url($img->image_path)])->toArray(),
                'access' => ['can_view' => true]
            ];
        }

        // Add attachments as sections
        foreach ($product->attachments->where('is_active', true) as $att) {
            $attAccess = $resolver->resolveAttachmentAccess($att, $product, $user, $userTier);
            $canView = ($attAccess['view_mode'] === 'clear');

            // Normalize type and map content
            $type = $att->type;
            $content = null;

            if ($canView) {
                if ($type === 'rich') {
                    $type = 'markdown';
                    $content = $att->body;
                } elseif ($type === 'kv') {
                    $type = 'key_values';
                    $content = [['label' => $att->kv_key, 'value' => $att->kv_value]];
                } elseif ($type === 'line') {
                    $type = 'line_items';
                    $content = [['label' => $att->heading, 'value' => $att->line_text]];
                }
            } else {
                // Return normalized types even if locked so view renders correct "style" of lock
                if ($type === 'rich') $type = 'markdown';
                elseif ($type === 'kv') $type = 'key_values';
                elseif ($type === 'line') $type = 'line_items';
            }
            
            $sections[] = [
                'type' => $type,
                'title' => ($type === 'line_items') ? null : ($att->heading ?: $att->title),
                'content' => $content,
                'access' => [
                    'can_view' => $canView,
                    'lock_title' => $attAccess['message']['title'] ?? 'Inner Circle Access',
                    'lock_hint' => $attAccess['message']['body'] ?? 'Upgrade to view this section.',
                    'icon' => $attAccess['message']['icon'] ?? 'lock',
                    'raw_access' => $attAccess // For advanced view logic if needed
                ]
            ];
        }

        return [
            'id' => $product->id,
            'title' => $product->title,
            'kicker' => $product->category?->title ?? 'ECC Archive',
            'hero_image_url' => $product->images->first() ? \Storage::url($product->images->first()->image_path) : null,
            'ctas' => [
                'view_360_url' => $product->images360->count() > 0 ? '#' : null,
            ],
            'chips' => $chips,
            'facts' => $facts,
            'sections' => $sections,
            'estimate' => [
                'label' => 'Current Estimate',
                'value' => $this->formatEstimate($product),
            ],
            'enquire' => [
                'enabled' => (($productAccess['view_mode'] ?? 'blocked') !== 'blocked'),
                'action_url' => null
            ],
            'access' => $productAccess
        ];
    }

    private function formatEstimate(ArchiveProduct $product): string
    {
        if ($product->estimate_text) {
            return $product->estimate_text;
        }

        if ($product->price_min_amount && $product->price_max_amount) {
            return '₹' . number_format($product->price_min_amount / 100) . 'k - ₹' . number_format($product->price_max_amount / 100) . 'k';
        }

        if ($product->price_min_amount) {
            return 'From ₹' . number_format($product->price_min_amount / 100) . 'k';
        }

        return 'Request';
    }
}
