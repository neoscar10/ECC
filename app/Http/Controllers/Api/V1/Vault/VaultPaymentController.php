<?php

namespace App\Http\Controllers\Api\V1\Vault;

use App\Http\Controllers\Controller;
use App\Models\VaultRemovalRequest;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentGatewayAvailabilityService;
use App\Http\Resources\Payment\PaymentResource;
use App\Support\ApiResponse;
use App\Support\Payments\PaymentPurpose;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class VaultPaymentController extends Controller
{
    use ApiResponse;

    protected PaymentManager $paymentManager;
    protected PaymentGatewayAvailabilityService $availabilityService;

    public function __construct(
        PaymentManager $paymentManager,
        PaymentGatewayAvailabilityService $availabilityService
    ) {
        $this->paymentManager = $paymentManager;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Initiate payment for a vault removal request.
     */
    public function initiatePayment(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        // 1. Ownership & Exists Validation
        $removalRequest = VaultRemovalRequest::where('user_id', $user->id)->find($id);

        if (!$removalRequest) {
            return $this->error('Vault removal request not found or does not belong to you.', 404);
        }

        // 2. Accept payment gateway validation
        $validator = Validator::make($request->all(), [
            'payment_gateway' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        // 3. Prevent duplicate paid payments / Validate payable
        if ($removalRequest->payment_status === VaultRemovalRequest::PAYMENT_PAID) {
            return $this->error('This request has already been paid.', 400);
        }

        if (empty($removalRequest->delivery_fee) || (float) $removalRequest->delivery_fee <= 0) {
            return $this->error('No delivery fee is set for this request.', 400);
        }

        // 4. Validate Gateway availability
        try {
            $gatewayName = $this->availabilityService->validateGateway(
                $request->input('payment_gateway'),
                PaymentPurpose::VAULT_DELIVERY
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        // 5. Initiate Payment via PaymentManager
        try {
            $paymentInitiation = $this->paymentManager->initiatePayment(
                payable: $removalRequest,
                amount: $removalRequest->delivery_fee,
                purpose: PaymentPurpose::VAULT_DELIVERY,
                user: $user,
                gateway: $gatewayName
            );

            $payment = $paymentInitiation['payment'];

            // Update request status to pending_payment if not already
            if ($removalRequest->payment_status !== VaultRemovalRequest::PAYMENT_PENDING) {
                $removalRequest->update([
                    'payment_status' => VaultRemovalRequest::PAYMENT_PENDING
                ]);
            }

            return $this->success([
                'vault_request' => [
                    'id' => $removalRequest->id,
                    'status' => $removalRequest->status,
                    'payment_status' => $removalRequest->payment_status,
                    'delivery_fee' => (float) $removalRequest->delivery_fee,
                    'tracking_number' => $removalRequest->tracking_number,
                ],
                'payment' => new PaymentResource($payment)
            ], 'Payment initiated successfully.');

        } catch (Exception $e) {
            return $this->error('Payment initiation failed: ' . $e->getMessage(), 500);
        }
    }
}
