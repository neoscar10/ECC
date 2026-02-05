<?php

namespace App\Http\Resources\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'currency' => $this->currency,
            'totals' => [
                'subtotal' => (float) $this->subtotal,
                'shipping_fee' => (float) $this->shipping_fee,
                'tax_amount' => (float) $this->tax_amount,
                'discount_amount' => (float) $this->discount_amount,
                'total_amount' => (float) $this->total_amount,
            ],
            'shipping_address' => $this->shipping_address_snapshot,
            'billing_address' => $this->billing_address_snapshot,
            'items' => $this->items->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->shop_product_id,
                'title' => $item->title_snapshot,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
                'variations' => $item->variationValues->map(fn($v) => [
                     'id' => $v->id,
                     'caption' => $v->caption,
                ]),
            ]),
            'dates' => [
                'placed_at' => $this->placed_at?->toIso8601String(),
                'paid_at' => $this->paid_at?->toIso8601String(),
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            ],
            'notes' => $this->notes,
        ];
    }
}
