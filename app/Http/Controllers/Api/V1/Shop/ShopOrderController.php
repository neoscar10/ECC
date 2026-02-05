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
    use ApiResponse;

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

        return $this->success(ShopOrderResource::collection($orders), 'Orders fetched successfully.');
    }

    public function show(Request $request, $id)
    {
        $order = ShopOrder::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with(['items.variationValues'])
            ->firstOrFail();

        return $this->success(new ShopOrderResource($order), 'Order details fetched successfully.');
    }

    public function confirmPayment(Request $request, $id)
    {
        $order = ShopOrder::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $order = $this->checkoutService->confirmPayment($order, $request->all());

        return $this->success(new ShopOrderResource($order), 'Payment confirmed.');
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
            return $this->success(new ShopOrderResource($order), 'Order cancelled successfully.');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
