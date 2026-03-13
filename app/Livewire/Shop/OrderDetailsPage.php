<?php

namespace App\Livewire\Shop;

use App\Models\Shop\ShopOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class OrderDetailsPage extends Component
{
    public $order;
    public $displayOrderReference;
    public $placedAtLabel;
    public $statusBadgeLabel;
    public $statusBadgeClass;
    public $invoiceUrl;
    public $trackingUrl;
    public $canTrackShipment = false;
    public $itemsCount;
    public $items;
    public $shippingName;
    public $shippingAddressBlock;
    public $shippingPhone;
    public $paymentBrandLabel;
    public $paymentMethodLabel;
    public $paymentExpiryLabel;
    public $billingSameAsShipping = true;
    public $billingAddressBlock;
    public $formattedSubtotal;
    public $formattedShipping;
    public $formattedTax;
    public $discountAmount;
    public $formattedDiscount;
    public $formattedGrandTotal;
    public $currencyCode;
    public $ordersIndexUrl;
    public $conciergeUrl;
    public $shippingLabel = 'Shipping';
    public $taxLabel = 'Tax';

    public function mount($orderId)
    {
        $this->order = ShopOrder::where('user_id', Auth::id())
            ->where('id', $orderId)
            ->with(['items.product.images', 'items.variationValues'])
            ->firstOrFail();

        $this->loadData();
    }

    protected function loadData()
    {
        $this->displayOrderReference = $this->order->order_number;
        $this->placedAtLabel = $this->order->placed_at?->format('F d, Y') ?? $this->order->created_at->format('F d, Y');
        
        $this->setStatusBadge();
        
        $this->itemsCount = $this->order->items->count();
        $this->items = $this->order->items->map(function ($item) {
            $img = $item->product?->images->first();
            return (object) [
                'title' => $item->title_snapshot,
                'type_label' => $item->product?->categories->first()?->name ?? 'Premium Item',
                'subtitle' => $item->product?->description ? Str::limit(strip_tags($item->product->description), 60) : '',
                'image_url' => $img ? Storage::url($img->image_path) : 'https://placehold.co/100x130/17130b/d4af37?text=SECURED',
                'quantity' => $item->quantity,
                'formatted_total' => '₹' . number_format($item->line_total, 2),
                'sku' => $item->product?->id ? 'ECC-' . str_pad($item->product->id, 5, '0', STR_PAD_LEFT) : null,
                'variant_summary' => $item->variationValues->pluck('caption')->implode(' / '),
            ];
        });

        $shipping = $this->order->shipping_address_snapshot;
        $this->shippingName = $shipping['full_name'] ?? Auth::user()->name;
        $this->shippingAddressBlock = implode("\n", array_filter([
            $shipping['line1'] ?? '',
            $shipping['line2'] ?? '',
            ($shipping['city'] ?? '') . ', ' . ($shipping['state'] ?? ''),
            ($shipping['country'] ?? '') . ' - ' . ($shipping['postal_code'] ?? ''),
        ]));
        $this->shippingPhone = $shipping['phone'] ?? null;

        $this->paymentBrandLabel = $this->order->meta_json['payment_method_brand'] ?? 'VISA';
        $this->paymentMethodLabel = 'Ending in ' . ($this->order->meta_json['last4'] ?? '4242');
        $this->paymentExpiryLabel = $this->order->meta_json['expiry'] ?? '12/28';

        $this->formattedSubtotal = '₹' . number_format($this->order->subtotal, 2);
        $this->formattedShipping = (float)$this->order->shipping_fee > 0 ? '₹' . number_format($this->order->shipping_fee, 2) : 'FREE';
        $this->formattedTax = '₹' . number_format($this->order->tax_amount, 2);
        $this->discountAmount = $this->order->discount_amount;
        $this->formattedDiscount = '₹' . number_format($this->order->discount_amount, 2);
        $this->formattedGrandTotal = '₹' . number_format($this->order->total_amount, 2);
        $this->currencyCode = $this->order->currency;

        $this->ordersIndexUrl = route('shop.orders');
        $this->conciergeUrl = route('home'); // Fallback to home
        
        $this->invoiceUrl = null; // Placeholder
        $this->trackingUrl = null; // Placeholder
    }

    protected function setStatusBadge()
    {
        $status = strtolower($this->order->status);
        
        switch ($status) {
            case 'pending':
            case 'processing':
                $this->statusBadgeLabel = 'Processing';
                $this->statusBadgeClass = 'status-processing';
                break;
            case 'shipped':
                $this->statusBadgeLabel = 'Shipped';
                $this->statusBadgeClass = 'status-shipped';
                break;
            case 'delivered':
                $this->statusBadgeLabel = 'Delivered';
                $this->statusBadgeClass = 'status-delivered';
                break;
            case 'cancelled':
                $this->statusBadgeLabel = 'Cancelled';
                $this->statusBadgeClass = 'status-default';
                break;
            default:
                $this->statusBadgeLabel = ucfirst($status);
                $this->statusBadgeClass = 'status-default';
        }
    }



    public function render()
    {
        return view('livewire.shop.order-details')
            ->layout('layouts.web-app', ['title' => 'Order Details']);
    }
}
