<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    use ApiResponse;

    /**
     * Get Cricket Profile options (formats, eras).
     */
    public function getCricketProfileOptions(): JsonResponse
    {
        $options = config('ecc_meta.cricket_profile');
        
        // Transform for API: [{code: 'CODE', label: 'Label'}, ...]
        $data = [
            'formats' => $this->transformOptions($options['formats'] ?? []),
            'eras' => $this->transformOptions($options['eras'] ?? []),
        ];

        return $this->success($data);
    }

    /**
     * Get Collector Intent options (focus, investment_horizon).
     */
    public function getCollectorIntentOptions(): JsonResponse
    {
        $options = config('ecc_meta.collector_intent');

        $data = [
            'focus' => $this->transformOptions($options['focus'] ?? []),
            'investment_horizon' => $this->transformOptions($options['investment_horizon'] ?? []),
        ];

        return $this->success($data);
    }

    private function transformOptions(array $options): array
    {
        $transformed = [];
        foreach ($options as $code => $label) {
            $transformed[] = [
                'code' => $code,
                'label' => $label,
            ];
        }
        return $transformed;
    }
}
