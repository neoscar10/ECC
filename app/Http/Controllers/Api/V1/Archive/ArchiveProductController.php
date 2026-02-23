<?php

namespace App\Http\Controllers\Api\V1\Archive;

use App\Http\Controllers\Controller;
use App\Http\Resources\Archive\ArchiveProductResource;
use App\Http\Resources\Archive\ArchiveProductListResource;
use App\Models\Archive\ArchiveProduct;
use App\Services\Archive\ArchiveProductService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ArchiveProductController extends Controller
{
    use ApiResponse;

    protected ArchiveProductService $archiveProductService;

    public function __construct(ArchiveProductService $archiveProductService)
    {
        $this->archiveProductService = $archiveProductService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $userTier = $user ? $user->currentMembership?->membershipTier : null;

        if ($request->has('category_id') && !is_numeric($request->category_id)) {
            return $this->error('Invalid category_id. Must be numeric.', 422);
        }

        $products = $this->archiveProductService->getProducts(
            $user, 
            $userTier, 
            $request->only(['category_id']),
            20
        );

        return $this->success(ArchiveProductListResource::collection($products));
    }

    public function show($id)
    {
        $user = request()->user();
        $userTier = $user ? $user->currentMembership?->membershipTier : null;

        $product = ArchiveProduct::with([
            'category', 
            'images', 
            'restrictedMinTier', 
            'restrictedPrivateTier',
            'attachments',
            'earlyAccessWindows',
            'clearViewTiers', // Fix: Load pivots so Resolver works
            'visibilityTiers'
        ])
        ->where('is_active', true)
        ->findOrFail($id);

        // Access Check for Blocked Items
        // The visibleTo scope handles listing, but for direct ID access we must verify opacity.
        $resolver = app(\App\Services\Archive\ArchiveAccessResolver::class);
        $access = $resolver->resolveProductAccess($product, $user, $userTier);
        
        if ($access['view_mode'] === 'blocked') {
            // Return 403 with the access object so frontend knows why
             return response()->json([
                'status' => 'error',
                'message' => 'Access Denied',
                'code' => 403,
                'data' => [
                    'access' => $access
                ]
            ], 403);
        }

        return $this->success(new ArchiveProductResource($product));
    }
}
