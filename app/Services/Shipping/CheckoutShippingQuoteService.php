<?php

namespace App\Services\Shipping;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class CheckoutShippingQuoteService
{
    protected ShippingMeasurementService $measurementService;
    protected ShippingCourierSelectionService $courierService;

    public function __construct(
        ShippingMeasurementService $measurementService,
        ShippingCourierSelectionService $courierService
    ) {
        $this->measurementService = $measurementService;
        $this->courierService = $courierService;
    }

    /**
     * Get shipping quote for checkout.
     */
    public function quoteForCheckout(User $user, $cartItems, $address): array
    {
        // 1. Resolve DeliveryCountry relationship or lookup by country name if missing
        $deliveryCountry = null;
        if (is_object($address)) {
            $deliveryCountry = $address->deliveryCountry;
            if (!$deliveryCountry && !empty($address->country)) {
                $deliveryCountry = \App\Models\DeliveryCountry::where('name', $address->country)->first();
            }
        } elseif (is_array($address)) {
            if (!empty($address['delivery_country_id'])) {
                $deliveryCountry = \App\Models\DeliveryCountry::find($address['delivery_country_id']);
            } elseif (!empty($address['country'])) {
                $deliveryCountry = \App\Models\DeliveryCountry::where('name', $address['country'])->first();
            }
        }

        // 2. Check if the address belongs to a negotiated delivery country FIRST
        if ($deliveryCountry && $deliveryCountry->delivery_type === 'negotiated') {
            return [
                'success' => true,
                'shipping_charge' => 0,
                'currency' => 'INR',
                'delivery_type' => 'negotiated',
                'message' => 'To be discussed',
                'measurement' => $this->measurementService->measurementFromCartItems($cartItems),
                'rate_quote_id' => null,
            ];
        }

        // 3. Fallback for non-India countries (always negotiated delivery terms as Shiprocket is domestic India courier service)
        $countryName = is_array($address) ? ($address['country'] ?? null) : ($address->country ?? null);
        if ($countryName && strtolower(trim($countryName)) !== 'india') {
            return [
                'success' => true,
                'shipping_charge' => 0,
                'currency' => 'INR',
                'delivery_type' => 'negotiated',
                'message' => 'To be discussed',
                'measurement' => $this->measurementService->measurementFromCartItems($cartItems),
                'rate_quote_id' => null,
            ];
        }

        $deliveryPincode = $this->extractPincode($address);

        if (!$deliveryPincode) {
            return [
                'success' => false,
                'message' => 'Valid delivery pincode is required.',
                'reason' => 'missing_pincode',
            ];
        }

        return $this->quoteForPincode($user, $cartItems, $deliveryPincode);
    }

    /**
     * Get shipping quote for a specific pincode.
     */
    public function quoteForPincode(User $user, $cartItems, string $deliveryPincode): array
    {
        try {
            // 1. Calculate measurement from cart items
            $measurement = $this->measurementService->measurementFromCartItems($cartItems);

            // 2. Fetch rates and select best courier
            $pickupPincode = config('shiprocket.pickup_pincode');
            
            $payload = array_merge($measurement, [
                'user_id' => $user->id,
                'pickup_pincode' => $pickupPincode,
                'delivery_pincode' => $deliveryPincode,
                'payment_mode' => 'prepaid', // Checkout quotes are typically for prepaid
            ]);

            $response = $this->courierService->fetchAvailableCouriers($payload);
            $availableCouriers = $this->courierService->extractAvailableCouriers($response);
            $selectedCourier = $this->courierService->selectBestCourier($availableCouriers);

            if (!$selectedCourier) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'no_courier_available',
                    'measurement' => $measurement,
                ];
            }

            // 3. Store quote for reference
            $quote = $this->courierService->storeQuote($payload, $response, $selectedCourier);

            $shippingCharge = (float) ($selectedCourier['total_charge'] ?? 0);
            if ($shippingCharge <= 0) {
                $shippingCharge = (float) ($selectedCourier['freight_charge'] ?? 0) + (float) ($selectedCourier['cod_charge'] ?? 0);
            }

            return [
                'success' => true,
                'shipping_charge' => $shippingCharge,
                'currency' => 'INR',
                'pickup_pincode' => $pickupPincode,
                'delivery_pincode' => $deliveryPincode,
                'measurement' => $measurement,
                'selected_courier' => $selectedCourier,
                'rate_quote_id' => $quote->id,
            ];

        } catch (\Throwable $e) {
            Log::error('Checkout shipping quote failed', [
                'user_id' => $user->id,
                'pincode' => $deliveryPincode,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Unable to calculate shipping at this moment.',
                'reason' => 'exception',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract pincode from address object or array.
     */
    protected function extractPincode($address): ?string
    {
        if (is_array($address)) {
            return $address['postal_code'] ?? $address['pincode'] ?? $address['postcode'] ?? null;
        }

        if (is_object($address)) {
            return $address->postal_code ?? $address->pincode ?? $address->postcode ?? null;
        }

        return null;
    }
}
