<?php

namespace App\Services\Shop;

use App\Models\Shop\Cart;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopOrderItem;
use App\Models\Shop\ShopOrderItemVariationValue;
use App\Models\Shop\ShopProductVariationValue;
use App\Models\Shop\UserAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;
use InvalidArgumentException;

class CheckoutService
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get checkout summary (totals, items, stock check).
     */
    public function getCheckoutSummary(User $user, ?int $shippingAddressId = null): array
    {
        $cart = $this->cartService->getCart($user);
        $cart->load(['items.product', 'items.variationValues']);

        $subtotal = 0;
        $items = [];
        $currency = 'INR'; // Default

        foreach ($cart->items as $item) {
            // Re-validate price/stock strictly? 
            // For summary, we just calc current values.
            // But we should warn if out of stock.
            
            // Note: unit_price on item might be stale if product changed. 
            // Ideally we re-compute, but for efficiency we often trust cart unless 'refresh' requested.
            // Implementation: using stored unit_price for display, but placeOrder must re-verify.
            
            $lineTotal = $item->quantity * $item->unit_price;
            $subtotal += $lineTotal;
            $currency = $item->currency;

            $stockIssues = [];
            
            // Combination-aware Stock Check
            if ($item->shop_product_variant_id) {
                $variant = $item->variant;
                if (!$variant || $variant->stock_qty < $item->quantity) {
                    $labels = $item->variationValues->pluck('caption')->implode(' / ');
                    $available = $variant ? $variant->stock_qty : 0;
                    $stockIssues[] = "Insufficient stock for {$labels} (Available: {$available})";
                }
            } else {
                // Simple Product or Legacy Check
                if ($item->product->stock_qty < $item->quantity) {
                    $stockIssues[] = "Insufficient stock for {$item->product->title} (Available: {$item->product->stock_qty})";
                }
            }

            $items[] = [
                'shop_product_id' => $item->shop_product_id,
                'title' => $item->product->title,
                'quantity' => $item->quantity,
                'unit_price' => (float)$item->unit_price,
                'line_total' => (float)$lineTotal,
                'variation_values' => $item->variationValues->map(fn($v) => [
                    'id' => $v->id,
                    'caption' => $v->caption,
                ])->toArray(),
                'stock_issues' => $stockIssues,
            ];
        }

        // Placeholders for fees
        $shippingFee = 0;
        $taxAmount = 0;
        $discountAmount = 0;
        
        $totalAmount = $subtotal + $shippingFee + $taxAmount - $discountAmount;

        return [
            'currency' => $currency,
            'subtotal' => round($subtotal, 2),
            'shipping_fee' => round($shippingFee, 2),
            'tax_amount' => round($taxAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'items' => $items,
            'can_place_order' => empty(array_filter(array_column($items, 'stock_issues'))),
        ];
    }

    /**
     * Place an order.
     */
    public function placeOrder(User $user, array $data, ?array $paymentDetails = null): ShopOrder
    {
        $shippingAddress = UserAddress::where('user_id', $user->id)
            ->where('id', $data['shipping_address_id'])
            ->firstOrFail();

        if (!empty($data['billing_address_id'])) {
            $billingAddress = UserAddress::where('user_id', $user->id)
                ->where('id', $data['billing_address_id'])
                ->firstOrFail();
        } else {
            $billingAddress = $shippingAddress;
        }

        $isPaid = !empty($paymentDetails);

        return DB::transaction(function () use ($user, $shippingAddress, $billingAddress, $data, $paymentDetails, $isPaid) {
            $cart = $this->cartService->getCart($user);
            $cart->load(['items.product', 'items.variationValues']);

            if ($cart->items->isEmpty()) {
                throw new Exception("Cart is empty.");
            }

            $orderNumber = $this->generateOrderNumber();
            $subtotal = 0;
            $orderItemsData = [];
            $currency = 'INR';

                // 1. Process Items & Deduct Stock
                foreach ($cart->items as $item) {
                    if ($item->shop_product_variant_id) {
                        // Variant Product Logic (Lock the specific combination)
                        $variant = \App\Models\Shop\ShopProductVariant::where('id', $item->shop_product_variant_id)
                            ->lockForUpdate()
                            ->first();
                        
                        if (!$variant) {
                            throw new Exception("Variant combination not found.", 404);
                        }
                        
                        if ($variant->stock_qty < $item->quantity) {
                            $labels = $item->variationValues->pluck('caption')->implode(' / ');
                            throw new Exception("Insufficient stock for {$labels}. Requested: {$item->quantity}, Available: {$variant->stock_qty}", 409);
                        }
                        
                        $variant->decrement('stock_qty', $item->quantity);
                        
                    } else {
                        // Simple Product Logic
                        $product = $item->product()->lockForUpdate()->first();
                        
                        if (!$product) {
                            throw new Exception("Product not found or unavailable.", 404);
                        }
                        
                        if ($product->stock_qty < $item->quantity) {
                             throw new Exception("Insufficient stock for {$product->title}. Requested: {$item->quantity}, Available: {$product->stock_qty}", 409);
                        }
                        
                        $product->decrement('stock_qty', $item->quantity);
                    }

                $lineTotal = $item->quantity * $item->unit_price;
                $subtotal += $lineTotal;
                $currency = $item->currency;

                $orderItemsData[] = [
                    'shop_product_id' => $item->shop_product_id,
                    'title_snapshot' => $item->product->title,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $lineTotal,
                    'selection_signature' => $item->selection_signature,
                    'shop_product_variant_id' => $item->shop_product_variant_id,
                    'variation_value_ids' => $item->variationValues->pluck('id')->toArray(),
                ];
            }

            // 2. Create Order
            $order = ShopOrder::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'status' => $isPaid ? 'paid' : 'pending_payment',
                'payment_status' => $isPaid ? 'paid' : 'unpaid',
                'currency' => $currency,
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'shipping_address_snapshot' => $shippingAddress->toArray(),
                'billing_address_snapshot' => $billingAddress->toArray(),
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
                'paid_at' => $isPaid ? now() : null,
                'meta_json' => $isPaid ? ['payment_details' => $paymentDetails] : null,
            ]);

            // 3. Create Items & Pivots
            foreach ($orderItemsData as $itemData) {
                $orderItem = ShopOrderItem::create([
                    'shop_order_id' => $order->id,
                    'shop_product_id' => $itemData['shop_product_id'],
                    'title_snapshot' => $itemData['title_snapshot'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'line_total' => $itemData['line_total'],
                    'selection_signature' => $itemData['selection_signature'],
                    'shop_product_variant_id' => $itemData['shop_product_variant_id'],
                ]);

                $orderItem->variationValues()->attach($itemData['variation_value_ids']);
            }

            // 4. Clear Cart
            $this->cartService->clearCart($user);

            return $order->load('items.variationValues');
        });
    }

    /**
     * Confirm Mock Payment.
     */
    public function confirmPayment(ShopOrder $order, array $paymentDetails): ShopOrder
    {
        if ($order->payment_status === 'paid') {
            return $order;
        }

        $order->update([
            'payment_status' => 'paid',
            'status' => 'paid', // Or 'processing' if fulfillment is next step
            'paid_at' => now(),
            'meta_json' => array_merge($order->meta_json ?? [], ['payment_details' => $paymentDetails]),
        ]);

        return $order;
    }

    /**
     * Cancel Order (Restore Stock).
     */
    public function cancelOrder(ShopOrder $order, ?string $reason = null): ShopOrder
    {
        if (in_array($order->status, ['cancelled', 'failed'])) {
            return $order;
        }

        // Logic check: Allow cancel only if unpaid? 
        // User requested: "Cancel endpoint: only allowed when unpaid/pending_payment."
        // So strict check here.
        if ($order->payment_status !== 'unpaid') {
            throw new Exception("Cannot cancel a paid order via this endpoint.");
        }

        return DB::transaction(function () use ($order, $reason) {
            $order->load(['items.variationValues']);

            foreach ($order->items as $item) {
                if ($item->shop_product_variant_id) {
                    $item->variant()->increment('stock_qty', $item->quantity);
                } else if ($item->product) {
                    $item->product()->increment('stock_qty', $item->quantity);
                }
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $order->notes . "\n[Cancelled: $reason]",
            ]);

            return $order;
        });
    }

    /**
     * Admin Force Cancel (Allows cancelling paid orders).
     */
    public function adminCancelOrder(ShopOrder $order, string $reason): ShopOrder
    {
        if (in_array($order->status, ['cancelled', 'delivered', 'returned'])) {
             // Maybe allow cancelling 'processing'/'packed'/'shipped' but definitely not if already cancelled or returned.
             // Delivered? Usually implies return flow, but we can stick to 'cancelled' status check.
             if ($order->status === 'cancelled') return $order;
        }

        return DB::transaction(function () use ($order, $reason) {
            $order->load(['items.variationValues']);

            // Restore Stock
            // Restore Stock
            foreach ($order->items as $item) {
                if ($item->shop_product_variant_id) {
                    $item->variant()->increment('stock_qty', $item->quantity);
                } else if ($item->product) {
                    $item->product()->increment('stock_qty', $item->quantity);
                }
            }

            $updateData = [
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $order->notes . "\n[Admin Cancelled: $reason]",
            ];

            // If paid, mark as refunded for simple tracking (mock refund)
            if ($order->payment_status === 'paid') {
                $updateData['payment_status'] = 'refunded';
            }

            $order->update($updateData);

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        // Format: SHP-YYYYMMDD-XXXX
        $prefix = 'ECC-' . now()->format('Ymd') . '-';
        $random = strtoupper(\Illuminate\Support\Str::random(6));
        return $prefix . $random;
        // In high volume, check uniqueness.
    }
}
