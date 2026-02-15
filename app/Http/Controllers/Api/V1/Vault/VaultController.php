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

        return response()->json([
            'success' => true,
            'message' => 'Vault summary retrieved',
            'data' => [
                'access' => $access,
                'can_access_vault' => true,
                'counts' => [
                    'locked' => $lockedCount,
                    'removed' => $removedCount,
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
