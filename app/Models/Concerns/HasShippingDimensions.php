<?php

namespace App\Models\Concerns;

trait HasShippingDimensions
{
    /**
     * Get the volumetric weight of the product in kg.
     * Formula: (L * B * H) / Divisor
     *
     * @return float|null
     */
    public function getVolumetricWeightKgAttribute(): ?float
    {
        if (!$this->length_cm || !$this->breadth_cm || !$this->height_cm) {
            return null;
        }

        $divisor = (float) config('shipping.volumetric_divisor', 5000);

        return round(((float) $this->length_cm * (float) $this->breadth_cm * (float) $this->height_cm) / $divisor, 3);
    }

    /**
     * Get the chargeable weight of the product in kg.
     * Higher of actual weight and volumetric weight.
     *
     * @return float|null
     */
    public function getChargeableWeightKgAttribute(): ?float
    {
        $actual = $this->weight_kg ? (float) $this->weight_kg : null;
        $volumetric = $this->volumetric_weight_kg;

        if ($actual === null && $volumetric === null) {
            return null;
        }

        return max($actual ?? 0, $volumetric ?? 0);
    }
}
