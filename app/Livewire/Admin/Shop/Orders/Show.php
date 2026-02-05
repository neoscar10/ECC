<?php

namespace App\Livewire\Admin\Shop\Orders;

use App\Models\Shop\ShopOrder;
use App\Services\Shop\CheckoutService;
use Livewire\Component;
use Exception;

class Show extends Component
{
    public $orderId;
    public $showCancelModal = false;
    public $cancelReason = '';

    public function mount($id)
    {
        $this->orderId = $id;
    }

    public function getOrderProperty()
    {
        return ShopOrder::with(['items.variationValues', 'user'])->findOrFail($this->orderId);
    }

    public function markPaid(CheckoutService $checkoutService)
    {
        try {
            // Mock payment details for admin manual confirmation
            $checkoutService->confirmPayment($this->order, [
                'method' => 'admin_manual',
                'confirmed_by' => auth()->id(),
            ]);
            
            session()->flash('success', 'Order marked as Paid.');
        } catch (Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function confirmCancel()
    {
        $this->showCancelModal = true;
    }

    public function cancelOrder(CheckoutService $checkoutService)
    {
        try {
            // Service handles validation (e.g. must be unpaid) and stock restoration
            $checkoutService->cancelOrder($this->order, "Admin Cancelled: " . $this->cancelReason);
            
            $this->showCancelModal = false;
            $this->cancelReason = '';
            
            session()->flash('success', 'Order cancelled successfully.');
        } catch (Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
            $this->showCancelModal = false;
        }
    }

    public function render()
    {
        return view('livewire.admin.shop.orders.show')
            ->layout('layouts.admin');
    }
}
