<?php

namespace App\Livewire\Admin\Archive\Ownership;

use App\Models\Archive\ArchiveProduct;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = ArchiveProduct::withTrashed()
            ->with(['images', 'orders' => function($q) {
                $q->where('status', 'completed');
            }])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('id', 'like', '%' . $this->search . '%');
            })
            ->latest('id')
            ->paginate(15);

        // Calculate sold counts
        $products->getCollection()->transform(function($product) {
            $product->total_sold = $product->orders->sum('qty');
            $product->total_owners = $product->orders->map(function ($order) {
                return $order->user_id ? 'user_' . $order->user_id : 'guest_' . ($order->external_email ?? $order->external_phone);
            })->unique()->count();
            return $product;
        });

        return view('livewire.admin.archive.ownership.index', [
            'products' => $products
        ]);
    }
}
