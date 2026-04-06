<?php

namespace App\Livewire\Shop;

use App\Models\Shop\ShopProduct;
use App\Services\Shop\CartService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public $slug;
    public $product;
    
    // UI State
    public $quantity = 1;
    
    // Dynamic Variant State
    public $selectedVariationValues = []; // keyed by group_id -> value_id
    public $variationGroups = [];
    public $availableOptions = []; // groupId -> [valueIds]
    public $currentGallery = [];
    public $computedPriceDisplay = null;
    public $availabilityLabel = null;
    public $inStock = true;
    public $selectedMediaIndex = 0;
    
    public function mount($slug)
    {
        $this->slug = $slug;
        $this->loadProduct();
    }

    protected function loadProduct()
    {
        // Match ShopProductController::show's eager loading depth to reuse service correctly
        $this->product = ShopProduct::with([
            'images', 
            'categories.parent', 
            'tags.group', 
            'variationGroups.values.images',
            'variants.optionValues' // Added variants for combination-aware logic
        ])->active()->where('slug', $this->slug)->firstOrFail();

        // Standardize groups for view
        $this->variationGroups = $this->product->variationGroups->toArray();
        $this->selectedVariationValues = [];

        // Evaluate Defaults
        foreach ($this->product->variationGroups as $group) {
            $def = $group->values->where('is_default', true)->first();
            if (!$def && $group->values->isNotEmpty()) {
                $def = $group->values->first();
            }
            if ($def) {
                $this->selectedVariationValues[$group->id] = $def->id;
            }
        }

        $this->recomputeDynamicState();
    }

    public function selectVariationValue($groupId, $valueId)
    {
        // Don't allow selecting disabled options
        if (isset($this->availableOptions[$groupId]) && !in_array($valueId, $this->availableOptions[$groupId])) {
            return;
        }

        $this->selectedVariationValues[$groupId] = $valueId;
        
        // After changing one variation, we must ensure other selections are still valid.
        // If not, they will be cleared in recomputeDynamicState or we can do it here.
        // recomputeDynamicState will handle the "availableOptions" map.
        
        $this->recomputeDynamicState();
    }

    public function selectMedia($index)
    {
        if (isset($this->currentGallery[$index])) {
            $this->selectedMediaIndex = $index;
        }
    }

    protected function recomputeDynamicState()
    {
        $allVariants = $this->product->variants->where('is_active', true);
        $inStockVariants = $allVariants->where('stock_qty', '>', 0);
        
        $groups = $this->product->variationGroups;
        $this->availableOptions = [];

        // 1. Calculate Available Options per Group
        // An option is available if it belongs to at least one active, in-stock variant 
        // that matches all CURRENT selections in OTHER groups.
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

        // 2. Validate current selections
        // If a currently selected value is no longer "available" (compatible with other choices), 
        // we keep it selected but it will mark the whole thing as Out of Stock, 
        // OR we can clear it. Clearing is better for "constraint" UX.
        // However, the user flow usually blocks clicking disabled ones anyway.
        
        // 3. Resolve the Specific Variant for Price and Stock
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

        // 4. Update Display State
        if ($matchedVariant) {
            $this->computedPriceDisplay = number_format($matchedVariant->price ?? $this->product->base_price, 2, '.', '');
            $this->inStock = $matchedVariant->stock_qty > 0;
            $this->availabilityLabel = $this->inStock ? null : 'Out of Stock';
        } else {
            // Partial selection or no variations
            if ($totalGroupCount > 0) {
                $this->computedPriceDisplay = number_format($this->product->base_price, 2, '.', '');
                $this->inStock = false; // Cannot add to cart until full selection
                $this->availabilityLabel = 'Select options';
            } else {
                // Simple product
                $this->computedPriceDisplay = number_format($this->product->base_price, 2, '.', '');
                $this->inStock = $this->product->stock_qty > 0;
                $this->availabilityLabel = $this->inStock ? null : 'Out of Stock';
            }
        }

        // 5. Resolve Gallery Control Rules
        $galleryControlGroup = $this->product->variationGroups->where('has_images', true)->first();
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

        // Fallback to base product media
        if (empty($newGallery)) {
            $newGallery = $this->product->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => url('storage/' . $img->image_path),
                    'thumb_url' => url('storage/' . $img->image_path)
                ];
            })->toArray();
        }

        $this->currentGallery = $newGallery;

        // Reset media index if out of bounds after a gallery swap
        if (!isset($this->currentGallery[$this->selectedMediaIndex])) {
            $this->selectedMediaIndex = 0;
        }
    }

    public function incrementQuantity()
    {
        if ($this->quantity < 99) {
            $this->quantity++;
        }
    }

    public function decrementQuantity()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function addToCart(CartService $cartService)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!$this->inStock) {
            session()->flash('error', 'The selected options are currently out of stock.');
            return;
        }

        try {
            // CartService requires a flat array of value IDs
            $variationValueIds = array_values($this->selectedVariationValues);
            
            $cart = $cartService->addItem(
                user: Auth::user(),
                productId: $this->product->id,
                quantity: $this->quantity,
                variationValueIds: $variationValueIds
            );
            
            $this->dispatch('itemAddedToCart');
            $this->dispatch('refresh-cart-badge', count: $cart->items()->sum('quantity'));
            
            session()->flash('success', 'Added to cart successfully.');

        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.shop.show', [
            'displayPrice' => $this->product->currency ?? 'INR',
            'featureBullets' => [], 
        ])->layout('layouts.web-app', [
            'title' => $this->product->title ?? 'Club Store',
        ]);
    }
}
