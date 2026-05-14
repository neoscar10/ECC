<?php

return [
    'volumetric_divisor' => env('SHIPPING_VOLUMETRIC_DIVISOR', 5000),
    'default_weight_kg' => env('SHIPPING_DEFAULT_WEIGHT_KG', 0.5),
    'default_length_cm' => env('SHIPPING_DEFAULT_LENGTH_CM', 10),
    'default_breadth_cm' => env('SHIPPING_DEFAULT_BREADTH_CM', 10),
    'default_height_cm' => env('SHIPPING_DEFAULT_HEIGHT_CM', 10),
];
