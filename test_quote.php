<?php
$user = App\Models\User::find(11);
$cart = app(App\Services\Shop\CartService::class)->getCart($user);
$cart->load(['items.product', 'items.variant']);
$meas = app(App\Services\Shipping\ShippingMeasurementService::class)->measurementFromCartItems($cart->items);
var_export($meas);
