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
