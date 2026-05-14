<?php

namespace App\Services\Shipping\Shiprocket;

use App\Models\Shipping\ShippingShipment;
use App\Services\Shipping\ShipmentService;
use Illuminate\Support\Facades\Log;

class ShiprocketTrackingWebhookService
{
    protected $shipmentService;

    public function __construct(ShipmentService $shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    /**
     * Handle incoming tracking webhook payload from Shiprocket.
     */
    public function handle(array $payload, array $headers = []): array
    {
        // 1. Extract Identifiers
        $awbCode = $this->extract($payload, ['awb', 'awb_code', 'awb_number', 'data.awb', 'data.awb_code', 'shipment.awb_code', 'tracking_data.awb_code']);
        $shipmentId = $this->extract($payload, ['shipment_id', 'sr_shipment_id', 'data.shipment_id', 'shipment.shipment_id']);
        $orderId = $this->extract($payload, ['order_id', 'shiprocket_order_id', 'provider_order_id', 'data.order_id', 'data.shiprocket_order_id', 'channel_order_id', 'data.channel_order_id']);

        // 2. Extract Event Details
        $rawStatus = $this->extract($payload, ['current_status', 'shipment_status', 'status', 'tracking_status', 'data.current_status', 'data.shipment_status', 'data.status', 'tracking_data.status']);
        $statusCode = $this->extract($payload, ['current_status_id', 'shipment_status_id', 'status_code', 'data.status_code']);
        $description = $this->extract($payload, ['status_description', 'activity', 'remark', 'remarks', 'message', 'data.status_description', 'tracking_data.activity']);
        $location = $this->extract($payload, ['location', 'current_location', 'scan_location', 'data.location']);
        $eventTime = $this->extract($payload, ['event_time', 'scan_time', 'status_time', 'updated_at', 'event_date', 'data.event_time', 'data.updated_at']);
        $trackingUrl = $this->extract($payload, ['tracking_url', 'track_url', 'data.tracking_url', 'data.track_url']);

        // 3. Find matching shipment
        $shipment = $this->findShipment($awbCode, $shipmentId, $orderId);

        if (!$shipment) {
            Log::warning('Logistics Webhook: Received tracking update but no matching shipment found.', [
                'awb_code' => $awbCode,
                'shipment_id' => $shipmentId,
                'order_id' => $orderId,
            ]);

            return [
                'success' => true,
                'message' => 'Webhook received but no matching shipment was found.',
                'shipment_id' => null,
                'matched_by' => null,
            ];
        }

        // Determine match reason for debug
        $matchedBy = null;
        if ($awbCode && $shipment->awb_code == $awbCode) $matchedBy = 'awb_code';
        elseif ($shipmentId && $shipment->provider_shipment_id == $shipmentId) $matchedBy = 'provider_shipment_id';
        elseif ($orderId && $shipment->provider_order_id == $orderId) $matchedBy = 'provider_order_id';

        // 4. Normalize Status
        $normalizedStatus = $this->normalizeStatus($rawStatus, $statusCode);

        // 5. Deduplicate and Store Event
        $isDuplicate = $this->isDuplicateEvent($shipment, $rawStatus, $description, $location, $eventTime);

        if (!$isDuplicate) {
            $this->shipmentService->recordEvent($shipment, [
                'event_code' => $statusCode ?? $normalizedStatus,
                'event_status' => $rawStatus ?? 'unknown',
                'event_description' => $description,
                'location' => $location,
                'event_time' => $eventTime ? \Carbon\Carbon::parse($eventTime) : now(),
                'raw_payload' => $payload,
            ]);
        }

        // 6. Update Shipment
        $metadata = $shipment->metadata ?? [];
        $metadata['last_raw_status'] = $rawStatus;
        $metadata['last_status_code'] = $statusCode;
        $metadata['last_tracking_update_at'] = now()->toDateTimeString();
        $metadata['last_tracking_payload'] = $payload;

        $updateData = [
            'status' => $normalizedStatus !== 'unknown' ? $normalizedStatus : $shipment->status,
            'last_tracked_at' => now(),
            'metadata' => $metadata,
            'provider_response' => $payload, // Update full payload for history
        ];

        // Only fill missing URLs/AWBs if we just got them
        if (empty($shipment->awb_code) && $awbCode) {
            $updateData['awb_code'] = $awbCode;
        }
        if (empty($shipment->tracking_url) && $trackingUrl) {
            $updateData['tracking_url'] = $trackingUrl;
        }

        $shipment->update($updateData);

        // 7. Update Related Order if possible
        $this->updateRelatedOrder($shipment, $normalizedStatus);

        return [
            'success' => true,
            'message' => 'Tracking update processed.',
            'shipment_id' => $shipment->id,
            'matched_by' => $matchedBy,
            'status' => $normalizedStatus,
            'event_code' => $statusCode ?? $normalizedStatus,
        ];
    }

    /**
     * Safely extract first valid value from an array of possible keys.
     */
    protected function extract(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $val = data_get($payload, $key);
            if (!empty($val) && is_string($val)) {
                return $val;
            }
        }
        return null;
    }

    /**
     * Find Shipment in specific priority order.
     */
    protected function findShipment(?string $awbCode, ?string $shipmentId, ?string $orderId): ?ShippingShipment
    {
        if ($awbCode) {
            $shipment = ShippingShipment::where('awb_code', $awbCode)->latest()->first();
            if ($shipment) return $shipment;
        }
        if ($shipmentId) {
            $shipment = ShippingShipment::where('provider_shipment_id', $shipmentId)->latest()->first();
            if ($shipment) return $shipment;
        }
        if ($orderId) {
            $shipment = ShippingShipment::where('provider_order_id', $orderId)->latest()->first();
            if ($shipment) return $shipment;
        }
        return null;
    }

    /**
     * Check if exact event already exists.
     */
    protected function isDuplicateEvent(ShippingShipment $shipment, ?string $rawStatus, ?string $description, ?string $location, ?string $eventTime): bool
    {
        $query = $shipment->events()
            ->where('event_status', $rawStatus)
            ->where('event_description', $description);

        if ($location) {
            $query->where('location', $location);
        }

        if ($eventTime) {
            try {
                $parsedTime = \Carbon\Carbon::parse($eventTime)->toDateTimeString();
                $query->where('event_time', $parsedTime);
            } catch (\Exception $e) {}
        }

        return $query->exists();
    }

    /**
     * Normalize Shiprocket raw status to internal status.
     */
    protected function normalizeStatus(?string $rawStatus, ?string $rawStatusCode = null): string
    {
        if (empty($rawStatus)) return 'unknown';

        $status = strtolower(trim($rawStatus));

        $map = [
            'new' => 'created',
            'order created' => 'created',
            'manifested' => 'created',
            
            'awb assigned' => 'awb_assigned',
            
            'pickup scheduled' => 'pickup_scheduled',
            'pickup generated' => 'pickup_scheduled',
            
            'picked up' => 'picked_up',
            'shipped' => 'picked_up',
            
            'in transit' => 'in_transit',
            'in-transit' => 'in_transit',
            'reached at destination hub' => 'in_transit',
            
            'out for delivery' => 'out_for_delivery',
            'ofd' => 'out_for_delivery',
            
            'delivered' => 'delivered',
            
            'undelivered' => 'failed',
            'ndr' => 'failed',
            'failed delivery' => 'failed',
            
            'rto initiated' => 'rto',
            'rto in transit' => 'rto',
            'rto delivered' => 'rto',
            'return to origin' => 'rto',
            
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            
            'lost' => 'lost',
            'damaged' => 'damaged',
        ];

        return $map[$status] ?? 'unknown';
    }

    /**
     * Update related order if it has fulfillment/shipping status fields.
     */
    protected function updateRelatedOrder(ShippingShipment $shipment, string $normalizedStatus): void
    {
        if (!$shipment->shippable) {
            return;
        }

        $order = $shipment->shippable;
        
        // We only attempt to update shipment_status, shipping_status, or fulfillment_status
        $updated = false;
        if (in_array('shipping_status', $order->getFillable()) || \Schema::hasColumn($order->getTable(), 'shipping_status')) {
            $order->update(['shipping_status' => $normalizedStatus]);
            $updated = true;
        } elseif (in_array('fulfillment_status', $order->getFillable()) || \Schema::hasColumn($order->getTable(), 'fulfillment_status')) {
            $order->update(['fulfillment_status' => $normalizedStatus]);
            $updated = true;
        } elseif (in_array('shipment_status', $order->getFillable()) || \Schema::hasColumn($order->getTable(), 'shipment_status')) {
            $order->update(['shipment_status' => $normalizedStatus]);
            $updated = true;
        }

        // If delivered, set delivered_at if available and empty
        if ($normalizedStatus === 'delivered') {
            if ((in_array('delivered_at', $order->getFillable()) || \Schema::hasColumn($order->getTable(), 'delivered_at')) && empty($order->delivered_at)) {
                $order->update(['delivered_at' => now()]);
            }
        }
        
        // Optional Notification hook
        // if ($updated && in_array($normalizedStatus, ['picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'rto'])) {
        //     // TODO: Trigger notification to user
        // }
    }
}
