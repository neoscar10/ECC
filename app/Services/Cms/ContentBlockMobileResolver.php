<?php

namespace App\Services\Cms;

use App\Models\Cms\CmsBlock;
use App\Models\User;
use App\Models\Shop\ShopProduct;
use App\Models\Archive\ArchiveProduct;
use App\Models\Auctions\AuctionLot;
use App\Services\Cms\CmsBlockAccessResolverService;
use App\Support\Archive\AccessIconNormalizer;
use Illuminate\Support\Facades\Storage;

class ContentBlockMobileResolver
{
    protected CmsBlockAccessResolverService $accessResolver;

    public function __construct(CmsBlockAccessResolverService $accessResolver)
    {
        $this->accessResolver = $accessResolver;
    }

    /**
     * Resolve a CMS Block into a Mobile-friendly DTO array using a specific tier (for preview).
     */
    public function resolveForTier(CmsBlock $block, ?int $tierId, bool $includeItems = true, int $itemLimit = 10, bool $includeDetail = false): array
    {
        $access = $this->resolveAccessForTier($block, $tierId);
        $tier = $tierId ? \App\Models\MembershipTier::find($tierId) : null;
        return $this->buildBlockStructure($block, $access, $includeItems, $itemLimit, $includeDetail, null, $tier);
    }

    /**
     * Resolve a CMS Block into a Mobile-friendly DTO array.
     */
    public function resolve(CmsBlock $block, ?User $user, bool $includeItems = true, int $itemLimit = 10, bool $includeDetail = false): array
    {
        $access = $this->resolveAccess($block, $user);
        $tier = $user?->currentMembership?->membershipTier;
        return $this->buildBlockStructure($block, $access, $includeItems, $itemLimit, $includeDetail, $user, $tier);
    }

    protected function buildBlockStructure(CmsBlock $block, array $access, bool $includeItems, int $itemLimit, bool $includeDetail, ?User $user = null, ?\App\Models\MembershipTier $userTier = null): array
    {
        $isClear = ($access['view_mode'] === 'clear');
        $content = $block->content ?? [];

        // Base Block Structure
        $mobileBlock = [
            'id' => $block->id ?? uniqid(), // Handle in-memory blocks without IDs
            'placement' => $block->placement ?? 'home',
            'type' => $block->type, // banner, slider, card, text
            'sort_order' => $block->sort_order,
            'is_active' => $block->is_active,
            
            // Text Content
            'title' => $content['title'] ?? $block->title,
            'subtitle' => $content['subtitle'] ?? null,
            'badge_text' => $content['badge'] ?? null,
            
            // Media
            'media' => [
                'image_url' => $this->resolveImageUrl($content['image_url'] ?? null),
                'image_mobile_url' => $this->resolveImageUrl($content['image_mobile_url'] ?? null),
            ],

            // UI Config
            'text_position' => $block->type_config['text_position'] ?? 'below',
            
            // Detail Page Config
            'has_detail_page' => $content['has_detail_page'] ?? false,
            'cta_text' => $isClear ? ($content['cta_text'] ?? null) : null,
            'detail_endpoint' => ($content['has_detail_page'] ?? false) 
                ? "/api/v1/content/blocks/{$block->id}" 
                : null,
            
            // Access State
            'access' => $access,
        ];
        
        // Target Routing
        if (isset($content['has_target']) && $content['has_target']) {
            $mobileBlock['has_target'] = true;
            if ($isClear && isset($content['target'])) {
                $mobileBlock['target'] = $content['target'];
            }
        }

        // 3. Body Text (Only if clear)
        if ($isClear) {
            $mobileBlock['body_text'] = $content['body'] ?? null;
            
            // Add Detail Markdown if requested and available
            if ($includeDetail && ($content['has_detail_page'] ?? false)) {
                $mobileBlock['detail_markdown'] = $content['detail_markdown'] ?? null;
            }
        }

        // 4. Slider Resolution
        if ($block->type === 'slider') {
            $mobileBlock['slider'] = $this->resolveSliderData($block, $includeItems, $itemLimit, $user, $userTier);
            
            // If slider mode is 'images', populate media.slides
            if (($block->type_config['mode'] ?? '') === 'images') {
                $mobileBlock['media']['slides'] = $mobileBlock['slider']['items'] ?? [];
            }
        }

        return $mobileBlock;
    }

    protected function resolveAccess(CmsBlock $block, ?User $user): array
    {
        $access = $this->accessResolver->resolve($block, $user);
        return $this->formatAccessOutput($access);
    }

    protected function resolveAccessForTier(CmsBlock $block, ?int $tierId): array
    {
        $access = $this->accessResolver->resolveForTier($block, $tierId);
        return $this->formatAccessOutput($access);
    }

    protected function formatAccessOutput(array $access): array
    {

        // Normalize Icon
        $access['message']['icon'] = AccessIconNormalizer::normalize(
            $access['reason'] ?? null, 
            $access['view_mode'] ?? 'blocked'
        );
        
        $access['state'] = match($access['view_mode']) {
            'clear' => 'public',
            'blur' => 'teaser',
            default => 'locked'
        };
        
        $access['is_allowed'] = ($access['view_mode'] === 'clear');
        $access['show_teaser'] = ($access['view_mode'] === 'blur');
        
        // Ensure required_tier structure is complete if present
        if (isset($access['required_tier']) && is_array($access['required_tier'])) {
             // Basic tier info is usually enough
        }

        return $access;
    }

    protected function resolveImageUrl(?string $url): ?string
    {
        if (!$url) return null;
        if (filter_var($url, FILTER_VALIDATE_URL)) return $url;
        return Storage::url($url);
    }

    protected function resolveSliderData(CmsBlock $block, bool $includeItems, int $limit, ?User $user = null, ?\App\Models\MembershipTier $userTier = null): array
    {
        $config = $block->type_config ?? [];
        $mode = $config['mode'] ?? 'category';
        $source = $config['source'] ?? 'shop';
        
        $sliderData = [
            'mode' => $mode,
            'source' => $source,
            'item_limit' => min($limit, 20),
            'items' => [],
        ];

        if (!$includeItems) {
            return $sliderData;
        }

        if ($mode === 'images') {
             $sliderData['items'] = $this->resolveStaticSlides($config['slides'] ?? []);
        } elseif ($mode === 'category') {
             if ($source === 'shop') {
                 $sliderData['items'] = $this->resolveShopItems($config['category_id'] ?? null, $sliderData['item_limit'], $user, $userTier);
             } elseif ($source === 'archive') {
                 $sliderData['items'] = $this->resolveArchiveItems($config['category_id'] ?? null, $sliderData['item_limit'], $user, $userTier);
             } elseif ($source === 'auctions') {
                 $sliderData['items'] = $this->resolveAuctionLots($config['items'] ?? [], $user, $userTier); 
             }
        } elseif ($mode === 'manual') {
             // Manual items not fully supported in requested scope, returning empty or could implement via IDs
             // $ids = collect($config['items'] ?? [])->pluck('id')->toArray();
        }

        return $sliderData;
    }

    protected function resolveStaticSlides(array $slides): array
    {
        return collect($slides)->map(function($slide) {
            return [
                'kind' => 'static_slide',
                'id' => uniqid(),
                'title' => $slide['title'] ?? '',
                'subtitle' => $slide['subtitle'] ?? '',
                'image_url' => $this->resolveImageUrl($slide['image_url'] ?? null),
                'action_url' => null,
            ];
        })->toArray();
    }

    protected function resolveShopItems(?int $categoryId, int $limit, ?User $user = null, ?\App\Models\MembershipTier $userTier = null): array
    {
        if (!$categoryId) return [];

        $query = ShopProduct::active()
            ->whereHas('categories', fn($q) => $q->where('shop_categories.id', $categoryId))
            ->with(['images'])
            ->take($limit);
            
        return $query->get()->map(function($product) {
            return [
                'kind' => 'shop_product',
                'id' => $product->id,
                'title' => $product->title,
                'image_url' => $this->resolveImageUrl($product->images->first()?->image_path), // Shop uses image_path
                'price_label' => 'INR ' . number_format($product->base_price),
                'price_meta' => [
                    'currency' => $product->currency ?? 'INR',
                    'amount' => (float) $product->base_price,
                ],
                // Open access for Shop logic
                'access' => [
                    'view_mode' => 'clear',
                    'message' => ['icon' => null],
                ],
                'status' => 'active', 
                'detail_endpoint' => "/api/v1/shop/products/{$product->id}",
            ];
        })->toArray();
    }

    protected function resolveArchiveItems(?int $categoryId, int $limit, ?User $user = null, ?\App\Models\MembershipTier $userTier = null): array
    {
        if (!$categoryId) return [];

        $query = ArchiveProduct::where('archive_category_id', $categoryId)
            ->visibleTo($user, $userTier)
            ->with(['images'])
            ->take($limit);

        $accessResolver = app(\App\Services\Archive\ArchiveAccessResolver::class);

        return $query->get()->map(function($product) use ($accessResolver, $user, $userTier) {
            $min = $product->price_min_amount;
            $max = $product->price_max_amount;
            
            $label = 'Price on Request';
            if ($min && $max) {
                 $label = ($min == $max) 
                    ? 'INR ' . number_format($min)
                    : 'INR ' . number_format($min) . ' - ' . number_format($max);
            } elseif ($min) {
                 $label = 'From INR ' . number_format($min);
            }

            $access = $accessResolver->resolveProductAccess($product, $user, $userTier);
            $access['message']['icon'] = AccessIconNormalizer::normalize(
                $access['reason'] ?? null, 
                $access['view_mode'] ?? 'blocked'
            );

            return [
                'kind' => 'archive_item',
                'id' => $product->id,
                'title' => $product->title ?? 'Untitled', 
                'image_url' => $this->resolveImageUrl($product->images->first()?->image_path), // Archive uses image_path
                'price_label' => $label,
                'price_meta' => [
                    'currency' => 'INR',
                    'range_min' => $min,
                    'range_max' => $max,
                ],
                'access' => $access,
                'status' => null,
                'detail_endpoint' => "/api/v1/archive/products/{$product->id}",
            ];
        })->toArray();
    }

    protected function resolveAuctionLots(array $lotItems, ?User $user = null, ?\App\Models\MembershipTier $userTier = null): array
    {
        $ids = collect($lotItems)->pluck('id')->filter()->toArray();
        if (empty($ids)) return [];

        $lots = AuctionLot::whereIn('id', $ids)->with(['images'])->get();
        $ordered = collect($ids)->map(fn($id) => $lots->firstWhere('id', $id))->filter();
        
        $accessResolver = app(\App\Services\Auctions\AuctionAccessResolverService::class);

        return $ordered->map(function($lot) use ($accessResolver, $user, $userTier) {
            $price = $lot->current_highest_bid > 0 ? $lot->current_highest_bid : $lot->starting_price;
            
            $access = $accessResolver->resolve($lot, $user, $userTier);
            $access['message']['icon'] = AccessIconNormalizer::normalize(
                $access['reason'] ?? null, 
                $access['view_mode'] ?? 'blocked'
            );

            return [
                'kind' => 'auction_lot',
                'id' => $lot->id,
                'title' => $lot->title ?? 'Lot #' . $lot->lot_no,
                'image_url' => $this->resolveImageUrl($lot->images->first()?->path), // Auction uses path
                'price_label' => 'INR ' . number_format($price),
                'price_meta' => [
                    'currency' => 'INR',
                    'current_bid' => (float) $lot->current_highest_bid,
                    'starting_price' => (float) $lot->starting_price,
                ],
                'access' => $access,
                'status' => $lot->status, // live, upcoming, ended
                'detail_endpoint' => "/api/v1/auctions/{$lot->id}",
            ];
        })->toArray();
    }
}
