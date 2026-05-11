<?php

namespace App\Livewire\Admin\Shop\Products;

use App\Models\Shop\ShopProduct;
use Livewire\Component;

class Show extends Component
{
    public $productId;
    public $product;
    public $activeImageId = null;

    public function mount($id)
    {
        $this->productId = $id;
        $this->loadProduct();
    }

    public function loadProduct()
    {
        $this->product = ShopProduct::with([
            'images',
            'categories',
            'tags',
            'variationGroups.values',
            'variants.optionValues.group'
        ])->findOrFail($this->productId);

        if (!$this->activeImageId || !$this->product->images->contains('id', $this->activeImageId)) {
            $this->activeImageId = $this->product->images->sortBy('sort_order')->first()?->id;
        }
    }

    public function selectImage($id)
    {
        $this->activeImageId = $id;
    }

    public function getActiveImageProperty()
    {
        if (!$this->activeImageId) return null;
        return $this->product->images->firstWhere('id', $this->activeImageId);
    }

    public function requestEdit()
    {
        $this->dispatch('shop-product:edit', productId: $this->product->id);
    }

    public function render()
    {
        return view('livewire.admin.shop.products.show')
            ->layout('layouts.admin');
    }
}
