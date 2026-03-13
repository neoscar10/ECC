<?php

namespace App\Livewire\Shop;

use App\Models\Shop\ShopOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class OrderSuccessPage extends Component
{
    public $order;
    public $displayTransactionReference;
    public $heroItemTitle;
    public $heroItemImageUrl;
    public $formattedGrandTotal;
    public $formattedDiscountAmount;
    public $discountAmount;
    public $estimatedDeliveryLabel;
    public $shippingRecipientOrTitle;
    public $shippingAddressBlock;
    public $continueShoppingUrl;
    public $orderDetailsUrl;
    public $showPrivilegeBanner = false;
    public $privilegeMessage;
    public $privilegeUrl;

    public function mount($orderId)
    {
        $this->order = ShopOrder::where('user_id', Auth::id())
            ->where('id', $orderId)
            ->with(['items.product.images'])
            ->firstOrFail();

        if ($this->order->payment_status !== 'paid') {
             return redirect()->route('shop.cart')->with('error', 'Payment not confirmed for this order.');
        }

        $this->displayTransactionReference = $this->order->order_number;
        
        $heroItem = $this->order->items->first();
        $this->heroItemTitle = $heroItem->title_snapshot;
        
        $img = $heroItem->product?->images->first();
        $this->heroItemImageUrl = $img ? Storage::url($img->image_path) : 'https://placehold.co/180x180/17130b/d4af37?text=SECURED';

        $this->formattedGrandTotal = '₹' . number_format($this->order->total_amount, 2);
        $this->discountAmount = $this->order->discount_amount;
        $this->formattedDiscountAmount = '₹' . number_format($this->order->discount_amount, 2);
        
        $this->estimatedDeliveryLabel = 'Within 5-7 Business Days';
        
        $shipping = $this->order->shipping_address_snapshot;
        $this->shippingRecipientOrTitle = $shipping['full_name'] ?? Auth::user()->name;
        
        $this->shippingAddressBlock = implode("\n", array_filter([
            $shipping['line1'] ?? '',
            $shipping['line2'] ?? '',
            ($shipping['city'] ?? '') . ', ' . ($shipping['state'] ?? ''),
            ($shipping['country'] ?? '') . ' - ' . ($shipping['postal_code'] ?? ''),
        ]));

        $this->continueShoppingUrl = route('shop.index');
        $this->orderDetailsUrl = route('shop.order-details', $this->order->id);

        // Optional Privilege logic (mock for now but follows requirements)
        if ($this->order->total_amount > 10000) {
            $this->showPrivilegeBanner = true;
            $this->privilegeMessage = "Your elite acquisition grants you early access to the upcoming Summer Solstice Drop.";
            $this->privilegeUrl = route('shop.index');
        }
    }

    public function render()
    {
        return view('livewire.shop.order-success')
            ->layout('layouts.web-app', ['title' => 'Order Success']);
    }
}
