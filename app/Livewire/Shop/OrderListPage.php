<?php

namespace App\Livewire\Shop;

use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class OrderListPage extends Component
{
    use WithPagination;

    public $perPage = 5;
    public $totalOrdersCount = 0;
    public $formattedTotalInvested = '₹0.00';
    public $activeTransitCount = 0;
    public $curatedItemsCount = 0;

    public function mount()
    {
        $this->loadStats();
        $this->totalOrdersCount = ShopOrder::where('user_id', Auth::id())
            ->where('status', '!=', 'draft')
            ->count();
    }

    public function loadMore()
    {
        $this->perPage += 5;
    }

    protected function loadStats()
    {
        $user = Auth::user();
        
        $stats = ShopOrder::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
            ->selectRaw('SUM(total_amount) as total_invested, COUNT(*) as total_count')
            ->first();

        $this->formattedTotalInvested = '₹' . number_format($stats->total_invested ?? 0, 2);
        
        $this->activeTransitCount = ShopOrder::where('user_id', $user->id)
            ->whereIn('status', ['processing', 'shipped'])
            ->count();

        $this->curatedItemsCount = ShopOrderItem::whereHas('order', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered']);
        })->sum('quantity');
    }

    public function render()
    {
        $orders = ShopOrder::where('user_id', Auth::id())
            ->where('status', '!=', 'draft')
            ->with(['items.product.images', 'items.variationValues'])
            ->latest('placed_at')
            ->latest('created_at')
            ->paginate($this->perPage);

        $mappedOrders = $orders->getCollection()->map(function ($order) {
            $heroItem = $order->items->first();
            $heroImg = $heroItem?->product?->images->first();
            
            $previewImages = $order->items->map(function($item) {
                $img = $item->product?->images->first();
                return $img ? Storage::url($img->image_path) : null;
            })->filter()->unique()->values()->toArray();

            return (object) [
                'id' => $order->id,
                'display_reference' => $order->order_number,
                'placed_at_label' => $order->placed_at?->format('M d, Y') ?? $order->created_at->format('M d, Y'),
                'status_label' => $this->getStatusLabel($order->status),
                'status_badge_class' => $this->getStatusBadgeClass($order->status),
                'status_emphasis_class' => in_array($order->status, ['paid', 'processing', 'shipped']) ? 'ecc-text-gold' : '',
                'formatted_total' => '₹' . number_format($order->total_amount, 2),
                'formatted_secondary_total' => null, // Project doesn't seem to have secondary currency yet
                'hero_image_url' => $heroImg ? Storage::url($heroImg->image_path) : 'https://placehold.co/400x300/17130b/d4af37?text=SECURED',
                'hero_image_alt' => $heroItem?->title_snapshot,
                'preview_images' => $previewImages,
                'extra_items_count' => max(0, $order->items->count() - 3),
                'footer_note' => $order->items->count() . ' ' . \Illuminate\Support\Str::plural('item', $order->items->count()),
                'transit_note' => $this->getTransitNote($order->status),
                'details_url' => route('shop.order-details', $order->id),
            ];
        });

        return view('livewire.shop.order-list', [
            'orders' => $mappedOrders,
            'hasMoreOrders' => $orders->hasMorePages(),
            'visibleOrdersCount' => min($orders->total(), $this->perPage),
            'continueShoppingUrl' => route('shop.index'),
        ])->layout('layouts.web-app', ['title' => 'Order History']);
    }

    protected function getStatusLabel($status)
    {
        return match (strtolower($status)) {
            'pending_payment' => 'Pending Payment',
            'paid' => 'Paid',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => ucfirst($status),
        };
    }

    protected function getStatusBadgeClass($status)
    {
        return match (strtolower($status)) {
            'processing', 'paid' => 'status-processing',
            'shipped' => 'status-shipped',
            'delivered' => 'status-delivered',
            default => 'status-default',
        };
    }

    protected function getTransitNote($status)
    {
        return match (strtolower($status)) {
            'shipped' => 'In Transit to Destination',
            'processing' => 'Preparing for Dispatch',
            'paid' => 'Awaiting Fulfillment',
            default => null,
        };
    }
}
