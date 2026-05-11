<?php

namespace App\Livewire\Admin\Archive\Products;

use App\Models\Archive\ArchiveProduct;
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
        $this->product = ArchiveProduct::with([
            'category',
            'images',
            'images360',
            'tiers',
            'visibilityTiers',
            'clearViewTiers',
            'earlyAccessWindows.tier',
            'attachments'
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
        $this->dispatch('archive-product:edit', productId: $this->product->id);
    }

    public function render()
    {
        return view('livewire.admin.archive.products.show')
            ->layout('layouts.admin');
    }
}
