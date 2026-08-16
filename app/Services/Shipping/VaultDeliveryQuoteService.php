<?php

namespace App\Services\Shipping;

use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\Shop\UserAddress;
use App\Services\Shipping\ShippingMeasurementService;
use App\Services\Shipping\ShippingCourierSelectionService;
use Illuminate\Support\Facades\Log;

class VaultDeliveryQuoteService
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
     * Get delivery quote for a Vault Item and selected address.
     */
    public function quoteForVaultItem(
        UserVaultItem $vaultItem,
        UserAddress $address,
        ?User $user = null,
        array $options = []
    ): array {
        $deliveryPincode = $address->postal_code;

        if (!$deliveryPincode) {
            return [
                'success' => false,
                'message' => 'Delivery is not available for this address.',
                'reason' => 'missing_pincode',
                'delivery_fee' => 0.00,
                'currency' => 'INR',
            ];
        }

        $resolvedUser = $user ?: $vaultItem->user ?: auth('web')->user();

        return $this->quoteForVaultItemAndPincode($vaultItem, $deliveryPincode, $resolvedUser, $options);
    }

    /**
     * Get delivery quote for a specific Vault Item and delivery pincode.
     */
    public function quoteForVaultItemAndPincode(
        UserVaultItem $vaultItem,
        string $deliveryPincode,
        ?User $user = null,
        array $options = []
    ): array {
        try {
            // 1. Fetch pickup pincode from config
            $pickupPincode = config('shiprocket.pickup_pincode');
            if (!$pickupPincode) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'missing_pickup_pincode',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            // 2. Validate delivery pincode
            $deliveryPincode = trim($deliveryPincode);
            if (empty($deliveryPincode)) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'missing_pincode',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            // 3. Calculate measurement metrics from Vault Item
            $measurement = $this->measurementService->measurementFromVaultItem($vaultItem);

            // Ensure dimensions and weight are valid
            if (empty($measurement['weight_kg']) || empty($measurement['length_cm']) || empty($measurement['breadth_cm']) || empty($measurement['height_cm'])) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'invalid_measurement',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            // 4. Fetch available rates/couriers
            $resolvedUser = $user ?: $vaultItem->user ?: auth('web')->user();
            
            $payload = [
                'shippable_type' => UserVaultItem::class,
                'shippable_id' => $vaultItem->id,
                'user_id' => $resolvedUser ? $resolvedUser->id : null,
                'pickup_pincode' => $pickupPincode,
                'delivery_pincode' => $deliveryPincode,
                'payment_mode' => 'prepaid',
                'weight_kg' => $measurement['weight_kg'],
                'length_cm' => $measurement['length_cm'],
                'breadth_cm' => $measurement['breadth_cm'],
                'height_cm' => $measurement['height_cm'],
                'volumetric_weight_kg' => $measurement['volumetric_weight_kg'],
                'chargeable_weight_kg' => $measurement['chargeable_weight_kg'],
            ];

            $response = $this->courierService->fetchAvailableCouriers($payload);
            
            if (empty($response) || (isset($response['success']) && $response['success'] === false)) {
                Log::error('Shiprocket serviceability response failure for Vault Item', [
                    'vault_item_id' => $vaultItem->id,
                    'pincode' => $deliveryPincode,
                    'response' => $response,
                ]);
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'shiprocket_error',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            $availableCouriers = $this->courierService->extractAvailableCouriers($response);
            $selectedCourier = $this->courierService->selectBestCourier($availableCouriers);

            if (!$selectedCourier) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'no_courier_available',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            // 5. Save rate quote database record with UserVaultItem polymorphic association
            $quote = $this->courierService->storeQuote($payload, $response, $selectedCourier);

            $deliveryFee = (float) ($selectedCourier['total_charge'] ?? 0);
            if ($deliveryFee <= 0) {
                $deliveryFee = (float) ($selectedCourier['freight_charge'] ?? 0) + (float) ($selectedCourier['cod_charge'] ?? 0);
            }

            // Formulate expected success response
            return [
                'success' => true,
                'message' => 'Delivery quote calculated.',
                'delivery_fee' => $deliveryFee,
                'currency' => 'INR',
                'pickup_pincode' => $pickupPincode,
                'delivery_pincode' => $deliveryPincode,
                'payment_mode' => 'prepaid',
                'measurement' => [
                    'weight_kg' => $measurement['weight_kg'],
                    'length_cm' => $measurement['length_cm'],
                    'breadth_cm' => $measurement['breadth_cm'],
                    'height_cm' => $measurement['height_cm'],
                    'volumetric_weight_kg' => $measurement['volumetric_weight_kg'],
                    'chargeable_weight_kg' => $measurement['chargeable_weight_kg'],
                    'source' => $measurement['source'],
                    'has_fallback' => $measurement['has_fallback'],
                ],
                'selected_courier' => [
                    'courier_company_id' => $selectedCourier['courier_company_id'],
                    'courier_name' => $selectedCourier['courier_name'],
                    'rating' => $selectedCourier['rating'],
                    'freight_charge' => $selectedCourier['freight_charge'],
                    'cod_charge' => $selectedCourier['cod_charge'],
                    'total_charge' => $selectedCourier['total_charge'],
                    'etd' => $selectedCourier['etd'] ?? null,
                    'estimated_delivery_days' => $selectedCourier['estimated_delivery_days'],
                    'raw' => $selectedCourier['raw'] ?? [],
                ],
                'rate_quote_id' => $quote->id,
            ];

        } catch (\Throwable $e) {
            Log::error('Vault Delivery Quote calculation failed with exception', [
                'vault_item_id' => $vaultItem->id,
                'pincode' => $deliveryPincode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Delivery is not available for this address.',
                'reason' => 'shiprocket_error',
                'delivery_fee' => 0.00,
                'currency' => 'INR',
            ];
        }
    }

    /**
     * Get delivery quote for multiple Vault Items and selected address.
     */
    public function quoteForVaultItems(
        $vaultItems,
        UserAddress $address,
        ?User $user = null,
        array $options = []
    ): array {
        $deliveryPincode = $address->postal_code;

        if (!$deliveryPincode) {
            return [
                'success' => false,
                'message' => 'Delivery is not available for this address.',
                'reason' => 'missing_pincode',
                'delivery_fee' => 0.00,
                'currency' => 'INR',
            ];
        }

        $resolvedUser = $user ?: $vaultItems->first()->user ?: auth('web')->user();

        return $this->quoteForVaultItemsAndPincode($vaultItems, $deliveryPincode, $resolvedUser, $options);
    }

    /**
     * Get delivery quote for multiple Vault Items and delivery pincode.
     */
    public function quoteForVaultItemsAndPincode(
        $vaultItems,
        string $deliveryPincode,
        ?User $user = null,
        array $options = []
    ): array {
        try {
            $pickupPincode = config('shiprocket.pickup_pincode');
            if (!$pickupPincode) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'missing_pickup_pincode',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            $deliveryPincode = trim($deliveryPincode);
            if (empty($deliveryPincode)) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'missing_pincode',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            $measurement = $this->measurementService->measurementFromVaultItems($vaultItems);

            if (empty($measurement['weight_kg']) || empty($measurement['length_cm']) || empty($measurement['breadth_cm']) || empty($measurement['height_cm'])) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'invalid_measurement',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            $resolvedUser = $user ?: $vaultItems->first()->user ?: auth('web')->user();
            
            $payload = [
                'shippable_type' => UserVaultItem::class,
                'shippable_id' => $vaultItems->first()->id, // Poly relation to the first item for the quote record
                'user_id' => $resolvedUser ? $resolvedUser->id : null,
                'pickup_pincode' => $pickupPincode,
                'delivery_pincode' => $deliveryPincode,
                'payment_mode' => 'prepaid',
                'weight_kg' => $measurement['weight_kg'],
                'length_cm' => $measurement['length_cm'],
                'breadth_cm' => $measurement['breadth_cm'],
                'height_cm' => $measurement['height_cm'],
                'volumetric_weight_kg' => $measurement['volumetric_weight_kg'],
                'chargeable_weight_kg' => $measurement['chargeable_weight_kg'],
            ];

            $response = $this->courierService->fetchAvailableCouriers($payload);
            
            if (empty($response) || (isset($response['success']) && $response['success'] === false)) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'shiprocket_error',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            $availableCouriers = $this->courierService->extractAvailableCouriers($response);
            $selectedCourier = $this->courierService->selectBestCourier($availableCouriers);

            if (!$selectedCourier) {
                return [
                    'success' => false,
                    'message' => 'Delivery is not available for this address.',
                    'reason' => 'no_courier_available',
                    'delivery_fee' => 0.00,
                    'currency' => 'INR',
                ];
            }

            $quote = $this->courierService->storeQuote($payload, $response, $selectedCourier);

            $deliveryFee = (float) ($selectedCourier['total_charge'] ?? 0);
            if ($deliveryFee <= 0) {
                $deliveryFee = (float) ($selectedCourier['freight_charge'] ?? 0) + (float) ($selectedCourier['cod_charge'] ?? 0);
            }

            return [
                'success' => true,
                'message' => 'Delivery quote calculated.',
                'delivery_fee' => $deliveryFee,
                'currency' => 'INR',
                'pickup_pincode' => $pickupPincode,
                'delivery_pincode' => $deliveryPincode,
                'payment_mode' => 'prepaid',
                'measurement' => [
                    'weight_kg' => $measurement['weight_kg'],
                    'length_cm' => $measurement['length_cm'],
                    'breadth_cm' => $measurement['breadth_cm'],
                    'height_cm' => $measurement['height_cm'],
                    'volumetric_weight_kg' => $measurement['volumetric_weight_kg'],
                    'chargeable_weight_kg' => $measurement['chargeable_weight_kg'],
                    'source' => $measurement['source'],
                    'has_fallback' => $measurement['has_fallback'],
                ],
                'selected_courier' => [
                    'courier_company_id' => $selectedCourier['courier_company_id'],
                    'courier_name' => $selectedCourier['courier_name'],
                    'rating' => $selectedCourier['rating'],
                    'freight_charge' => $selectedCourier['freight_charge'],
                    'cod_charge' => $selectedCourier['cod_charge'],
                    'total_charge' => $selectedCourier['total_charge'],
                    'etd' => $selectedCourier['etd'] ?? null,
                    'estimated_delivery_days' => $selectedCourier['estimated_delivery_days'],
                    'raw' => $selectedCourier['raw'] ?? [],
                ],
                'rate_quote_id' => $quote->id,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Delivery is not available for this address.',
                'reason' => 'shiprocket_error',
                'delivery_fee' => 0.00,
                'currency' => 'INR',
            ];
        }
    }
}
