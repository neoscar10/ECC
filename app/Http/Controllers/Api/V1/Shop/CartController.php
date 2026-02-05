<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\CartResource;
use App\Services\Shop\CartService;
use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    use ApiResponse;

    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get the current user's cart.
     */
    public function index(Request $request)
    {
        // getCart creates one if not exists, which is standard "Get My Cart" behavior
        $cart = $this->cartService->getCart(Auth::user());
        return $this->success(new CartResource($cart));
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'quantity' => 'required|integer|min:1',
            'variation_value_ids' => 'sometimes|array',
            'variation_value_ids.*' => 'integer|exists:shop_product_variation_values,id',
        ]);

        try {
            $cart = $this->cartService->addItem(
                Auth::user(),
                $validated['product_id'],
                $validated['quantity'],
                $validated['variation_value_ids'] ?? []
            );

            return $this->success(new CartResource($cart), 'Item added to cart');

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Exception $e) {
            // Check for stock specific error code 409
            if ($e->getCode() === 409) {
                return $this->error($e->getMessage(), 409);
            }
            throw $e;
        }
    }

    /**
     * Update a cart item (qty or variations).
     */
    public function updateItem(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'variation_value_ids' => 'sometimes|array',
            'variation_value_ids.*' => 'integer|exists:shop_product_variation_values,id',
        ]);

        try {
            $cart = $this->cartService->updateItem(
                Auth::user(),
                (int)$id,
                $validated['quantity'] ?? null,
                $request->has('variation_value_ids') ? $validated['variation_value_ids'] : null
            );

            return $this->success(new CartResource($cart), 'Cart item updated');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Cart item not found', 404);
        } catch (\InvalidArgumentException $e) {
             return $this->error($e->getMessage(), 422);
        } catch (Exception $e) {
             if ($e->getCode() === 409) {
                return $this->error($e->getMessage(), 409);
             }
             throw $e;
        }
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem($id)
    {
        try {
            $cart = $this->cartService->removeItem(Auth::user(), (int)$id);
            return $this->success(new CartResource($cart), 'Item removed from cart');
        } catch (Exception $e) {
             return $this->error('Failed to remove item', 500); 
             // Or handle ModelNotFound is nice, but remove is idempotent-ish usually? 
             // Service calls delete on query, won't fail if ID mismatch usually unless I force it.
        }
    }

    /**
     * Clear the cart.
     */
    public function clear()
    {
        $cart = $this->cartService->clearCart(Auth::user());
        return $this->success(new CartResource($cart), 'Cart cleared');
    }
}
