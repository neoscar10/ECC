<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\ShopOrderResource;
use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentFinalizationService;
use App\Support\ApiResponse;
use App\Support\Payments\PaymentStatus;
use App\Support\Payments\PaymentPurpose;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CashfreePaymentController extends Controller
{
    use ApiResponse;

    protected PaymentManager $paymentManager;
    protected PaymentFinalizationService $finalizationService;

    public function __construct(PaymentManager $paymentManager, PaymentFinalizationService $finalizationService)
    {
        $this->paymentManager = $paymentManager;
        $this->finalizationService = $finalizationService;
    }

    /**
     * Verify the Cashfree payment from mobile SDK callback.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id'          => 'required|exists:payments,id',
            'order_id'            => 'nullable|string',
            'cf_order_id'         => 'nullable|string',
            'payment_session_id'  => 'nullable|string',
        ]);

        $payment = Payment::findOrFail($request->input('payment_id'));

        // Ensure the authenticated user owns the payment
        if ($payment->user_id !== auth()->id()) {
            return $this->error('Unauthorized access to payment.', 403);
        }

        // If gateway is not Cashfree
        if ($payment->gateway !== 'cashfree') {
            return $this->error('Invalid payment gateway for this verification endpoint.', 422);
        }

        // If already paid, return success
        if ($payment->status === PaymentStatus::PAID) {
            return $this->buildSuccessResponse($payment);
        }

        Log::info('API Cashfree verification received.', [
            'payment_id'         => $payment->id,
            'gateway_order_id'   => $payment->gateway_order_id,
            'request_order_id'   => $request->input('order_id'),
            'cf_order_id'        => $request->input('cf_order_id'),
        ]);

        try {
            $verificationData = [
                'gateway'            => 'cashfree',
                'gateway_order_id'   => $request->input('order_id') ?? $payment->gateway_order_id,
                'gateway_payment_id' => $request->input('cf_order_id') ?? $payment->gateway_payment_id,
                'payload'            => $request->all(),
            ];

            $result = $this->paymentManager->verifyPayment($payment, $verificationData);

            if ($result['result']->success && $result['payment']->status === PaymentStatus::PAID) {
                // Finalize payment polymorphically
                $this->finalizationService->finalizePaidPayment($result['payment']->fresh());

                return $this->buildSuccessResponse($result['payment']->fresh(), 'Payment verified successfully.');
            } elseif ($result['payment']->status === PaymentStatus::PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is still pending.',
                    'data'    => [
                        'payment' => (new \App\Http\Resources\Payment\PaymentResource($result['payment']))->resolve(request())
                    ],
                    'meta'    => null,
                    'errors'  => null,
                ], 202);
            } else {
                $reason = $result['result']->failureMessage ?? 'Payment verification failed.';
                $this->finalizationService->finalizeFailedPayment($result['payment']->fresh(), $reason);
                
                return $this->buildFailureResponse($result['payment']->fresh(), $reason);
            }
        } catch (\Exception $e) {
            Log::error('API Cashfree verification exception: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'trace'      => $e->getTraceAsString(),
            ]);

            return $this->error('Internal Server Error during verification.', 500);
        }
    }

    /**
     * Build standard verification success response.
     */
    protected function buildSuccessResponse(Payment $payment, string $message = 'Payment already paid.'): JsonResponse
    {
        $payable = $payment->payable;

        $data = [
            'payment' => (new \App\Http\Resources\Payment\PaymentResource($payment))->resolve(request())
        ];

        if ($payable) {
            if ($payment->payable_type === \App\Models\Shop\ShopOrder::class) {
                $data['order'] = new ShopOrderResource($payable);
            } elseif ($payment->payable_type === \App\Models\MembershipApplication::class) {
                if ($payment->purpose === PaymentPurpose::MEMBERSHIP_UPGRADE) {
                    $user = $payment->user;
                    $newMembership = $user ? $user->currentMembership : null;
                    $data['upgrade'] = [
                        'application_id' => $payable->id,
                        'status'         => $payable->status,
                        'payment_status' => $payable->payment_status,
                        'new_tier_id'    => $payable->selected_tier_id,
                        'membership'     => $newMembership ? [
                            'id'         => $newMembership->id,
                            'tier_id'    => $newMembership->membership_tier_id,
                            'status'     => $newMembership->status,
                            'expires_at' => $newMembership->expires_at ? $newMembership->expires_at->toIso8601String() : null,
                        ] : null,
                    ];
                } else {
                    $data['membership_application'] = [
                        'id'             => $payable->id,
                        'status'         => $payable->status,
                        'payment_status' => $payable->payment_status,
                    ];
                }
            } elseif ($payment->payable_type === \App\Models\VaultRemovalRequest::class) {
                $data['vault_request'] = [
                    'id'              => $payable->id,
                    'status'          => $payable->status,
                    'payment_status'  => $payable->payment_status,
                    'delivery_fee'    => (float) $payable->delivery_fee,
                    'tracking_number' => $payable->tracking_number,
                ];
            }
        }

        return $this->success($data, $message);
    }

    /**
     * Build standard verification failure response.
     */
    protected function buildFailureResponse(Payment $payment, string $message): JsonResponse
    {
        return $this->error($message, 422, [
            'payment' => ['Payment verification failed or not completed.']
        ]);
    }
}
