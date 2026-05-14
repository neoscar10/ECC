<?php

namespace App\Livewire\Admin\Shop\Orders;

use App\Models\Shop\ShopOrder;
use App\Services\Shop\CheckoutService;
use Livewire\Component;
use Exception;

class Show extends Component
{
    public $orderId;
    
    // Status Management
    public $paymentStatus;
    public $fulfillmentStatus;

    // Modal State
    public $showCancelModal = false;
    public $cancelReason = '';
    public $restoreStock = true; // Default to true

    public function mount($id)
    {
        $this->orderId = $id;
        $order = ShopOrder::findOrFail($id);
        $this->paymentStatus = $order->payment_status;
        $this->fulfillmentStatus = $order->status;
    }

    public function getOrderProperty()
    {
        return ShopOrder::with(['items.product.images', 'items.variationValues', 'user', 'shippingShipment.events'])->findOrFail($this->orderId);
    }

    /**
     * Shiprocket Phase 4: Refresh local courier selection
     */
    public function refreshCourierSelection(\App\Services\Shipping\ShipmentService $shipmentService)
    {
        try {
            $shipment = $shipmentService->refreshCourierSelectionForShopOrder($this->order);
            
            if ($shipment && $shipment->status === 'courier_selected') {
                $msg = 'Courier selection refreshed successfully.';
                if ($this->order->shipping_charge !== null && $shipment->courier_total_charge !== null && round($shipment->courier_total_charge, 2) !== round((float)$this->order->shipping_charge, 2)) {
                    $msg .= ' Note: Customer paid INR ' . number_format($this->order->shipping_charge, 2) . '. Current quote is INR ' . number_format($shipment->courier_total_charge, 2) . '.';
                }
                session()->flash('success', $msg);
            } else {
                session()->flash('warning', 'Shipment record updated but no couriers were available.');
            }
        } catch (Exception $e) {
            session()->flash('error', 'Error refreshing courier: ' . $e->getMessage());
        }
    }

    public function updateStatuses()
    {
        $this->validate([
            'paymentStatus' => 'required|in:unpaid,pending,paid,failed,refunded',
            'fulfillmentStatus' => 'required|in:placed,processing,packed,shipped,delivered,cancelled,returned',
        ]);

        $order = $this->order;

        // Prevent moving to cancelled/returned via simple dropdown if it requires stock logic?
        // Let's allow it but warn or just handle simple status update here.
        // If they pick "cancelled", maybe we should redirect them to use the Cancel button?
        // Or strictly just update the status string if they know what they are doing.
        // User instructions: "Update Status button opens a modal (or inline right column card)... Disallow invalid transitions... persist."
        
        // For safety, if they select 'cancelled' here, we force them to use the header button to ensure stock restore logic runs?
        // Or we just update the status.
        // Let's support direct update but warn that stock isn't restored this way? 
        // Better: Don't allow selecting 'cancelled' in this dropdown if it's not already cancelled. 
        if ($this->fulfillmentStatus === 'cancelled' && $order->status !== 'cancelled') {
             $this->addError('fulfillmentStatus', 'Please use the "Cancel Order" button to cancel and restore stock.');
             return;
        }

        $order->update([
            'payment_status' => $this->paymentStatus,
            'status' => $this->fulfillmentStatus,
        ]);

        // Logic side effects? e.g. if marked paid here, set paid_at?
        if ($this->paymentStatus === 'paid' && !$order->paid_at) {
            $order->update(['paid_at' => now()]);
        }

        session()->flash('success', 'Statuses updated successfully.');
    }

    public function markPaid(CheckoutService $checkoutService)
    {
        // Allowed check
        if ($this->order->payment_status === 'paid') return;

        try {
            $checkoutService->confirmPayment($this->order, [
                'method' => 'admin_manual',
                'confirmed_by' => auth()->id(),
            ]);
            
            // Refresh local state
            $this->paymentStatus = 'paid';
            $this->fulfillmentStatus = $this->order->status; // might change to processing if logic existed
            
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
        $this->validate([
            'cancelReason' => 'required|string|max:255',
        ]);

        try {
            // Use Admin Cancel (bypasses unpaid check)
            $checkoutService->adminCancelOrder($this->order, $this->cancelReason);
            
            $this->showCancelModal = false;
            $this->cancelReason = '';
            
            // Refresh local state
            $this->fulfillmentStatus = 'cancelled';
            $this->paymentStatus = $this->order->payment_status; // could become refunded

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
