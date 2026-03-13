<?php

namespace App\Http\View\Composers;

use App\Services\Shop\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebLayoutComposer
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $cartCount = 0;

        if (Auth::check()) {
            $cart = $this->cartService->getCart(Auth::user());
            $cartCount = $cart->items()->sum('quantity');
        }

        $view->with('cartCount', $cartCount);
    }
}
