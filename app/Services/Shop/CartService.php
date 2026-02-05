<?php

namespace App\Services\Shop;

use App\Models\Shop\Cart;
use App\Models\Shop\CartItem;
use App\Models\Shop\CartItemVariationValue;
use App\Models\Shop\ShopProduct;
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
        } else {
            // Update activity timestamp if we accessed it? 
            // Requirement: "Reading cart in admin must NOT update last_activity_at."
            // But reading for the USER likely ensures it's active. 
            // I'll leave strictly reading (getCart) as passive, but mutations update it.
            // If the user "views" the cart, the controller might convert it to resource without mutating. 
            // However, typical session expiry logic implies activity = any interaction.
            // For now, I will NOT update strictly on get, to distinguish "viewing" from "active shopping" if needed.
            // But usually viewing is activity. I'll stick to mutating methods updating it.
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

            // 3. Compute Signature & Price
            $signature = $this->computeSignature($selectedValues);
            $unitPrice = $this->computePrice($product, $selectedValues);

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
                $this->validateStock($product, $selectedValues, $newQty); // Optimization: avoid re-fetching
                
                $existingItem->update([
                    'quantity' => $newQty,
                    'unit_price' => $unitPrice, // Update price in case it changed in DB
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
            $item = $cart->items()->where('id', $cartItemId)->firstOrFail(); // Ensure item belongs to user's cart

            $product = $item->product; // Lazy load if needed

            // Determine Target State
            $targetQty = $quantity !== null ? $quantity : $item->quantity;
            
            // If variations strictly provided (even empty array means clear/reset? Or means "no change"? 
            // API spec: "PATCH... optional... If variations change, re-validate".
            // If key is present in payload, we update. If null, keep existing? 
            // I'll assume if passed as argument it's intended to be the new set.
            // But controller might pass null if not in request.
            $changingVariations = $variationValueIds !== null;
            
            if ($targetQty < 1) {
                throw new InvalidArgumentException("Quantity must be at least 1.");
            }

            if ($changingVariations) {
                // Full re-resolution based on new IDs
                $selectedValues = $this->resolveVariations($product, $variationValueIds);
                $signature = $this->computeSignature($selectedValues);
                $unitPrice = $this->computePrice($product, $selectedValues);
                
                // Check Merge Conflict (if changing signature to something that already exists)
                // Excluding self
                $mergeTarget = $cart->items()
                    ->where('shop_product_id', $product->id)
                    ->where('selection_signature', $signature)
                    ->where('id', '!=', $item->id)
                    ->first();

                if ($mergeTarget) {
                    // Merge current item INTO mergeTarget
                    $newTotalQty = $mergeTarget->quantity + $targetQty;
                    $this->validateStock($product, $selectedValues, $newTotalQty);
                    
                    $mergeTarget->update([
                        'quantity' => $newTotalQty,
                        'unit_price' => $unitPrice,
                        'updated_at' => now(),
                    ]);
                    
                    // Delete the old item
                    $item->delete();
                } else {
                    // No merge, just update this item
                    $this->validateStock($product, $selectedValues, $targetQty);
                    
                    $item->update([
                        'quantity' => $targetQty,
                        'unit_price' => $unitPrice,
                        'selection_signature' => $signature,
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
                $this->validateStock($product, $item->selectedVariations, $targetQty);
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
            // Find input ID belonging to this group
            // We need to know which group a value belongs to. 
            // Efficient way: Fetch all input values from DB to check their group_id.
            // But we can just iterate the loaded product->variationGroups->values to look for match.
            
            $match = null;
            foreach ($inputIds as $id) {
                // Check if this $id exists in $group->values collection
                $found = $group->values->firstWhere('id', $id);
                if ($found) {
                    if ($match) {
                        throw new InvalidArgumentException("Multiple values selected for group: {$group->name}");
                    }
                    $match = $found;
                }
            }

            if (!$match) {
                // Try Default
                $match = $group->values->firstWhere('is_default', true);
                if (!$match) {
                    // Fallback to first? Or Error?
                    // "If variation_value_ids not provided, auto-select defaults"
                    // If no default set, usually picking first is standard shop behavior, OR required.
                    // Given existing logic, I'll pick first.
                    $match = $group->values->first();
                }
            }

            if ($match) {
                $resolved->push($match);
            }
        }

        return $resolved;
    }

    private function validateStock(ShopProduct $product, Collection $selectedValues, int $quantity)
    {
        // 1. Check Variation Stock
        // "If selected variation value stock is 0, it must be unselectable"
        // "If requested qty > stock => return 409"
        
        // Logic: Checks each selected variation value. If ANY has stock < qty, fail.
        // Usually, a distinct physical item is 1 variation combo => 1 SKU with 1 stock count.
        // But here, stock is on VALUES (e.g. Size M has 10, Color Red has 5).
        // This suggests "Component Stock" model? Or just Simplified model?
        // E.g. T-Shirt (Red [10], Blue [5]) and (S, M, L).
        // If Model is: Value-based stock, then "Red" limits total Red items regardless of size.
        // This is unusual for apparel (SKU is Red-M).
        // BUT, looking at `ShopProductVariationValue` table, it has `stock_qty`.
        // `ShopProduct` does NOT have stock.
        // And there is no "VariationCombination/SKU" table.
        // So yes, stock is tracked per VALUE.
        // So if I buy Red-M, I need 1 Red and 1 M.
        // So Red must have stock >= qty AND M must have stock >= qty.
        // This is the implementation implied by the schema.

        foreach ($selectedValues as $value) {
            if ($value->stock_qty < $quantity) {
                 // Throw specific exception caught by controller
                 throw new Exception("Insufficient stock for {$value->caption}. Available: {$value->stock_qty}", 409);
            }
        }

        // 2. Check Product Stock (if simple product)
        if ($selectedValues->isEmpty()) {
            // Simple product. "validate stock based on product stock".
            // But ShopProduct HAS NO STOCK COLUMN.
            // Thus, assume infinite.
            // PASS.
        }
    }

    private function computeSignature(Collection $selectedValues): string
    {
        // "sorted ids join '-', then sha1" or just raw string
        $ids = $selectedValues->pluck('id')->sort()->values()->all();
        $str = implode('-', $ids);
        // Using hash to keep column length predictable/short if many variations
        return $str === '' ? 'standard' : $str; 
        // Note: I am NOT hashing it here to make debugging easier, unless it gets too long.
        // Table column is string (255).
        // "123-456-789" is short.
        // I will keep it plain text provided it fits. 
        // Plan said "hash/signature".
    }

    private function computePrice(ShopProduct $product, Collection $selectedValues): float
    {
        // "unit_price = max(product.base_price, max(selected_variation_value.price))"
        $maxValPrice = $selectedValues->max('price');
        return max((float)$product->base_price, (float)$maxValPrice);
    }
}
