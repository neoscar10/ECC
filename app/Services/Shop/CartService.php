<?php

namespace App\Services\Shop;

use App\Models\Shop\Cart;
use App\Models\Shop\CartItem;
use App\Models\Shop\CartItemVariationValue;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariant;
use App\Models\Shop\ShopProductVariationValue;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Exception;

class CartService
{
    /**
     * Get or create the active cart for a user.
     */
    public function getCart(User $user): Cart
    {
        // Try to find an existing active cart (not checked out)
        $cart = Cart::where('user_id', $user->id)
            ->whereNull('checked_out_at') // Active only
            ->latest('updated_at')
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'last_activity_at' => now(),
            ]);
        } 

        return $cart;
    }

    /**
     * Add an item to the cart.
     * 
     * @throws InvalidArgumentException
     */
    public function addItem(User $user, int $productId, int $quantity, ?array $variationValueIds = []): Cart
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException("Quantity must be at least 1.");
        }

        return DB::transaction(function () use ($user, $productId, $quantity, $variationValueIds) {
            $cart = $this->getCart($user);
            $product = ShopProduct::with(['variationGroups.values'])->findOrFail($productId);

            // 1. Resolve Variations (Defaults + Validations)
            $selectedValues = $this->resolveVariations($product, $variationValueIds);

            // 2. Validate Stock
            $this->validateStock($product, $selectedValues, $quantity);

            // 3. Compute Signature, Price & Variant
            $signature = $this->computeSignature($selectedValues);
            $variant = $this->findVariant($product, $selectedValues);
            $unitPrice = $this->computePrice($product, $selectedValues, $variant);

            // 4. Merge or Create
            // Check if item with exact same signature exists in this cart
            $existingItem = $cart->items()
                ->where('shop_product_id', $productId)
                ->where('selection_signature', $signature)
                ->first();

            if ($existingItem) {
                // Merge
                $newQty = $existingItem->quantity + $quantity;
                // Re-validate stock for total qty
                $this->validateStock($product, $selectedValues, $newQty, $variant); 
                
                $existingItem->update([
                    'quantity' => $newQty,
                    'unit_price' => $unitPrice,
                    'shop_product_variant_id' => $variant?->id,
                    'updated_at' => now(),
                ]);
            } else {
                // Create New
                $item = CartItem::create([
                    'cart_id' => $cart->id,
                    'shop_product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'currency' => $product->currency,
                    'selection_signature' => $signature,
                    'shop_product_variant_id' => $variant?->id,
                ]);

                // Attach Pivot
                foreach ($selectedValues as $value) {
                    CartItemVariationValue::create([
                        'cart_item_id' => $item->id,
                        'shop_product_variation_value_id' => $value->id,
                    ]);
                }
            }

            // 5. Update Cart Activity
            $cart->update(['last_activity_at' => now()]);

            return $cart->fresh(['items.product', 'items.selectedVariations']);
        });
    }

    /**
     * Update an existing cart item (quantity or variations).
     */
    public function updateItem(User $user, int $cartItemId, ?int $quantity = null, ?array $variationValueIds = null): Cart
    {
        return DB::transaction(function () use ($user, $cartItemId, $quantity, $variationValueIds) {
            $cart = $this->getCart($user);
            $item = $cart->items()->where('id', $cartItemId)->firstOrFail(); 

            $product = $item->product; 

            $targetQty = $quantity !== null ? $quantity : $item->quantity;
            $changingVariations = $variationValueIds !== null;
            
            if ($targetQty < 1) {
                throw new InvalidArgumentException("Quantity must be at least 1.");
            }

            if ($changingVariations) {
                // Full re-resolution based on new IDs
                $selectedValues = $this->resolveVariations($product, $variationValueIds);
                $signature = $this->computeSignature($selectedValues);
                $variant = $this->findVariant($product, $selectedValues);
                $unitPrice = $this->computePrice($product, $selectedValues, $variant);
                
                // Check Merge Conflict 
                $mergeTarget = $cart->items()
                    ->where('shop_product_id', $product->id)
                    ->where('selection_signature', $signature)
                    ->where('id', '!=', $item->id)
                    ->first();

                if ($mergeTarget) {
                    // Merge current item INTO mergeTarget
                    $newTotalQty = $mergeTarget->quantity + $targetQty;
                    $this->validateStock($product, $selectedValues, $newTotalQty, $variant);
                    
                    $mergeTarget->update([
                        'quantity' => $newTotalQty,
                        'unit_price' => $unitPrice,
                        'shop_product_variant_id' => $variant?->id,
                        'updated_at' => now(),
                    ]);
                    
                    // Delete the old item
                    $item->delete();
                } else {
                    // No merge, just update this item
                    $this->validateStock($product, $selectedValues, $targetQty, $variant);
                    
                    $item->update([
                        'quantity' => $targetQty,
                        'unit_price' => $unitPrice,
                        'selection_signature' => $signature,
                        'shop_product_variant_id' => $variant?->id,
                        'updated_at' => now(),
                    ]);
                    
                    // Sync Pivots
                    $item->variationValuePivots()->delete();
                    foreach ($selectedValues as $value) {
                        CartItemVariationValue::create([
                            'cart_item_id' => $item->id,
                            'shop_product_variation_value_id' => $value->id,
                        ]);
                    }
                }
            } else {
                // Only Quantity Change
                $variant = $item->shop_product_variant_id ? $item->variant : null;
                $this->validateStock($product, $item->variationValues, $targetQty, $variant);
                $item->update(['quantity' => $targetQty, 'updated_at' => now()]);
            }

            $cart->update(['last_activity_at' => now()]);
            return $cart->fresh(['items.product', 'items.selectedVariations']);
        });
    }

    public function removeItem(User $user, int $cartItemId): Cart
    {
        $cart = $this->getCart($user);
        $cart->items()->where('id', $cartItemId)->delete();
        $cart->update(['last_activity_at' => now()]);
        return $cart->fresh();
    }

    public function clearCart(User $user): Cart
    {
        $cart = $this->getCart($user);
        $cart->items()->delete();
        $cart->update(['last_activity_at' => now()]);
        return $cart->fresh();
    }

    // --- Helpers ---

    private function resolveVariations(ShopProduct $product, array $inputIds): Collection
    {
        $product->loadMissing('variationGroups.values');
        
        $groups = $product->variationGroups;
        $resolved = collect();

        foreach ($groups as $group) {
            $match = null;
            foreach ($inputIds as $id) {
                $found = $group->values->firstWhere('id', $id);
                if ($found) {
                    if ($match) {
                        throw new InvalidArgumentException("Multiple values selected for group: {$group->name}");
                    }
                    $match = $found;
                }
            }

            if (!$match) {
                $match = $group->values->firstWhere('is_default', true);
                if (!$match) {
                    $match = $group->values->first();
                }
            }

            if ($match) {
                $resolved->push($match);
            }
        }

        return $resolved;
    }

    private function validateStock(ShopProduct $product, Collection $selectedValues, int $quantity, ?ShopProductVariant $variant = null)
    {
        if ($product->variants()->exists()) {
            if (!$variant && $selectedValues->isNotEmpty()) {
                $variant = $this->findVariant($product, $selectedValues);
            }

            if ($variant) {
                if ($variant->stock_qty < $quantity) {
                    $labels = $variant->optionValues->pluck('caption')->implode(' / ');
                    throw new Exception("Insufficient stock for {$labels}. Requested: {$quantity}, Available: {$variant->stock_qty}", 409);
                }
                return; 
            }
            
            if ($selectedValues->isNotEmpty()) {
                throw new Exception("This combination ({$selectedValues->pluck('caption')->implode(' / ')}) is currently unavailable.", 404);
            }
        }

        if (!$product->variants()->exists()) {
            if ($product->stock_qty < $quantity) {
                 throw new Exception("Insufficient stock for {$product->title}. Available: {$product->stock_qty}", 409);
            }
        }
    }

    private function computeSignature(Collection $selectedValues): string
    {
        $ids = $selectedValues->pluck('id')->sort()->values()->all();
        $str = implode('-', $ids);
        return $str === '' ? 'standard' : $str; 
    }

    private function computePrice(ShopProduct $product, Collection $selectedValues, ?ShopProductVariant $variant = null): float
    {
        if ($product->variants()->exists()) {
             if (!$variant && $selectedValues->isNotEmpty()) {
                 $variant = $this->findVariant($product, $selectedValues);
             }

            if ($variant) {
                return (float)$variant->price;
            }
        }

        $maxValPrice = $selectedValues->max('price');
        return max((float)$product->base_price, (float)$maxValPrice);
    }

    private function findVariant(ShopProduct $product, Collection $selectedValues): ?ShopProductVariant
    {
        if ($selectedValues->isEmpty()) return null;

        $valueIds = $selectedValues->pluck('id')->sort()->values()->all();
        
        return $product->variants()
            ->with('optionValues')
            ->whereHas('optionValues', function($q) use ($valueIds) {
                $q->whereIn('shop_product_variation_values.id', $valueIds);
            }, '=', count($valueIds))
            ->first();
    }
}
