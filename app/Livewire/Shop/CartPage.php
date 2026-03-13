<?php

namespace App\Livewire\Shop;

use App\Services\Shop\CartService;
use App\Services\Shop\CheckoutService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class CartPage extends Component
{
    public $cartItems = [];
    public $summary = [];
    public $cartCount = 0;

    public function mount()
    {
        $this->loadCart();
    }

    public function loadCart()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $cartService = app(CartService::class);
        $checkoutService = app(CheckoutService::class);
        $user = Auth::user();

        $cart = $cartService->getCart($user);
        $cart->load(['items.product', 'items.selectedVariations.group']);

        $this->cartCount = $cart->items->sum('quantity');

        // Transform items for the view to match requested Blade structure
        $this->cartItems = $cart->items->map(function ($item) {
            $product = $item->product;
            
            // Build variant summary string
            $variantSummary = $item->selectedVariations->map(function ($v) {
                return ($v->group->name ?? 'Option') . ': ' . $v->caption;
            })->implode(' / ');

            return [
                'id' => $item->id,
                'title' => $product->title,
                'variant_summary' => $variantSummary,
                'image_url' => $product->images->first() ? Storage::url($product->images->first()->image_path) : null,
                'quantity' => $item->quantity,
                'price_display' => '₹' . number_format($item->unit_price, 2),
                'line_total_display' => '₹' . number_format($item->line_total, 2),
            ];
        })->toArray();

        // Get Summary from CheckoutService
        $summaryData = $checkoutService->getCheckoutSummary($user);

        $this->summary = [
            'subtotal_display' => '₹' . number_format($summaryData['subtotal'], 2),
            'shipping_display' => '₹' . number_format($summaryData['shipping_fee'], 2),
            'shipping_is_free' => $summaryData['shipping_fee'] <= 0,
            'tax_display' => '₹' . number_format($summaryData['tax_amount'], 2),
            'total_display' => '₹' . number_format($summaryData['total_amount'], 2),
        ];

        $this->dispatch('refresh-cart-badge', count: $this->cartCount);
    }

    public function incrementQuantity($itemId)
    {
        try {
            $cartService = app(CartService::class);
            $item = \App\Models\Shop\CartItem::findOrFail($itemId);
            
            $cartService->updateItem(
                user: Auth::user(),
                cartItemId: $itemId,
                quantity: $item->quantity + 1
            );

            $this->loadCart();
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function decrementQuantity($itemId)
    {
        try {
            $cartService = app(CartService::class);
            $item = \App\Models\Shop\CartItem::findOrFail($itemId);
            
            if ($item->quantity <= 1) {
                $this->removeItem($itemId);
                return;
            }

            $cartService->updateItem(
                user: Auth::user(),
                cartItemId: $itemId,
                quantity: $item->quantity - 1
            );

            $this->loadCart();
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function removeItem($itemId)
    {
        try {
            $cartService = app(CartService::class);
            $cartService->removeItem(Auth::user(), $itemId);
            $this->loadCart();
        } catch (Exception $e) {
            session()->flash('error', 'Failed to remove item.');
        }
    }

    public function proceedToCheckout()
    {
        // For now, redirect to checkout if it exists, or just a placeholder
        // Usually would go to route('shop.checkout')
        return redirect()->route('shop.index'); // Placeholder until checkout page is built
    }

    public function render()
    {
        return view('livewire.shop.cart')
            ->layout('layouts.web-app', [
                'title' => 'My Cart',
                'cartCount' => $this->cartCount
            ]);
    }
}
