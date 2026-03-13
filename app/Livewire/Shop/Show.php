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
            'variationGroups.values.images'
        ])->active()->where('slug', $this->slug)->firstOrFail();

        // Standardize groups for view
        $this->variationGroups = $this->product->variationGroups->toArray();
        $this->selectedVariationValues = [];

        // Evaluate Defaults
        $defaultValues = collect();
        foreach ($this->product->variationGroups as $group) {
            $def = $group->values->where('is_default', true)->first();
            if (!$def && $group->values->isNotEmpty()) {
                $def = $group->values->first();
            }
            if ($def) {
                $defaultValues->put($group->id, $def);
                $this->selectedVariationValues[$group->id] = $def->id;
            }
        }

        $this->recomputeDynamicState();
    }

    public function selectVariationValue($groupId, $valueId)
    {
        // Don't allow selecting disabled options
        $group = collect($this->variationGroups)->firstWhere('id', $groupId);
        if ($group) {
            $value = collect($group['values'])->firstWhere('id', $valueId);
            if ($value && ($value['stock_qty'] ?? 0) <= 0) {
                return; // Disabled
            }
        }

        $this->selectedVariationValues[$groupId] = $valueId;
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
        $selectedModels = collect();
        $maxPrice = (float) $this->product->base_price;
        $allInStock = true;

        // 1. Resolve Pricing and Stock Constraints
        foreach ($this->product->variationGroups as $group) {
            if (isset($this->selectedVariationValues[$group->id])) {
                $selectedId = $this->selectedVariationValues[$group->id];
                $val = $group->values->firstWhere('id', $selectedId);
                if ($val) {
                    $selectedModels->push($val);
                    if ((float) $val->price > $maxPrice) {
                        $maxPrice = (float) $val->price;
                    }
                    if ($val->stock_qty <= 0) {
                        $allInStock = false;
                    }
                }
            }
        }

        $this->computedPriceDisplay = number_format($maxPrice, 2, '.', '');
        $this->inStock = $allInStock;
        
        if (!$this->inStock) {
            $this->availabilityLabel = 'Out of Stock';
        } else {
            $this->availabilityLabel = null; 
        }

        // 2. Resolve Gallery Control Rules
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
