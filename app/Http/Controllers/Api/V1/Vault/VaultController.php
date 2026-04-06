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

    /**
     * Request removal of a vault item.
     */
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
            $removalRequest = $service->requestRemoval(
                $item, 
                $user, 
                $request->input('message'),
                $request->input('address_id'),
                $request->input('address')
            );

            return response()->json([
                'success' => true,
                'message' => 'Physical delivery request submitted successfully.',
                'data' => [
                    'request_id' => $removalRequest->id,
                    'status' => $removalRequest->status,
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

        $query = $user->vaultItems();

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

        $item = $user->vaultItems()->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Vault item not found.',
                'errors' => ['id' => ['Item not found or does not belong to user.']]
            ], 404);
        }

        return new \App\Http\Resources\Vault\VaultItemResource($item);
    }
}
