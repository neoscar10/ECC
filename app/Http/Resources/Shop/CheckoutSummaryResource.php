<?php

namespace App\Http\Resources\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Wrapper for the array returned by service
        return [
            'currency' => $this['currency'],
            'subtotal' => $this['subtotal'],
            'shipping_fee' => $this['shipping_fee'],
            'tax_amount' => $this['tax_amount'],
            'discount_amount' => $this['discount_amount'],
            'total_amount' => $this['total_amount'],
            'items' => collect($this['items'])->map(fn($item) => [
                'product_id' => $item['shop_product_id'],
                'title' => $item['title'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => $item['line_total'],
                'variation_values' => $item['variation_values'],
                'stock_issues' => $item['stock_issues'],
            ]),
            'can_place_order' => $this['can_place_order'],
        ];
    }
}
