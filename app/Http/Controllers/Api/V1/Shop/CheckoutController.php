<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\CheckoutSummaryResource;
use App\Http\Resources\Shop\ShopOrderResource;
use App\Services\Shop\CheckoutService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class CheckoutController extends Controller
{
    use ApiResponse;

    protected CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function summary(Request $request)
    {
        try {
            $data = $this->checkoutService->getCheckoutSummary(
                $request->user(),
                $request->input('shipping_address_id')
            );
            return $this->success(new CheckoutSummaryResource($data), 'Checkout summary generated.');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:user_addresses,id',
            'billing_address_id' => 'nullable|exists:user_addresses,id',
            'billing_same_as_shipping' => 'boolean',
            'notes' => 'nullable|string|max:500',
            'payment_gateway' => ['nullable', 'string'],
        ]);

        $availabilityService = app(\App\Services\Payments\PaymentGatewayAvailabilityService::class);
        $gatewayName = $availabilityService->validateGateway($request->input('payment_gateway'), 'shop_order');

        try {
            // Additional check: address must belong to user
            $request->user()->addresses()->findOrFail($request->shipping_address_id);
            if ($request->billing_address_id) {
                $request->user()->addresses()->findOrFail($request->billing_address_id);
            }

            $order = $this->checkoutService->placeOrder($request->user(), $request->all());
            
            // Initiate payment dynamically
            $paymentManager = app(\App\Services\Payments\PaymentManager::class);
            $paymentInitiation = $paymentManager->initiatePayment(
                payable: $order,
                amount: $order->total_amount,
                purpose: 'shop_order',
                user: $request->user(),
                gateway: $gatewayName
            );

            $payment = $paymentInitiation['payment'];
            if (is_object($payment)) {
                $payment->checkout = $paymentInitiation['checkout'] ?? null;
            }

            $responseData = (new ShopOrderResource($order))->resolve($request);
            $responseData['payment'] = (new \App\Http\Resources\Payment\PaymentResource($payment))->resolve($request);

            return $this->success(
                $responseData,
                'Order placed successfully.', 
                201
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Resource not found.', 404);
        } catch (Exception $e) {
            if ($e->getCode() === 409) {
                 return $this->error($e->getMessage(), 409);
            }
            return $this->error($e->getMessage(), 500);
        }
    }
}
