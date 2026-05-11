<?php

namespace App\Livewire\Shop;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Shop\ShopCategory;
use App\Models\Shop\ShopTagGroup;
use App\Models\Shop\ShopProduct;
use App\Services\Shop\ShopProductService;
use App\Services\Shop\CartService;
use Livewire\Attributes\Url;
use Exception;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(except: '')]
    public $search = '';

    #[Url(except: null)]
    public $activeCategoryId = null;

    #[Url(except: 'newest')]
    public $sort = 'newest';

    #[Url(except: [])]
    public $tags = []; // Flat array of tag IDs for URL simplicity

    public $minPrice = '';
    public $maxPrice = '';

    public $absoluteMinPrice = 0;
    public $absoluteMaxPrice = 0;

    public function mount()
    {
        $this->absoluteMinPrice = (int) (ShopProduct::query()->active()->min('base_price') ?? 0);
        $this->absoluteMaxPrice = (int) (ShopProduct::query()->active()->max('base_price') ?? 1500);

        if ($this->minPrice === '' || $this->minPrice === null) {
            $this->minPrice = $this->absoluteMinPrice;
        } else {
            $this->minPrice = max($this->absoluteMinPrice, min((int) $this->minPrice, $this->absoluteMaxPrice));
        }

        if ($this->maxPrice === '' || $this->maxPrice === null) {
            $this->maxPrice = $this->absoluteMaxPrice;
        } else {
            $this->maxPrice = max($this->absoluteMinPrice, min((int) $this->maxPrice, $this->absoluteMaxPrice));
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActiveCategoryId()
    {
        $this->resetPage();
    }

    public function updatingSort()
    {
        $this->resetPage();
    }

    public function updatedMinPrice($value): void
    {
        $value = (int) $value;

        $absoluteMin = (int) ($this->absoluteMinPrice ?? 0);
        $absoluteMax = (int) ($this->absoluteMaxPrice ?? $value);

        $value = max($absoluteMin, min($value, $absoluteMax));
        $this->minPrice = min($value, (int) ($this->maxPrice ?? $absoluteMax));

        $this->resetPage();
    }

    public function updatedMaxPrice($value): void
    {
        $value = (int) $value;

        $absoluteMin = (int) ($this->absoluteMinPrice ?? 0);
        $absoluteMax = (int) ($this->absoluteMaxPrice ?? $value);

        $value = min($absoluteMax, max($value, $absoluteMin));
        $this->maxPrice = max($value, (int) ($this->minPrice ?? $absoluteMin));

        $this->resetPage();
    }

    public function toggleTag($tagId)
    {
        $tagId = (int)$tagId;
        if (in_array($tagId, $this->tags)) {
            $this->tags = array_values(array_diff($this->tags, [$tagId]));
        } else {
            $this->tags[] = $tagId;
        }
        $this->resetPage();
    }

    public function addToCart(CartService $cartService, $productId)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        try {
            $cart = $cartService->addItem(
                user: auth()->user(),
                productId: $productId,
                quantity: 1,
                variationValueIds: [] // Assuming base product add, requires specific UI for variations typically
            );

            $cartCount = $cart->items()->sum('quantity');
            $this->dispatch('refresh-cart-badge', count: $cartCount);
            
            session()->flash('success', 'Added to cart successfully.');
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(ShopProductService $productService, CartService $cartService)
    {
        // 1. Categories
        $categories = ShopCategory::active()->roots()->with(['children' => function($q) {
            $q->active()->orderBy('sort_order')->orderBy('name');
        }])->orderBy('sort_order')->orderBy('name')->get();

        // 2. Tag Groups
        $tagGroups = ShopTagGroup::active()->with(['tags' => function ($q) {
            $q->active()->orderBy('sort_order', 'asc');
        }])->orderBy('sort_order', 'asc')->get();

        // Prepare tags for format needed by getProducts, which expects ['group_slug' => [id1, id2]]
        $groupedTags = [];
        if (!empty($this->tags)) {
            foreach ($tagGroups as $group) {
                // Find any selected tags that belong to this group
                $groupSelectedTags = $group->tags->whereIn('id', $this->tags)->pluck('id')->toArray();
                if (!empty($groupSelectedTags)) {
                    $groupedTags[$group->slug] = $groupSelectedTags;
                }
            }
        }

        // 3. Grid Products (Applying active filters)
        $filters = [
            'q' => $this->search,
            'sort' => $this->sort,
        ];

        if ($this->activeCategoryId) {
            $catIds = [$this->activeCategoryId];
            $category = ShopCategory::find($this->activeCategoryId);
            if ($category) {
                // Also include all children IDs for a broader search when parent is selected
                $catIds = array_merge($catIds, $category->children()->pluck('id')->toArray());
            }
            $filters['category_ids'] = $catIds;
        }

        if (!empty($groupedTags)) {
            $filters['tags'] = $groupedTags;
        }

        if ($this->minPrice !== null && $this->minPrice !== '') {
            $filters['price_min'] = (float)$this->minPrice;
        }

        if ($this->maxPrice !== null && $this->maxPrice !== '') {
            $filters['price_max'] = (float)$this->maxPrice;
        }

        $products = $productService->getProducts($filters, 16);
        $products->getCollection()->loadMissing(['variationGroups.values', 'categories', 'tags']);

        return view('livewire.shop.index', [
            'categories' => $categories,
            'tagGroups' => $tagGroups,
            'products' => $products,
        ])->layout('layouts.web-app', [
            'title' => 'Club Store',
        ]);
    }
}
