<?php

namespace App\Services\Cms;

use App\Models\Shop\ShopProduct;
use App\Models\Archive\ArchiveProduct;
use App\Models\Auctions\AuctionLot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ContentBlockPreviewService
{
    /**
     * Resolve items for the CMS block preview based on configuration.
     */
    public function resolveSliderItems(
        string $source,
        string $mode,
        ?int $categoryId = null,
        array $lotIds = [],
        int $limit = 10,
        array $manualItemIds = [],
        array $slides = []
    ): array {
        
        $items = [];

        // 1. Images Mode (Static Slides)
        if ($mode === 'images') {
            foreach ($slides as $slide) {
                $items[] = [
                    'id' => uniqid(),
                    'title' => $slide['title'] ?? '',
                    'subtitle' => $slide['subtitle'] ?? '',
                    'image' => $slide['image_url'] ?? null,
                    'price' => null, // No price for static images
                ];
            }
            return $items;
        }

        // 2. Manual Mode
        if ($mode === 'manual') {
            if ($source === 'shop') {
                return $this->resolveShopItems(null, $limit, $manualItemIds);
            } elseif ($source === 'archive') {
                return $this->resolveArchiveItems(null, $limit, $manualItemIds);
            } elseif ($source === 'auctions') {
                return $this->resolveAuctionLots($manualItemIds);
            }
            return []; 
        }

        // 3. Category / Source Mode
        if ($source === 'shop') {
            $items = $this->resolveShopItems($categoryId, $limit);
        } elseif ($source === 'archive') {
            $items = $this->resolveArchiveItems($categoryId, $limit);
        } elseif ($source === 'auctions') {
            // Auctions source typically uses "Select Lots" which acts like a manual selection of lots
            // OR if there's a category logic for auctions (unlikely per current schema), we'd use that.
            // The prompt implies "Select Lots" is the UI for auctions.
            $items = $this->resolveAuctionLots($lotIds);
        }

        return $items;
    }

    protected function resolveShopItems(?int $categoryId, int $limit, array $ids = []): array
    {
        if (!$categoryId && empty($ids)) return [];

        $query = ShopProduct::query()->active();
        
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query->whereHas('categories', function($q) use ($categoryId) {
                $q->where('shop_categories.id', $categoryId);
            })->take($limit);
        }

        // Eager load images for preview
        $products = $query->with(['images'])->get();

        return $products->map(function($product) {
            return [
                'id' => $product->id,
                'title' => $product->title, // Model uses 'title' or 'name'? Index.php used display_name ?? name. Model has 'title'. Let's check model again.
                // ShopProduct.php has 'title', 'slug', 'description'.
                // Index.php code: 'title' => $p->display_name ?? $p->name. 
                // Wait, ShopProduct model shown earlier has 'title'. 
                // Let's rely on 'title'.
                'image' => $this->resolveImage($product->images->first()?->image_path),
                'price' => 'INR ' . number_format($product->base_price), 
                // logic for sale price if exists would go here
            ];
        })->toArray();
    }

    protected function resolveArchiveItems(?int $categoryId, int $limit, array $ids = []): array
    {
        if (!$categoryId && empty($ids)) return [];

        $query = ArchiveProduct::query(); // Add active/visible scopes if needed
        
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query->where('archive_category_id', $categoryId)->take($limit);
        }
        
        $products = $query->with(['images'])->get();

        return $products->map(function($product) {
            $priceLabel = 'Price on Request';
            if ($product->price_min_amount && $product->price_max_amount) {
                if ($product->price_min_amount == $product->price_max_amount) {
                    $priceLabel = 'INR ' . number_format($product->price_min_amount);
                } else {
                    $priceLabel = 'INR ' . number_format($product->price_min_amount) . ' - ' . number_format($product->price_max_amount);
                }
            } elseif ($product->price_min_amount) {
                 $priceLabel = 'From INR ' . number_format($product->price_min_amount);
            }

            return [
                'id' => $product->id,
                'title' => $product->title ?? 'Untitled', 
                // ArchiveProduct has 'title' (implied by content). The model file didn't explicitly show 'title' in fillable, but accessed it in Index.php.
                // Re-read ArchiveProduct.php... protected $guarded = []; so we don't know fields from fillable.
                // Index.php usage: $p->title. Safe to assume title.
                'image' => $this->resolveImage($product->images->first()?->image_path),
                'price' => $priceLabel,
            ];
        })->toArray();
    }

    protected function resolveAuctionLots(array $lotIds): array
    {
        if (empty($lotIds)) return [];

        $lots = AuctionLot::whereIn('id', $lotIds)->with(['images'])->get();

        // Preserve order based on $lotIds
        $orderedStats = collect($lotIds)->map(function($id) use ($lots) {
            return $lots->firstWhere('id', $id);
        })->filter();

        return $orderedStats->map(function($lot) {
            $price = $lot->current_highest_bid > 0 
                ? 'INR ' . number_format($lot->current_highest_bid) . ' (Bid)'
                : 'INR ' . number_format($lot->starting_price);

            return [
                'id' => $lot->id,
                'title' => $lot->title ?? 'Lot #' . $lot->lot_no, // Corrected lot_no
                'image' => $this->resolveImage($lot->images->first()?->path),
                'price' => $price,
            ];
        })->toArray();
    }

    protected function resolveImage(?string $path): ?string
    {
        if (!$path) return null;
        return asset('storage/' . $path);
    }
}
