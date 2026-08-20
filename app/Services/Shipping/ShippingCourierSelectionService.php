<?php

namespace App\Services\Shipping;

use App\Models\Shipping\ShippingRateQuote;
use App\Services\Shipping\Shiprocket\ShiprocketClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShippingCourierSelectionService
{
    protected $shiprocket;

    public function __construct(ShiprocketClient $shiprocket)
    {
        $this->shiprocket = $shiprocket;
    }

    /**
     * Get rate quotes for a shipment draft.
     */
    public function quoteForShipmentDraft(array $payload): ShippingRateQuote
    {
        $response = $this->fetchAvailableCouriers($payload);
        
        $availableCouriers = $this->extractAvailableCouriers($response);
        $selectedCourier = $this->selectBestCourier($availableCouriers);

        return $this->storeQuote($payload, $response, $selectedCourier);
    }

    /**
     * Fetch available couriers from Shiprocket.
     */
    public function fetchAvailableCouriers(array $payload): array
    {
        try {
            return $this->shiprocket->get('/courier/serviceability/', $this->buildServiceabilityPayload($payload));
        } catch (\Exception $e) {
            Log::error('Shiprocket serviceability fetch failed', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get quote and select best courier for a Shop Order.
     */
    public function quoteAndSelectForShopOrder($order, array $measurement): ?ShippingRateQuote
    {
        $pickupPincode = config('shiprocket.pickup_pincode');
        $deliveryPincode = $this->extractDeliveryPincode($order);

        if (!$pickupPincode || !$deliveryPincode) {
            Log::warning('Cannot fetch courier quote: missing pincode', [
                'order_id' => $order->id,
                'pickup' => (bool)$pickupPincode,
                'delivery' => (bool)$deliveryPincode,
            ]);
            return null;
        }

        $payload = array_merge($measurement, [
            'shippable_type' => get_class($order),
            'shippable_id' => $order->id,
            'user_id' => $order->user_id,
            'pickup_pincode' => $pickupPincode,
            'delivery_pincode' => $deliveryPincode,
            'payment_mode' => ($order->payment_status === 'paid') ? 'prepaid' : 'cod', // Simple mapping
        ]);

        $response = $this->fetchAvailableCouriers($payload);
        $availableCouriers = $this->extractAvailableCouriers($response);
        $selectedCourier = $this->selectBestCourier($availableCouriers);

        return $this->storeQuoteForOrder($order, $payload, $response, $selectedCourier);
    }

    /**
     * Extract available couriers from Shiprocket response.
     */
    public function extractAvailableCouriers(array $response): array
    {
        $couriers = $response['data']['available_courier_companies'] ?? $response['available_courier_companies'] ?? $response['data'] ?? [];
        
        if (!is_array($couriers)) {
            return [];
        }

        return array_map([$this, 'normalizeCourier'], $couriers);
    }

    /**
     * Normalize courier data into internal structure.
     */
    public function normalizeCourier(array $courier): array
    {
        $freightCharge = (float) ($courier['freight_charge'] ?? $courier['rate'] ?? $courier['shipping_charge'] ?? $courier['courier_charge'] ?? $courier['charges'] ?? 0);
        $codCharge = (float) ($courier['cod_charges'] ?? $courier['cod_charge'] ?? 0);
        
        $totalCharge = (float) ($courier['total_charge'] ?? 0);
        if ($totalCharge <= 0) {
            $totalCharge = $freightCharge + $codCharge;
        }

        return [
            'courier_company_id' => (string) ($courier['courier_company_id'] ?? ''),
            'courier_name' => $courier['courier_name'] ?? '',
            'rating' => (float) ($courier['rating'] ?? 0),
            'freight_charge' => $freightCharge,
            'cod_charge' => $codCharge,
            'total_charge' => $totalCharge,
            'etd' => $courier['etd'] ?? null,
            'estimated_delivery_days' => (int) ($courier['estimated_delivery_days'] ?? $courier['estimated_delivery_days_min'] ?? 0),
            'is_cod_available' => (bool) ($courier['cod'] ?? false),
            'is_prepaid_available' => (bool) ($courier['prepaid'] ?? true),
            'raw' => $courier,
        ];
    }

    /**
     * Select the best courier based on rating and price.
     */
    public function selectBestCourier(array $availableCouriers): ?array
    {
        if (empty($availableCouriers)) {
            return null;
        }

        usort($availableCouriers, function ($a, $b) {
            // 1. Highest rating
            if ($a['rating'] != $b['rating']) {
                return $b['rating'] <=> $a['rating'];
            }
            // 2. Lowest total charge
            if ($a['total_charge'] != $b['total_charge']) {
                return $a['total_charge'] <=> $b['total_charge'];
            }
            // 3. Shortest ETD
            if ($a['estimated_delivery_days'] != $b['estimated_delivery_days']) {
                return $a['estimated_delivery_days'] <=> $b['estimated_delivery_days'];
            }
            return 0;
        });

        return $availableCouriers[0];
    }

    public function buildServiceabilityPayload(array $data): array
    {
        return [
            'pickup_postcode' => $data['pickup_pincode'] ?? config('shiprocket.pickup_pincode') ?? config('shiprocket.pickup_location'),
            'delivery_postcode' => $data['delivery_pincode'],
            'cod' => ($data['payment_mode'] === 'cod') ? 1 : 0,
            'weight' => max(0.05, (float) ($data['weight_kg'] ?? 0)),
            'length' => max(0.5, (float) ($data['length_cm'] ?? 0)),
            'breadth' => max(0.5, (float) ($data['breadth_cm'] ?? 0)),
            'height' => max(0.5, (float) ($data['height_cm'] ?? 0)),
        ];
    }

    /**
     * Store rate quote result in database.
     */
    public function storeQuote(array $context, array $rawResponse, ?array $selectedCourier): ShippingRateQuote
    {
        $ttl = config('shiprocket.rate_quote_ttl_minutes', 60);

        return ShippingRateQuote::create([
            'shippable_type' => $context['shippable_type'] ?? null,
            'shippable_id' => $context['shippable_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'shipping_provider' => 'shiprocket',
            'pickup_pincode' => $context['pickup_pincode'] ?? config('shiprocket.pickup_pincode') ?? config('shiprocket.pickup_location'),
            'delivery_pincode' => $context['delivery_pincode'],
            'payment_mode' => $context['payment_mode'] ?? 'prepaid',
            'weight_kg' => $context['weight_kg'],
            'length_cm' => $context['length_cm'],
            'breadth_cm' => $context['breadth_cm'],
            'height_cm' => $context['height_cm'],
            'volumetric_weight_kg' => $context['volumetric_weight_kg'] ?? null,
            'chargeable_weight_kg' => $context['chargeable_weight_kg'] ?? null,
            'selected_courier_company_id' => $selectedCourier['courier_company_id'] ?? null,
            'selected_courier_name' => $selectedCourier['courier_name'] ?? null,
            'selected_courier_rating' => $selectedCourier['rating'] ?? null,
            'selected_freight_charge' => $selectedCourier['freight_charge'] ?? null,
            'selected_cod_charge' => $selectedCourier['cod_charge'] ?? null,
            'selected_total_charge' => $selectedCourier['total_charge'] ?? null,
            'selected_etd' => $selectedCourier['etd'] ?? null,
            'selected_estimated_delivery_days' => $selectedCourier['estimated_delivery_days'] ?? null,
            'selected_courier_raw' => $selectedCourier['raw'] ?? null,
            'quotes' => $rawResponse['data']['available_courier_companies'] ?? $rawResponse['available_courier_companies'] ?? $rawResponse['data'] ?? [],
            'raw_response' => $rawResponse,
            'status' => $selectedCourier ? 'fetched' : 'failed',
            'expires_at' => now()->addMinutes($ttl),
        ]);
    }

    /**
     * Store rate quote for a Shop Order.
     */
    public function storeQuoteForOrder($order, array $context, array $rawResponse, ?array $selectedCourier): ShippingRateQuote
    {
        return $this->storeQuote($context, $rawResponse, $selectedCourier);
    }

    /**
     * Extract delivery pincode from order.
     */
    protected function extractDeliveryPincode($order): ?string
    {
        $snapshot = $order->shipping_address_snapshot;
        
        if (is_array($snapshot)) {
            return $snapshot['postal_code'] ?? $snapshot['pincode'] ?? $snapshot['postcode'] ?? null;
        }

        return $order->shipping_pincode ?? $order->shipping_postcode ?? $order->shipping_postal_code ?? null;
    }
}
