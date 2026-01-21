<?php

namespace App\Services\Archive;

use App\Models\Archive\ArchiveOrder;
use App\Models\Archive\ArchiveProduct;
use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class ArchiveOrderService
{
    /**
     * Create a new order and deduct stock.
     */
    public function createOrder(array $data, User $loggedBy)
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
                'order_number' => $this->generateOrderNumber(),
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
            $order = ArchiveOrder::create($orderData);

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
     * Cancel an order and restore stock.
     */
    public function cancelOrder(ArchiveOrder $order, User $cancelledBy)
    {
        return DB::transaction(function () use ($order, $cancelledBy) {
            if ($order->status === 'cancelled') {
                return $order;
            }

            // 1. Lock Product to restore stock safely
            $product = ArchiveProduct::where('id', $order->archive_product_id)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Update Order
            $order->update([
                'status' => 'cancelled',
                'notes' => $order->notes . "\n[Cancelled by {$cancelledBy->name} at " . now() . "]",
            ]);

            // 3. Restore Stock
            $product->increment('quantity', $order->qty);
            
            // 4. Update Enquiry linkage?
            // Optionally we could look up the enquiry and clear the ID, but keeping historical link might be better.
            // Let's leave the link but maybe re-open enquiry? 
            // Better to leave as is unless spec says otherwise. User can manually re-open enquiry.

            return $order;
        });
    }

    private function generateOrderNumber()
    {
        // Format: ARC-YYYYMMDD-XXXX
        $prefix = 'ARC-' . now()->format('Ymd') . '-';
        $attempts = 0;
        
        do {
            $random = strtoupper(Str::random(4));
            $number = $prefix . $random;
            $exists = ArchiveOrder::where('order_number', $number)->exists();
            $attempts++;
        } while ($exists && $attempts < 10);

        if ($exists) {
            throw new Exception("Failed to generate unique order number.");
        }

        return $number;
    }
}
