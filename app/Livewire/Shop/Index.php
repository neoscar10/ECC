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

    // Quick View Modal State
    public $quickViewProduct = null;
    public $quickViewQuantity = 1;
    public $selectedVariationValues = []; // keyed by group_id -> value_id
    public $variationGroups = [];
    public $availableOptions = []; // groupId -> [valueIds]
    public $currentGallery = [];
    public $computedPriceDisplay = null;
    public $availabilityLabel = null;
    public $inStock = true;
    public $selectedMediaIndex = 0;

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

        $product = ShopProduct::with(['variationGroups'])->findOrFail($productId);

        if ($product->variationGroups->isNotEmpty()) {
            // It has variations! Instead of adding default, open the premium quick view modal!
            $this->openQuickView($productId);
            return;
        }

        // Simple product: add it directly to cart!
        try {
            $cart = $cartService->addItem(
                user: auth()->user(),
                productId: $productId,
                quantity: 1,
                variationValueIds: []
            );

            $cartCount = $cart->items()->sum('quantity');
            $this->dispatch('refresh-cart-badge', count: $cartCount);
            
            session()->flash('success', 'Added to cart successfully.');
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openQuickView($productId)
    {
        $this->quickViewProduct = ShopProduct::with([
            'images', 
            'categories.parent', 
            'tags.group', 
            'variationGroups.values.images',
            'variants.optionValues'
        ])->active()->findOrFail($productId);

        $this->variationGroups = $this->quickViewProduct->variationGroups->toArray();
        $this->selectedVariationValues = [];
        $this->quickViewQuantity = 1;
        $this->selectedMediaIndex = 0;

        foreach ($this->quickViewProduct->variationGroups as $group) {
            $def = $group->values->where('is_default', true)->first();
            if (!$def && $group->values->isNotEmpty()) {
                $def = $group->values->first();
            }
            if ($def) {
                $this->selectedVariationValues[$group->id] = $def->id;
            }
        }

        $this->recomputeQuickViewDynamicState();
    }

    protected function recomputeQuickViewDynamicState()
    {
        if (!$this->quickViewProduct) {
            return;
        }

        // Ensure all values are integers to prevent type mismatch during strict comparisons
        $this->selectedVariationValues = collect($this->selectedVariationValues)
            ->map(fn($val) => is_numeric($val) ? (int)$val : $val)
            ->toArray();

        $allVariants = $this->quickViewProduct->variants->where('is_active', true);
        $inStockVariants = $allVariants->where('stock_qty', '>', 0);
        
        $groups = $this->quickViewProduct->variationGroups;
        $this->availableOptions = [];

        foreach ($groups as $group) {
            $otherSelections = collect($this->selectedVariationValues)->forget($group->id);
            
            $availableInThisGroup = $inStockVariants->filter(function($variant) use ($otherSelections) {
                $variantOptionIds = $variant->optionValues->pluck('id')->toArray();
                foreach ($otherSelections as $otherGroupId => $otherValueId) {
                    if (!in_array($otherValueId, $variantOptionIds)) {
                        return false;
                    }
                }
                return true;
            })->flatMap(function($variant) {
                return $variant->optionValues;
            })->where('group_id', $group->id)->pluck('id')->unique()->values()->toArray();
            
            $this->availableOptions[$group->id] = $availableInThisGroup;
        }

        $selectedCount = count($this->selectedVariationValues);
        $totalGroupCount = count($groups);
        $matchedVariant = null;

        if ($selectedCount === $totalGroupCount && $totalGroupCount > 0) {
            $selectedIds = collect($this->selectedVariationValues)->values()->sort()->values()->toArray();
            
            $matchedVariant = $allVariants->filter(function($v) use ($selectedIds) {
                $vIds = $v->optionValues->pluck('id')->sort()->values()->toArray();
                return $vIds === $selectedIds;
            })->first();
        }

        if ($matchedVariant) {
            $this->computedPriceDisplay = number_format($matchedVariant->price ?? $this->quickViewProduct->base_price, 2, '.', '');
            $this->inStock = $matchedVariant->stock_qty > 0;
            $this->availabilityLabel = $this->inStock ? null : 'Out of Stock';
        } else {
            if ($totalGroupCount > 0) {
                $this->computedPriceDisplay = number_format($this->quickViewProduct->base_price, 2, '.', '');
                $this->inStock = false;
                $this->availabilityLabel = 'Select options';
            } else {
                $this->computedPriceDisplay = number_format($this->quickViewProduct->base_price, 2, '.', '');
                $this->inStock = $this->quickViewProduct->stock_qty > 0;
                $this->availabilityLabel = $this->inStock ? null : 'Out of Stock';
            }
        }

        $galleryControlGroup = $this->quickViewProduct->variationGroups->where('has_images', true)->first();
        $newGallery = [];

        if ($galleryControlGroup && isset($this->selectedVariationValues[$galleryControlGroup->id])) {
            $controllingValueId = $this->selectedVariationValues[$galleryControlGroup->id];
            $controllingVal = $galleryControlGroup->values->firstWhere('id', $controllingValueId);
            
            if ($controllingVal && $controllingVal->relationLoaded('images') && $controllingVal->images->isNotEmpty()) {
                $newGallery = $controllingVal->images->map(function ($i) {
                    return [
                        'id' => $i->id,
                        'url' => url('storage/' . $i->image_path),
                        'thumb_url' => url('storage/' . $i->image_path)
                    ];
                })->toArray();
            }
        }

        if (empty($newGallery)) {
            $newGallery = $this->quickViewProduct->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => url('storage/' . $img->image_path),
                    'thumb_url' => url('storage/' . $img->image_path)
                ];
            })->toArray();
        }

        $this->currentGallery = $newGallery;

        if (!isset($this->currentGallery[$this->selectedMediaIndex])) {
            $this->selectedMediaIndex = 0;
        }
    }

    public function updatedSelectedVariationValues($value, $key)
    {
        $this->recomputeQuickViewDynamicState();
    }

    public function selectVariationValue($groupId, $valueId)
    {
        if (isset($this->availableOptions[$groupId]) && !in_array($valueId, $this->availableOptions[$groupId])) {
            return;
        }

        $this->selectedVariationValues[$groupId] = $valueId;
        $this->recomputeQuickViewDynamicState();
    }

    public function selectMedia($index)
    {
        if (isset($this->currentGallery[$index])) {
            $this->selectedMediaIndex = $index;
        }
    }

    public function incrementQuickViewQuantity()
    {
        if ($this->quickViewQuantity < 99) {
            $this->quickViewQuantity++;
        }
    }

    public function decrementQuickViewQuantity()
    {
        if ($this->quickViewQuantity > 1) {
            $this->quickViewQuantity--;
        }
    }

    public function closeQuickView()
    {
        $this->quickViewProduct = null;
        $this->selectedVariationValues = [];
        $this->quickViewQuantity = 1;
        $this->currentGallery = [];
    }

    public function addQuickViewToCart(CartService $cartService)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!$this->inStock || !$this->quickViewProduct) {
            session()->flash('error', 'The selected options are currently out of stock.');
            return;
        }

        try {
            $variationValueIds = array_values($this->selectedVariationValues);
            
            $cart = $cartService->addItem(
                user: auth()->user(),
                productId: $this->quickViewProduct->id,
                quantity: $this->quickViewQuantity,
                variationValueIds: $variationValueIds
            );
            
            $cartCount = $cart->items()->sum('quantity');
            $this->dispatch('refresh-cart-badge', count: $cartCount);
            
            session()->flash('success', 'Added to cart successfully.');
            $this->closeQuickView();

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
