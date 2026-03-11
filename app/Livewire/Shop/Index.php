<?php

namespace App\Livewire\Shop;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Shop\ShopCategory;
use App\Services\Shop\ShopProductService;
use App\Services\Shop\CartService;
use Livewire\Attributes\Url;

class Index extends Component
{
    use WithPagination;

    // We can use standard bootstrap simple pagination layout if configured
    protected $paginationTheme = 'bootstrap';

    #[Url(except: '')]
    public $search = '';

    #[Url(except: null)]
    public $activeCategoryId = null;

    #[Url(except: 'newest')]
    public $sort = 'newest';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActiveCategoryId()
    {
        $this->resetPage();
    }

    public function render(ShopProductService $productService, CartService $cartService)
    {
        // 1. Categories (Tree mapping simplified for Chips)
        // Similar to ShopCategoryController->filters() / index()
        $categories = ShopCategory::active()->roots()->orderBy('sort_order')->orderBy('name')->get();

        $newArrivalsPaginator = $productService->getProducts(['sort' => 'newest'], 4);
        $newArrivalsPaginator->getCollection()->loadMissing('variationGroups.values');
        $newArrivals = $newArrivalsPaginator->items();

        // 3. Grid Products (Applying active filters)
        $filters = [
            'q' => $this->search,
            'sort' => $this->sort,
        ];

        if ($this->activeCategoryId) {
            $filters['category_ids'] = [$this->activeCategoryId];
        }

        $products = $productService->getProducts($filters, 16);
        $products->getCollection()->loadMissing('variationGroups.values');

        // 4. Cart Count
        $cartCount = 0;
        if (auth()->check()) {
            $cart = $cartService->getCart(auth()->user());
            $cartCount = $cart->items()->sum('quantity');
        }

        // Map derived fields for Blade rendering to avoid messy Blade logic
        // As requested by PART 5 - PRODUCT / CATEGORY DATA REQUIREMENTS
        $mapProduct = function($p) {
            $img = $p->images->first();
            
            // Replicate pricing checks
            $priceDisplay = '₹' . number_format((float)$p->base_price, 2);
            $oldPriceDisplay = null;
            // No current `compare_at_price` mapping locally, but keeping pattern
            
            return [
                'id' => $p->id,
                'name' => $p->title,
                'slug' => $p->slug,
                'short_description' => $p->description ?? '',
                'image_url' => $img ? url('storage/' . $img->image_path) : null,
                'price_display' => $priceDisplay,
                'old_price_display' => $oldPriceDisplay,
                'is_new' => $p->created_at ? $p->created_at->diffInDays(now()) < 14 : false, // Arbitrary new flag mimicking service pattern
                'is_on_sale' => false,
                'is_sold_out' => $p->computed_stock <= 0,
                'rating' => null, // No ratings in model yet
                'details_url' => route('shop.show', $p->slug)
            ];
        };

        return view('livewire.shop.index', [
            'categories' => $categories,
            'newArrivals' => collect($newArrivals)->map($mapProduct),
            'products' => collect($products->items())->map($mapProduct),
            'paginator' => $products,
            'cartCount' => $cartCount,
        ])->layout('layouts.user.app', [
            'title' => 'Club Store',
            'cartCount' => $cartCount
        ]);
    }
}
