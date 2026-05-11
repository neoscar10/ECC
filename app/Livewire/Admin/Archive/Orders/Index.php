<?php

namespace App\Livewire\Admin\Archive\Orders;

use App\Models\Order;
use App\Models\Archive\ArchiveProduct;
use App\Services\OrderService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    
    #[\Livewire\Attributes\On('order-created')]
    public function refresh() { 
        // Re-render to show new orders
    }

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
        // Only show Archive orders
        $query = Order::with(['product', 'buyer', 'enquiry', 'logger'])
            ->where('source', 'archive')
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

    public $orderIdToCancel = null;

    public function confirmCancel($id)
    {
        $this->orderIdToCancel = $id;
        $this->dispatch('show-cancel-modal');
    }

    public function executeCancelOrder(OrderService $service)
    {
        if (!$this->orderIdToCancel) return;

        try {
            $order = Order::findOrFail($this->orderIdToCancel);
            $service->cancelOrder($order, auth()->user());
            
            session()->flash('success', 'Order cancelled successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error cancelling order: ' . $e->getMessage());
        }

        $this->orderIdToCancel = null;
        $this->dispatch('hide-cancel-modal');
    }

    public function openCreateModal()
    {
        $this->dispatch('open-create-order-modal'); // Dispatch to child/sibling component if separated, or handle here
    }
}
