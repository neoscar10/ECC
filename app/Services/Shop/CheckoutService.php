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
            $currency = $item->currency; // Assume matches across cart

            $stockIssues = [];
            foreach ($item->variationValues as $val) {
                if ($val->stock_qty < $item->quantity) {
                    $stockIssues[] = "Insufficient stock for {$val->caption} (Available: {$val->stock_qty})";
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
    public function placeOrder(User $user, array $data): ShopOrder
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

        return DB::transaction(function () use ($user, $shippingAddress, $billingAddress, $data) {
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
                // Lock selected variations for stock check
                $variationIds = $item->variationValues->pluck('id')->toArray();
                
                // Sort IDs to prevent deadlocks if locking multiple
                sort($variationIds); // Not strictly needed for `whereIn->lockForUpdate`, but good practice
                
                $lockedVariations = ShopProductVariationValue::whereIn('id', $variationIds)
                    ->lockForUpdate()
                    ->get();
                
                // Re-validate stock for EACH variation value
                foreach ($lockedVariations as $val) {
                    if ($val->stock_qty < $item->quantity) {
                         throw new Exception("Insufficient stock for {$val->caption}. Requested: {$item->quantity}, Available: {$val->stock_qty}", 409);
                    }
                    
                    // Deduct
                    $val->decrement('stock_qty', $item->quantity);
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
                    'variation_value_ids' => $variationIds, // Temp storage for pivot creation
                ];
            }

            // 2. Create Order
            $order = ShopOrder::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'status' => 'pending_payment',
                'payment_status' => 'unpaid',
                'currency' => $currency,
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal, // +0+0-0
                'shipping_address_snapshot' => $shippingAddress->toArray(),
                'billing_address_snapshot' => $billingAddress->toArray(),
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
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
                ]);

                $orderItem->variationValues()->attach($itemData['variation_value_ids']);
            }

            // 4. Clear Cart
            // We use clearCart from service, but ensure we don't double-transaction (nested is fine in Laravel)
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
                foreach ($item->variationValues as $val) {
                    $val->increment('stock_qty', $item->quantity);
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

    private function generateOrderNumber(): string
    {
        // Format: SHP-YYYYMMDD-XXXX
        $prefix = 'SHP-' . now()->format('Ymd') . '-';
        $random = strtoupper(\Illuminate\Support\Str::random(6));
        return $prefix . $random;
        // In high volume, check uniqueness.
    }
}
