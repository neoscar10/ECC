<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Archive\ArchiveProduct;
use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\Auctions\AuctionLot;
use App\Models\User;
use App\Events\AuctionTimelineEventCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class OrderService
{
    /**
     * Create a new order for Archive Product and deduct stock.
     */
    public function createArchiveOrder(array $data, User $loggedBy)
    {
        return DB::transaction(function () use ($data, $loggedBy) {
            // 1. Lock and Validate Product
            $product = ArchiveProduct::where('id', $data['archive_product_id'])
                ->lockForUpdate() // Crucial for stock safety
                ->firstOrFail();

            if ($product->quantity < $data['qty']) {
                throw new Exception("Insufficient stock. Available: {$product->quantity}");
            }

            // 2. Prepare Order Data
            $orderData = [
                'order_number' => $this->generateOrderNumber('ARC'),
                'source' => 'archive',
                'archive_product_id' => $product->id,
                'archive_product_enquiry_id' => $data['archive_product_enquiry_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'buyer_type' => $data['buyer_type'] ?? 'registered',
                'external_name' => $data['external_name'] ?? null,
                'external_phone' => $data['external_phone'] ?? null,
                'external_email' => $data['external_email'] ?? null,
                'external_address' => $data['external_address'] ?? null,
                'qty' => $data['qty'],
                'unit_price_inr' => $data['unit_price_inr'],
                'subtotal_inr' => $data['qty'] * $data['unit_price_inr'],
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
                'logged_by' => $loggedBy->id,
                'sold_at' => now(),
            ];

            // 3. Create Order
            $order = Order::create($orderData);

            // 4. Deduct Stock
            $product->decrement('quantity', $data['qty']);

            // 5. Link Enquiry (if applicable)
            if ($order->archive_product_enquiry_id) {
                $enquiry = ArchiveProductEnquiry::find($order->archive_product_enquiry_id);
                if ($enquiry) {
                    $enquiry->update(['archive_order_id' => $order->id, 'status' => 'closed']);
                }
            }

            return $order;
        });
    }

    /**
     * Create a new order for Auction Lot.
     */
    public function createAuctionOrder(array $data, User $loggedBy)
    {
        return DB::transaction(function () use ($data, $loggedBy) {
            // 1. Validate Auction Lot
            $lot = AuctionLot::where('id', $data['auction_lot_id'])->firstOrFail();

            // Validate duplicate order
            if (Order::where('auction_lot_id', $lot->id)->exists()) {
                throw new Exception("Order already exists for this lot.");
            }

            // 2. Prepare Order Data
            $orderData = [
                'order_number' => $this->generateOrderNumber('AUC'),
                'source' => 'auction',
                'auction_lot_id' => $lot->id,
                'user_id' => $data['user_id'] ?? null,
                'buyer_type' => $data['buyer_type'] ?? 'registered',
                'external_name' => $data['external_name'] ?? null,
                'external_phone' => $data['external_phone'] ?? null,
                'external_email' => $data['external_email'] ?? null, // Fixed: was external_phone in some examples/logic potentially
                'external_address' => $data['external_address'] ?? null,
                'qty' => 1, // Auctions are single items usually
                'unit_price_inr' => $data['unit_price_inr'], // Winning Bid
                'subtotal_inr' => $data['unit_price_inr'],
                'payment_method' => $data['payment_method'] ?? 'Offline',
                'payment_reference' => $data['payment_reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
                'logged_by' => $loggedBy->id,
                'sold_at' => now(),
            ];

            // 3. Create Order
            $order = Order::create($orderData);

            // 4. Create Timeline Event
            $timelineEvent = \App\Models\Auctions\AuctionEvent::create([
                'auction_lot_id' => $lot->id,
                'actor_type' => 'admin',
                'actor_id' => $loggedBy->id,
                'event_type' => 'sale_recorded',
                'payload' => [
                    'order_number' => $order->order_number, 
                    'amount' => $order->subtotal_inr,
                    'message' => $loggedBy->name . ' recorded sale'
                ]
            ]);

            event(new AuctionTimelineEventCreated($timelineEvent));

            return $order;
        });
    }

    /**
     * Cancel an order and restore stock.
     */
    public function cancelOrder(Order $order, User $cancelledBy)
    {
        return DB::transaction(function () use ($order, $cancelledBy) {
            if ($order->status === 'cancelled') {
                return $order;
            }

            if ($order->source === 'archive') {
                // 1. Lock Product to restore stock safely
                $product = ArchiveProduct::where('id', $order->archive_product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 3. Restore Stock
                $product->increment('quantity', $order->qty);
            }
            
            // 2. Update Order
            $order->update([
                'status' => 'cancelled',
                'notes' => $order->notes . "\n[Cancelled by {$cancelledBy->name} at " . now() . "]",
            ]);

            return $order;
        });
    }

    private function generateOrderNumber($prefixPart = 'ARC')
    {
        // Format: {PREFIX}-YYYYMMDD-XXXX
        $prefix = $prefixPart . '-' . now()->format('Ymd') . '-';
        $attempts = 0;
        
        do {
            $random = strtoupper(Str::random(4));
            $number = $prefix . $random;
            $exists = Order::where('order_number', $number)->exists();
            $attempts++;
        } while ($exists && $attempts < 10);

        if ($exists) {
            throw new Exception("Failed to generate unique order number.");
        }

        return $number;
    }
}
