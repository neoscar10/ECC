<?php

namespace App\Http\Resources\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Compute Totals
        // Ideally totals are computed on the fly or stored? 
        // Service should probably handle this or we sum here. 
        // Summing here is fine for small carts.
        $subtotal = $this->items->sum('line_total');
        $totalItems = $this->items->sum('quantity');
        
        // Currency: assuming all items same currency (Safe Assumption for ECC: INR)
        $currency = $this->items->first()->currency ?? 'INR';

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => 'active', // Placeholder if needed
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'is_abandoned' => $this->is_abandoned,
            'abandoned_threshold_minutes' => (int)config('cart.abandoned_minutes'),
            'totals' => [
                'subtotal' => (float)$subtotal,
                'total_items' => (int)$totalItems,
                'currency' => $currency,
            ],
            'items' => CartItemResource::collection($this->items),
        ];
    }
}
