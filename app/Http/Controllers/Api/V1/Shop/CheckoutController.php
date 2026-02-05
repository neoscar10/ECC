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
            return ApiResponse::success('Checkout summary generated.', new CheckoutSummaryResource($data));
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:user_addresses,id',
            'billing_address_id' => 'nullable|exists:user_addresses,id',
            'billing_same_as_shipping' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // Additional check: address must belong to user
            $request->user()->addresses()->findOrFail($request->shipping_address_id);
            if ($request->billing_address_id) {
                $request->user()->addresses()->findOrFail($request->billing_address_id);
            }

            $order = $this->checkoutService->placeOrder($request->user(), $request->all());
            
            return ApiResponse::success(
                'Order placed successfully.', 
                new ShopOrderResource($order)
            )->setStatusCode(201);

        } catch (Exception $e) {
            if ($e->getCode() === 409) {
                 return ApiResponse::error($e->getMessage(), 409);
            }
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
