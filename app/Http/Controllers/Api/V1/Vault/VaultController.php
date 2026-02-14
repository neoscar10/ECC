<?php

namespace App\Http\Controllers\Api\V1\Vault;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaultController extends Controller
{
    /**
     * Get the authenticated user's vault items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Check if user has vault access
        if (!$user->has_vault_access) {
             return response()->json([
                'message' => 'Your current membership tier does not support vault access.',
                'error' => 'forbidden_tier_access'
            ], 403);
        }

        $items = $user->vaultItems()
            ->locked() // Only show currently locked items
            ->latest('locked_at')
            ->get();

        return JsonResource::collection($items);
    }
}
