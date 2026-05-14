<?php

namespace App\Services\Shipping;

use App\Models\Shipping\ShippingEvent;
use App\Models\Shipping\ShippingRateQuote;
use App\Models\Shipping\ShippingShipment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShipmentService
{
    /**
     * Create a draft shipment for a shippable model.
     */
    public function createDraftFor($shippable, array $data = []): ShippingShipment
    {
        return ShippingShipment::create([
            'uuid' => (string) Str::uuid(),
            'shippable_type' => get_class($shippable),
            'shippable_id' => $shippable->id,
            'user_id' => $data['user_id'] ?? $shippable->user_id ?? $shippable->customer_id ?? null,
            'shipping_provider' => 'shiprocket',
            'status' => 'draft',
            'pickup_location' => $data['pickup_location'] ?? config('shiprocket.pickup_location'),
            'pickup_pincode' => $data['pickup_pincode'] ?? null,
            'delivery_pincode' => $data['delivery_pincode'] ?? null,
            'payment_mode' => $data['payment_mode'] ?? 'prepaid',
            'delivery_address_snapshot' => $data['delivery_address'] ?? null,
            'package_snapshot' => $data['package_items'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Update shipment measurements.
     */
    public function updateMeasurement(ShippingShipment $shipment, array $measurement): ShippingShipment
    {
        $shipment->update([
            'weight_kg' => $measurement['weight_kg'] ?? $shipment->weight_kg,
            'length_cm' => $measurement['length_cm'] ?? $shipment->length_cm,
            'breadth_cm' => $measurement['breadth_cm'] ?? $shipment->breadth_cm,
            'height_cm' => $measurement['height_cm'] ?? $shipment->height_cm,
            'volumetric_weight_kg' => $measurement['volumetric_weight_kg'] ?? $shipment->volumetric_weight_kg,
            'chargeable_weight_kg' => $measurement['chargeable_weight_kg'] ?? $shipment->chargeable_weight_kg,
        ]);

        return $shipment;
    }

    /**
     * Attach selected courier details to shipment.
     */
    public function attachSelectedCourier(ShippingShipment $shipment, array $selectedCourier, ?ShippingRateQuote $quote = null): ShippingShipment
    {
        $shipment->markCourierSelected($selectedCourier, $quote);
        return $shipment;
    }

    /**
     * Mark shipment as ready to ship.
     */
    public function markReadyToShip(ShippingShipment $shipment): ShippingShipment
    {
        if ($shipment->status === 'courier_selected') {
            $shipment->update(['status' => 'ready_to_ship']);
        }
        return $shipment;
    }

    /**
     * Find a shipment by provider references.
     */
    public function findByProviderReference(?string $providerOrderId = null, ?string $providerShipmentId = null, ?string $awbCode = null): ?ShippingShipment
    {
        $query = ShippingShipment::query();

        if ($providerOrderId) {
            $query->orWhere('provider_order_id', $providerOrderId);
        }
        if ($providerShipmentId) {
            $query->orWhere('provider_shipment_id', $providerShipmentId);
        }
        if ($awbCode) {
            $query->orWhere('awb_code', $awbCode);
        }

        if (!$providerOrderId && !$providerShipmentId && !$awbCode) {
            return null;
        }

        return $query->first();
    }

    /**
     * Record a shipping event.
     */
    public function recordEvent(ShippingShipment $shipment, array $eventData): ShippingEvent
    {
        return $shipment->events()->create([
            'shipping_provider' => $shipment->shipping_provider,
            'event_code' => $eventData['event_code'] ?? null,
            'event_status' => $eventData['event_status'] ?? null,
            'event_description' => $eventData['event_description'] ?? null,
            'location' => $eventData['location'] ?? null,
            'event_time' => $eventData['event_time'] ?? now(),
            'raw_payload' => $eventData['raw_payload'] ?? $eventData,
        ]);
    }

    /**
     * Prepare courier selection for a Shop Order.
     */
    public function prepareCourierSelectionForShopOrder($order): ?ShippingShipment
    {
        try {
            $measurementService = app(ShippingMeasurementService::class);
            $courierService = app(ShippingCourierSelectionService::class);

            // 1. Create or get existing shipment draft
            $shipment = $this->getOrCreateDraftForShopOrder($order);

            // 2. Calculate measurement
            $measurement = $measurementService->measurementFromShopOrder($order);
            $this->updateMeasurement($shipment, $measurement);

            // 3. Update Pincodes
            $pickupPincode = config('shiprocket.pickup_pincode');
            $deliveryPincode = $this->extractDeliveryPincode($order);
            
            $shipment->update([
                'pickup_pincode' => $pickupPincode,
                'delivery_pincode' => $deliveryPincode,
            ]);

            // 4. Get Quote and Select Best Courier
            $quote = $courierService->quoteAndSelectForShopOrder($order, $measurement);

            if ($quote && $quote->hasSelectedCourier()) {
                $selectedCourier = $quote->selected_courier_raw;
                $this->attachSelectedCourier($shipment, $selectedCourier, $quote);
                $shipment->update(['status' => 'courier_selected']);
            } else {
                $this->failShipment($shipment, 'No couriers available or quote failed.', [
                    'quote_id' => $quote?->id,
                    'status' => $quote?->status
                ]);
            }

            return $shipment;

        } catch (\Throwable $e) {
            Log::error('Failed to prepare courier selection for shop order', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (isset($shipment)) {
                $this->failShipment($shipment, 'Exception during courier selection: ' . $e->getMessage());
            }

            return null;
        }
    }

    /**
     * Get shipment for a Shop Order.
     */
    public function getShipmentForShopOrder($order): ?ShippingShipment
    {
        return ShippingShipment::where('shippable_type', get_class($order))
            ->where('shippable_id', $order->id)
            ->first();
    }

    /**
     * Get or create a draft shipment for a Shop Order.
     */
    public function getOrCreateDraftForShopOrder($order): ShippingShipment
    {
        $shipment = $this->getShipmentForShopOrder($order);

        if (!$shipment) {
            $shipment = $this->createDraftFor($order, [
                'delivery_pincode' => $this->extractDeliveryPincode($order),
                'delivery_address' => $order->shipping_address_snapshot,
            ]);
        }

        return $shipment;
    }

    /**
     * Refresh courier selection for a Shop Order.
     */
    public function refreshCourierSelectionForShopOrder($order): ?ShippingShipment
    {
        // Simple implementation: just call prepare again
        return $this->prepareCourierSelectionForShopOrder($order);
    }

    /**
     * Helper to extract delivery pincode.
     */
    protected function extractDeliveryPincode($order): ?string
    {
        $snapshot = $order->shipping_address_snapshot;
        if (is_array($snapshot)) {
            return $snapshot['postal_code'] ?? $snapshot['pincode'] ?? $snapshot['postcode'] ?? null;
        }
        return $order->shipping_pincode ?? $order->shipping_postcode ?? $order->shipping_postal_code ?? null;
    }

    /**
     * Mark shipment as failed.
     */
    public function failShipment(ShippingShipment $shipment, string $message, array $context = []): ShippingShipment
    {
        $shipment->markFailed($message, $context);
        return $shipment;
    }
}
