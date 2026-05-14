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
        $record = $variant ?: $product;
        return $this->measurementFromRecord($record);
    }

    /**
     * Extract and normalize measurement from a model record.
     */
    public function measurementFromRecord($record): array
    {
        $data = $this->extractData($record);
        $source = ($record instanceof ShopProductVariant) ? 'variant' : 'product';
        
        if (!$this->hasAtLeastWeightOrDimensions($data)) {
            return $this->normalizeMeasurement([], true);
        }

        return $this->normalizeMeasurement(array_merge($data, ['source' => $source]), false);
    }

    /**
     * Normalize measurement data and calculate derived weights.
     */
    public function normalizeMeasurement(array $data, bool $useFallback = true): array
    {
        $result = [
            'weight_kg' => $data['weight_kg'] ?? null,
            'length_cm' => $data['length_cm'] ?? null,
            'breadth_cm' => $data['breadth_cm'] ?? null,
            'height_cm' => $data['height_cm'] ?? null,
            'source' => $data['source'] ?? 'partial',
            'is_fallback' => false,
        ];

        if ($useFallback && !$this->hasAtLeastWeightOrDimensions($result)) {
            $result['weight_kg'] = (float) config('shipping.default_weight_kg', 0.5);
            $result['length_cm'] = (float) config('shipping.default_length_cm', 10);
            $result['breadth_cm'] = (float) config('shipping.default_breadth_cm', 10);
            $result['height_cm'] = (float) config('shipping.default_height_cm', 10);
            $result['source'] = 'fallback';
            $result['is_fallback'] = true;
        }

        $result['volumetric_weight_kg'] = $this->volumetricWeightKg($result['length_cm'], $result['breadth_cm'], $result['height_cm']);
        $result['chargeable_weight_kg'] = $this->chargeableWeightKg($result['weight_kg'], $result['volumetric_weight_kg']);

        return $result;
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

        return max((float) ($actualWeight ?? 0), (float) ($volumetricWeight ?? 0));
    }

    /**
     * Check if measurement data has all dimensions.
     */
    public function hasCompleteDimensions(array $data): bool
    {
        return !empty($data['length_cm']) && !empty($data['breadth_cm']) && !empty($data['height_cm']);
    }

    /**
     * Calculate total package measurement from an order.
     */
    public function measurementFromShopOrder($order): array
    {
        $order->load(['items.product', 'items.variant']);

        $totalWeight = 0;
        $maxLength = 0;
        $maxBreadth = 0;
        $maxHeight = 0;
        $hasFallback = false;
        $itemsData = [];

        foreach ($order->items as $item) {
            $record = $item->variant ?: $item->product;
            $measurement = $this->measurementFromRecord($record);
            
            if ($measurement['is_fallback']) {
                $hasFallback = true;
            }

            $quantity = $item->quantity;
            $totalWeight += ($measurement['weight_kg'] ?? 0) * $quantity;
            
            // Simple logic: largest dimensions of any single item defines the package box
            $maxLength = max($maxLength, (float) ($measurement['length_cm'] ?? 0));
            $maxBreadth = max($maxBreadth, (float) ($measurement['breadth_cm'] ?? 0));
            $maxHeight = max($maxHeight, (float) ($measurement['height_cm'] ?? 0));

            $itemsData[] = [
                'order_item_id' => $item->id,
                'product_id' => $item->shop_product_id,
                'variant_id' => $item->shop_product_variant_id,
                'quantity' => $quantity,
                'measurement_source' => $measurement['source'],
                'weight_kg' => $measurement['weight_kg'],
                'length_cm' => $measurement['length_cm'],
                'breadth_cm' => $measurement['breadth_cm'],
                'height_cm' => $measurement['height_cm'],
            ];
        }

        $volumetricWeight = $this->volumetricWeightKg($maxLength, $maxBreadth, $maxHeight);
        $chargeableWeight = $this->chargeableWeightKg($totalWeight, $volumetricWeight);

        return [
            'weight_kg' => round($totalWeight, 3),
            'length_cm' => round($maxLength, 2),
            'breadth_cm' => round($maxBreadth, 2),
            'height_cm' => round($maxHeight, 2),
            'volumetric_weight_kg' => $volumetricWeight,
            'chargeable_weight_kg' => $chargeableWeight,
            'source' => 'order_items',
            'items' => $itemsData,
            'has_fallback' => $hasFallback,
        ];
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
        return ($data['weight_kg'] ?? null) !== null || 
               (($data['length_cm'] ?? null) !== null && ($data['breadth_cm'] ?? null) !== null && ($data['height_cm'] ?? null) !== null);
    }
}
