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
        $purpose = $request->query('purpose') ?: $request->query('method'); // handle method/purpose interchangeably if needed

        $options = $this->availabilityService->publicOptions($purpose);

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

    /**
     * Retrieve payment options list matching the dynamic filters.
     */
    public function paymentOptions(Request $request)
    {
        $purpose = $request->query('purpose');
        $settingsService = app(\App\Services\Payments\PaymentSettingsService::class);
        
        $gatewayCodes = $settingsService->getGatewaysForPurpose($purpose ?: 'shop_order');
        
        $options = [];
        foreach ($gatewayCodes as $code) {
            $dbGateway = \App\Models\PaymentGateway::where('code', $code)->first();
            if ($dbGateway) {
                // If in maintenance mode, it is excluded or flagged
                $methods = \App\Models\PaymentGatewayMethod::where('gateway_id', $dbGateway->id)
                    ->where('is_enabled', true)
                    ->pluck('method')
                    ->toArray();

                $options[] = [
                    'gateway' => $code,
                    'name' => $dbGateway->name,
                    'is_default' => $dbGateway->is_default,
                    'display_order' => $dbGateway->display_order,
                    'available_methods' => $methods,
                    'maintenance_status' => false,
                ];
            }
        }

        return $this->success($options, 'Payment options retrieved successfully.');
    }
}
