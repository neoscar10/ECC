<?php

namespace App\Livewire\Admin\Archive\Orders;

use App\Models\Archive\ArchiveOrder;
use App\Models\Archive\ArchiveProduct;
use App\Services\Archive\ArchiveOrderService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $status = '';
    public $dateFrom;
    public $dateTo;

    // Modal State
    public $showCreateModal = false;
    public $isCreateMode = true; // Always create for now, edit might come later
    
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function render()
    {
        $query = ArchiveOrder::with(['product', 'buyer', 'enquiry', 'logger'])
            ->latest('sold_at');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('order_number', 'like', '%'.$this->search.'%')
                  ->orWhere('external_name', 'like', '%'.$this->search.'%')
                  ->orWhereHas('buyer', function($u) {
                      $u->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                  })
                  ->orWhereHas('product', function($p) {
                      $p->where('title', 'like', '%'.$this->search.'%');
                  });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom) {
            $query->whereDate('sold_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('sold_at', '<=', $this->dateTo);
        }

        return view('livewire.admin.archive.orders.index', [
            'orders' => $query->paginate(15)
        ]);
    }

    public function cancelOrder($orderId, ArchiveOrderService $service)
    {
        try {
            $order = ArchiveOrder::findOrFail($orderId);
            $service->cancelOrder($order, auth()->user());
            session()->flash('success', 'Order cancelled and stock restored.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error cancelling order: ' . $e->getMessage());
        }
    }

    public function openCreateModal()
    {
        $this->dispatch('open-create-order-modal'); // Dispatch to child/sibling component if separated, or handle here
    }
}
