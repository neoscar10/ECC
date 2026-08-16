<?php

namespace App\Http\Controllers\Api\V1\Vault;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaultController extends Controller
{
    /**
     * Get vault summary (access status + counts).
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        $membership = $user->currentMembership;
        $userTier = $membership ? $membership->membershipTier : null;
        
        $resolver = app(\App\Services\Vault\VaultAccessResolver::class);
        $access = $resolver->resolveVaultAccess($user, $userTier);

        if (!$user->has_vault_access) {
            return response()->json([
                'success' => true,
                'message' => 'Vault access is restricted for your membership tier.',
                'data' => [
                    'access' => $access,
                    'can_access_vault' => false,
                    'counts' => [
                        'locked' => 0,
                        'removed' => 0,
                        'total' => 0
                    ]
                ],
                'meta' => [
                    'code' => 'VAULT_ACCESS_RESTRICTED'
                ]
            ], 200);
        }

        // 200 Payload
        $lockedCount = $user->vaultItems()->locked()->count();
        $removedCount = $user->vaultItems()->removed()->count();
        $pendingRequestsCount = $user->vaultItems()->whereHas('removalRequests', function($q) {
            $q->where('status', 'pending');
        })->count();

        return response()->json([
            'success' => true,
            'message' => 'Vault summary retrieved',
            'data' => [
                'access' => $access,
                'can_access_vault' => true,
                'counts' => [
                    'locked' => $lockedCount,
                    'removed' => $removedCount,
                    'pending_removal_requests' => $pendingRequestsCount,
                    'total' => $lockedCount + $removedCount
                ],
                'membership' => [
                    'tier' => [
                        'id' => $userTier->id,
                        'code' => $userTier->code,
                        'name' => $userTier->name,
                        'level' => $userTier->level,
                        'has_vault_access' => true
                    ]
                ]
            ],
            'meta' => [],
            'errors' => []
        ]);
    }

    public function requestRemoval(Request $request, $id, \App\Services\VaultService $service)
    {
        $user = $request->user();

        if (!$user->has_vault_access) {
            return $this->summary($request);
        }

        $item = $user->vaultItems()->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Vault item not found.',
                'errors' => ['id' => ['Item not found or does not belong to user.']]
            ], 404);
        }

        $request->validate([
            'message' => 'nullable|string|max:1000',
            'address_id' => 'required_without:address|nullable|integer',
            'address' => 'required_without:address_id|nullable|array',
            'address.full_name' => 'required_with:address|string|max:255',
            'address.phone' => 'required_with:address|string|max:20',
            'address.line1' => 'required_with:address|string|max:255',
            'address.city' => 'required_with:address|string|max:100',
            'address.state' => 'required_with:address|string|max:100',
            'address.postal_code' => 'required_with:address|string|max:20',
            'address.country' => 'nullable|string|max:100',
            'address.label' => 'nullable|string|max:50',
            'address.is_default' => 'nullable|boolean',
        ]);

        try {
            // Calculate Delivery Quote for API flow
            $quoteData = null;
            $quoteService = app(\App\Services\Shipping\VaultDeliveryQuoteService::class);
            $quoteResult = null;
            
            if ($request->input('address_id')) {
                $addressForQuote = $user->addresses()->find($request->input('address_id'));
                if ($addressForQuote) {
                    $quoteResult = $quoteService->quoteForVaultItem($item, $addressForQuote, $user);
                }
            } elseif ($request->input('address') && isset($request->input('address')['postal_code'])) {
                $postalCodeForQuote = $request->input('address')['postal_code'];
                $quoteResult = $quoteService->quoteForVaultItemAndPincode($item, $postalCodeForQuote, $user);
            }

            if ($quoteResult && ($quoteResult['success'] ?? false)) {
                $fee = (float) $quoteResult['delivery_fee'];
                $quoteData = [
                    'delivery_fee' => $fee,
                    'delivery_currency' => $quoteResult['currency'] ?? 'INR',
                    'shipping_rate_quote_id' => $quoteResult['rate_quote_id'] ?? null,
                    'selected_courier_company_id' => $quoteResult['selected_courier']['courier_company_id'] ?? null,
                    'selected_courier_name' => $quoteResult['selected_courier']['courier_name'] ?? null,
                    'package_weight_kg' => $quoteResult['measurement']['weight_kg'] ?? null,
                    'package_length_cm' => $quoteResult['measurement']['length_cm'] ?? null,
                    'package_breadth_cm' => $quoteResult['measurement']['breadth_cm'] ?? null,
                    'package_height_cm' => $quoteResult['measurement']['height_cm'] ?? null,
                    'payment_status' => $fee > 0 ? \App\Models\VaultRemovalRequest::PAYMENT_PENDING : \App\Models\VaultRemovalRequest::PAYMENT_NONE,
                ];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $quoteResult['message'] ?? 'Delivery is not available for this address.',
                    'errors' => ['shipping' => [$quoteResult['message'] ?? 'Delivery is not available for this address.']]
                ], 422);
            }

            $removalRequest = $service->requestRemoval(
                $item, 
                $user, 
                $request->input('message'),
                $request->input('address_id'),
                $request->input('address'),
                $quoteData
            );

            return response()->json([
                'success' => true,
                'message' => 'Physical delivery request submitted successfully.',
                'data' => [
                    'request_id' => $removalRequest->id,
                    'status' => $removalRequest->status,
                    'payment_status' => $removalRequest->payment_status,
                    'delivery_fee' => $removalRequest->delivery_fee ? (float) $removalRequest->delivery_fee : null,
                    'delivery_currency' => $removalRequest->delivery_currency ?? 'INR',
                    'delivery_address' => [
                        'name' => $removalRequest->delivery_name,
                        'phone' => $removalRequest->delivery_phone,
                        'line1' => $removalRequest->delivery_line1,
                        'city' => $removalRequest->delivery_city,
                        'state' => $removalRequest->delivery_state,
                        'postal_code' => $removalRequest->delivery_postal_code,
                        'country' => $removalRequest->delivery_country,
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['logic' => [$e->getMessage()]]
            ], 422);
        }
    }

    public function requestMultiRemoval(Request $request, \App\Services\VaultService $service)
    {
        $user = $request->user();

        if (!$user->has_vault_access) {
            return $this->summary($request);
        }

        $request->validate([
            'vault_item_ids' => 'required|array|min:1',
            'vault_item_ids.*' => 'integer',
            'message' => 'nullable|string|max:1000',
            'address_id' => 'required_without:address|nullable|integer',
            'address' => 'required_without:address_id|nullable|array',
            'address.full_name' => 'required_with:address|string|max:255',
            'address.phone' => 'required_with:address|string|max:20',
            'address.line1' => 'required_with:address|string|max:255',
            'address.city' => 'required_with:address|string|max:100',
            'address.state' => 'required_with:address|string|max:100',
            'address.postal_code' => 'required_with:address|string|max:20',
            'address.country' => 'nullable|string|max:100',
            'address.label' => 'nullable|string|max:50',
            'address.is_default' => 'nullable|boolean',
        ]);

        $items = $user->vaultItems()->whereIn('id', $request->input('vault_item_ids'))->get();

        if ($items->isEmpty() || $items->count() !== count(array_unique($request->input('vault_item_ids')))) {
            return response()->json([
                'success' => false,
                'message' => 'One or more vault items not found.',
                'errors' => ['vault_item_ids' => ['Items not found or do not belong to user.']]
            ], 404);
        }

        try {
            // Calculate Delivery Quote for API flow
            $quoteData = null;
            $quoteService = app(\App\Services\Shipping\VaultDeliveryQuoteService::class);
            $quoteResult = null;
            
            if ($request->input('address_id')) {
                $addressForQuote = $user->addresses()->find($request->input('address_id'));
                if ($addressForQuote) {
                    $quoteResult = $quoteService->quoteForVaultItems($items, $addressForQuote, $user);
                }
            } elseif ($request->input('address') && isset($request->input('address')['postal_code'])) {
                $postalCodeForQuote = $request->input('address')['postal_code'];
                $quoteResult = $quoteService->quoteForVaultItemsAndPincode($items, $postalCodeForQuote, $user);
            }

            if ($quoteResult && ($quoteResult['success'] ?? false)) {
                $fee = (float) $quoteResult['delivery_fee'];
                $quoteData = [
                    'delivery_fee' => $fee,
                    'delivery_currency' => $quoteResult['currency'] ?? 'INR',
                    'shipping_rate_quote_id' => $quoteResult['rate_quote_id'] ?? null,
                    'selected_courier_company_id' => $quoteResult['selected_courier']['courier_company_id'] ?? null,
                    'selected_courier_name' => $quoteResult['selected_courier']['courier_name'] ?? null,
                    'package_weight_kg' => $quoteResult['measurement']['weight_kg'] ?? null,
                    'package_length_cm' => $quoteResult['measurement']['length_cm'] ?? null,
                    'package_breadth_cm' => $quoteResult['measurement']['breadth_cm'] ?? null,
                    'package_height_cm' => $quoteResult['measurement']['height_cm'] ?? null,
                    'payment_status' => $fee > 0 ? \App\Models\VaultRemovalRequest::PAYMENT_PENDING : \App\Models\VaultRemovalRequest::PAYMENT_NONE,
                ];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $quoteResult['message'] ?? 'Delivery is not available for this address.',
                    'errors' => ['shipping' => [$quoteResult['message'] ?? 'Delivery is not available for this address.']]
                ], 422);
            }

            $removalRequest = $service->requestRemoval(
                $items, 
                $user, 
                $request->input('message'),
                $request->input('address_id'),
                $request->input('address'),
                $quoteData
            );

            return response()->json([
                'success' => true,
                'message' => 'Physical delivery request submitted successfully.',
                'data' => [
                    'request_id' => $removalRequest->id,
                    'status' => $removalRequest->status,
                    'payment_status' => $removalRequest->payment_status,
                    'delivery_fee' => $removalRequest->delivery_fee ? (float) $removalRequest->delivery_fee : null,
                    'delivery_currency' => $removalRequest->delivery_currency ?? 'INR',
                    'delivery_address' => [
                        'name' => $removalRequest->delivery_name,
                        'phone' => $removalRequest->delivery_phone,
                        'line1' => $removalRequest->delivery_line1,
                        'city' => $removalRequest->delivery_city,
                        'state' => $removalRequest->delivery_state,
                        'postal_code' => $removalRequest->delivery_postal_code,
                        'country' => $removalRequest->delivery_country,
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['logic' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Get the authenticated user's vault items.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->has_vault_access) {
            return $this->summary($request); // Reuse 403 logic
        }

        $query = $user->vaultItems()->with(['removalRequests.shippingShipment.events']);

        // Filters
        // Status: locked (default), removed, all
        $status = $request->input('status', 'locked');
        if ($status !== 'all') {
             $query->where('status', $status);
        }

        // Source Type: auction, archive_product, all
        $sourceType = $request->input('source_type', 'all');
        if ($sourceType !== 'all') {
            if ($sourceType === 'auction') {
                $query->whereIn('source_type', ['auction', 'auction_lot']);
            } else {
                $query->where('source_type', $sourceType);
            }
        }

        // Search
        if ($q = $request->input('q')) {
            $query->where(function($b) use ($q) {
                $b->where('item_title', 'like', "%{$q}%")
                  ->orWhere('item_ref', 'like', "%{$q}%");
            });
        }

        // Sort
        $sort = $request->input('sort', 'locked_at');
        // Whitelist sort columns
        if (!in_array($sort, ['locked_at', 'removed_at', 'item_title'])) {
            $sort = 'locked_at';
        }
        $direction = $request->input('direction', 'desc');
        
        $items = $query->orderBy($sort, $direction)
            ->paginate($request->input('per_page', 20));

        return \App\Http\Resources\Vault\VaultItemResource::collection($items)->additional([
            'meta' => [
                'filters' => [
                    'status' => $status,
                    'source_type' => $sourceType,
                    'q' => $request->input('q'),
                    'sort' => $sort,
                    'direction' => $direction
                ]
            ]
        ]);
    }

    /**
     * Get details of a single vault item.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->has_vault_access) {
            return $this->summary($request);
        }

        $item = $user->vaultItems()->with(['removalRequests.shippingShipment.events'])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Vault item not found.',
                'errors' => ['id' => ['Item not found or does not belong to user.']]
            ], 404);
        }

        return new \App\Http\Resources\Vault\VaultItemResource($item);
    }

    /**
     * Get a delivery quote for a vault item.
     */
    public function deliveryQuote(Request $request, $id, \App\Services\Shipping\VaultDeliveryQuoteService $quoteService)
    {
        $user = $request->user();

        if (!$user->has_vault_access) {
            return $this->summary($request);
        }

        $item = $user->vaultItems()->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Vault item not found.',
                'errors' => ['id' => ['Item not found or does not belong to user.']]
            ], 404);
        }

        $request->validate([
            'address_id' => 'required_without:postal_code|nullable|integer',
            'postal_code' => 'required_without:address_id|nullable|string',
        ]);

        $addressId = $request->input('address_id');
        $postalCode = $request->input('postal_code');

        try {
            if ($addressId) {
                $address = $user->addresses()->find($addressId);
                if (!$address) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Address not found or does not belong to you.',
                        'errors' => ['address_id' => ['Address not found or does not belong to you.']]
                    ], 404);
                }
                $result = $quoteService->quoteForVaultItem($item, $address, $user);
            } else {
                $result = $quoteService->quoteForVaultItemAndPincode($item, $postalCode, $user);
            }

            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Delivery quote retrieved successfully.',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Delivery is not available for this address.',
                    'errors' => ['shipping' => [$result['message'] ?? 'Delivery is not available for this address.']]
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to calculate delivery quote.',
                'errors' => ['exception' => [$e->getMessage()]]
            ], 500);
        }
    }

    public function deliveryMultiQuote(Request $request, \App\Services\Shipping\VaultDeliveryQuoteService $quoteService)
    {
        $user = $request->user();

        if (!$user->has_vault_access) {
            return $this->summary($request);
        }

        $request->validate([
            'vault_item_ids' => 'required|array|min:1',
            'vault_item_ids.*' => 'integer',
            'address_id' => 'required_without:postal_code|nullable|integer',
            'postal_code' => 'required_without:address_id|nullable|string',
        ]);

        $items = $user->vaultItems()->whereIn('id', $request->input('vault_item_ids'))->get();

        if ($items->isEmpty() || $items->count() !== count(array_unique($request->input('vault_item_ids')))) {
            return response()->json([
                'success' => false,
                'message' => 'One or more vault items not found.',
                'errors' => ['vault_item_ids' => ['Items not found or do not belong to user.']]
            ], 404);
        }

        $addressId = $request->input('address_id');
        $postalCode = $request->input('postal_code');

        try {
            if ($addressId) {
                $address = $user->addresses()->find($addressId);
                if (!$address) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Address not found or does not belong to you.',
                        'errors' => ['address_id' => ['Address not found or does not belong to you.']]
                    ], 404);
                }
                $result = $quoteService->quoteForVaultItems($items, $address, $user);
            } else {
                $result = $quoteService->quoteForVaultItemsAndPincode($items, $postalCode, $user);
            }

            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Delivery quote retrieved successfully.',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Delivery is not available for this address.',
                    'errors' => ['shipping' => [$result['message'] ?? 'Delivery is not available for this address.']]
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to calculate delivery quote.',
                'errors' => ['exception' => [$e->getMessage()]]
            ], 500);
        }
    }
}

