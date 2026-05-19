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

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterPaymentStatus' => ['except' => ''],
    ];

    // Modal State
    public $showStatusModal = false;
    public $selectedOrderId = null;
    public $statusDraft = '';
    public $paymentStatusDraft = '';
    public $selectedOrderNumber = '';
    public $selectedOrderCustomer = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openStatusModal($orderId)
    {
        $order = ShopOrder::with('user')->findOrFail($orderId);
        $this->selectedOrderId = $order->id;
        $this->statusDraft = $order->status;
        $this->paymentStatusDraft = $order->payment_status;
        $this->selectedOrderNumber = $order->order_number;
        $this->selectedOrderCustomer = $order->user->name ?? 'Guest';
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->reset('selectedOrderId', 'statusDraft', 'paymentStatusDraft', 'selectedOrderNumber', 'selectedOrderCustomer');
    }

    public function saveStatus()
    {
        $this->validate([
            'statusDraft' => 'required|in:placed,paid,confirmed,processing,packed,shipped,delivered,cancelled,returned',
            'paymentStatusDraft' => 'required|in:unpaid,pending,paid,failed,refunded',
        ]);

        $order = ShopOrder::findOrFail($this->selectedOrderId);
        
        $order->update([
            'status' => $this->statusDraft,
            'payment_status' => $this->paymentStatusDraft,
        ]);

        // Side effects
        if ($this->paymentStatusDraft === 'paid' && !$order->paid_at) {
            $order->update(['paid_at' => now()]);
        }
        
        // Note: Simple update does not handle stock restoration if cancelled here.
        // We rely on the Detail page for full cancellation flow logic if needed, 
        // or we could add a warning in the modal that this is a simple status override.
        // For Index page "quick status", simple override is often standard behavior, 
        // but let's stick to simple update as requested to minimal change.

        session()->flash('success', 'Order status updated successfully.');
        $this->closeStatusModal();
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
