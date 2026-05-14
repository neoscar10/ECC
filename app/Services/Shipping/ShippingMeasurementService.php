<?php

namespace App\Services\Shipping;

use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariant;

class ShippingMeasurementService
{
    /**
     * Get measurement data for a product or variant.
     * Fallback order: Variant -> Product -> Config defaults.
     */
    public function productMeasurement(ShopProduct $product, ?ShopProductVariant $variant = null): array
    {
        $source = 'fallback';
        
        // 1. Try Variant
        if ($variant) {
            $data = $this->extractData($variant);
            if ($this->hasAtLeastWeightOrDimensions($data)) {
                $source = 'variant';
                return array_merge($data, [
                    'volumetric_weight_kg' => $variant->volumetric_weight_kg,
                    'chargeable_weight_kg' => $variant->chargeable_weight_kg,
                    'source' => $source,
                ]);
            }
        }

        // 2. Try Product
        $data = $this->extractData($product);
        if ($this->hasAtLeastWeightOrDimensions($data)) {
            $source = 'product';
            return array_merge($data, [
                'volumetric_weight_kg' => $product->volumetric_weight_kg,
                'chargeable_weight_kg' => $product->chargeable_weight_kg,
                'source' => $source,
            ]);
        }

        // 3. Fallback to config
        $data = [
            'weight_kg' => (float) config('shipping.default_weight_kg', 0.5),
            'length_cm' => (float) config('shipping.default_length_cm', 10),
            'breadth_cm' => (float) config('shipping.default_breadth_cm', 10),
            'height_cm' => (float) config('shipping.default_height_cm', 10),
        ];

        $volumetric = $this->volumetricWeightKg($data['length_cm'], $data['breadth_cm'], $data['height_cm']);

        return array_merge($data, [
            'volumetric_weight_kg' => $volumetric,
            'chargeable_weight_kg' => $this->chargeableWeightKg($data['weight_kg'], $volumetric),
            'source' => $source,
        ]);
    }

    /**
     * Calculate volumetric weight.
     */
    public function volumetricWeightKg(?float $length, ?float $breadth, ?float $height): ?float
    {
        if (!$length || !$breadth || !$height) {
            return null;
        }

        $divisor = (float) config('shipping.volumetric_divisor', 5000);

        return round(($length * $breadth * $height) / $divisor, 3);
    }

    /**
     * Calculate chargeable weight.
     */
    public function chargeableWeightKg(?float $actualWeight, ?float $volumetricWeight): ?float
    {
        if ($actualWeight === null && $volumetricWeight === null) {
            return null;
        }

        return max($actualWeight ?? 0, $volumetricWeight ?? 0);
    }

    /**
     * Check if measurement data has all dimensions.
     */
    public function hasCompleteDimensions(array $data): bool
    {
        return !empty($data['length_cm']) && !empty($data['breadth_cm']) && !empty($data['height_cm']);
    }

    /**
     * Private helper to extract fields from model.
     */
    private function extractData($model): array
    {
        return [
            'weight_kg' => $model->weight_kg ? (float) $model->weight_kg : null,
            'length_cm' => $model->length_cm ? (float) $model->length_cm : null,
            'breadth_cm' => $model->breadth_cm ? (float) $model->breadth_cm : null,
            'height_cm' => $model->height_cm ? (float) $model->height_cm : null,
        ];
    }

    /**
     * Private helper to check if we have any useful data.
     */
    private function hasAtLeastWeightOrDimensions(array $data): bool
    {
        return $data['weight_kg'] !== null || ($data['length_cm'] !== null && $data['breadth_cm'] !== null && $data['height_cm'] !== null);
    }
}
