<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\ShopOrderResource;
use App\Models\Shop\ShopOrder;
use App\Services\Shop\CheckoutService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class ShopOrderController extends Controller
{
    protected CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function index(Request $request)
    {
        $orders = ShopOrder::where('user_id', $request->user()->id)
            ->with(['items.variationValues'])
            ->latest('placed_at')
            ->paginate(15);

        return ApiResponse::success('Orders fetched successfully.', ShopOrderResource::collection($orders));
    }

    public function show(Request $request, $id)
    {
        $order = ShopOrder::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with(['items.variationValues'])
            ->firstOrFail();

        return ApiResponse::success('Order details fetched successfully.', new ShopOrderResource($order));
    }

    public function confirmPayment(Request $request, $id)
    {
        $order = ShopOrder::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $order = $this->checkoutService->confirmPayment($order, $request->all());

        return ApiResponse::success('Payment confirmed.', new ShopOrderResource($order));
    }

    public function cancel(Request $request, $id)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);

        $order = ShopOrder::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with(['items.variationValues']) // Needed for stock restoration
            ->firstOrFail();

        try {
            $order = $this->checkoutService->cancelOrder($order, $request->reason);
            return ApiResponse::success('Order cancelled successfully.', new ShopOrderResource($order));
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
