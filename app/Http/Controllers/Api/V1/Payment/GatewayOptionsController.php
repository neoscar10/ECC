<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentGatewayAvailabilityService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class GatewayOptionsController extends Controller
{
    use ApiResponse;

    protected PaymentGatewayAvailabilityService $availabilityService;

    public function __construct(PaymentGatewayAvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Retrieve payment gateway options.
     */
    public function index(Request $request)
    {
        $includeDisabled = $request->boolean('include_disabled', false);

        $options = $this->availabilityService->publicOptions();

        if (!$includeDisabled) {
            $options = array_values(array_filter($options, function ($opt) {
                return $opt['enabled'] === true;
            }));
        }

        return $this->success([
            'default_gateway' => $this->availabilityService->defaultGateway(),
            'gateways' => $options,
        ], 'Payment gateways retrieved successfully.');
    }
}
