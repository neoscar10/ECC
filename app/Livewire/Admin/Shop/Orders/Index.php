<?php

namespace App\Livewire\Admin\Shop\Orders;

use App\Models\Shop\ShopOrder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterStatus = '';
    public $filterPaymentStatus = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = ShopOrder::with('user')
            ->when($this->search, function ($query) {
                $query->where('order_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterPaymentStatus, function ($query) {
                $query->where('payment_status', $this->filterPaymentStatus);
            })
            ->latest('placed_at')
            ->paginate(15);

        return view('livewire.admin.shop.orders.index', [
            'orders' => $orders,
        ])->layout('layouts.admin');
    }
}
