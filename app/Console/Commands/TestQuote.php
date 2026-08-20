<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestQuote extends Command
{
    protected $signature = 'test:quote';
    protected $description = 'Command description';

    public function handle()
    {
        $user = \App\Models\User::find(11);
        if (!$user) {
            $user = \App\Models\User::first();
        }
        $cart = app(\App\Services\Shop\CartService::class)->getCart($user);
        $cart->load(['items.product', 'items.variant']);
        $meas = app(\App\Services\Shipping\ShippingMeasurementService::class)->measurementFromCartItems($cart->items);
        var_export($meas);
        echo "\n";
        
        $address = \App\Models\Shop\UserAddress::latest()->first();
        $quoteService = app(\App\Services\Shipping\CheckoutShippingQuoteService::class);
        $quoteResult = $quoteService->quoteForCheckout($user, $cart->items, $address);
        var_export($quoteResult);
        echo "\n";
    }
}
