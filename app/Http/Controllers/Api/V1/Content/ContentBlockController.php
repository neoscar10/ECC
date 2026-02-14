<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsBlock;
use App\Services\Cms\ContentBlockMobileResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentBlockController extends Controller
{
    protected ContentBlockMobileResolver $resolver;

    public function __construct(ContentBlockMobileResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get available placements.
     * GET /api/v1/content/placements
     */
    public function placements()
    {
        // Return canonical placements from config
        $placements = config('cms.placements', []);
        
        // Format as list of objects for frontend consistency/extensibility
        $formatted = [];
        foreach ($placements as $key => $label) {
            $formatted[] = ['key' => $key, 'label' => $label];
        }

        return response()->json([
            'data' => $formatted
        ]);
    }

    /**
     * Get blocks for a specific placement.
     * GET /api/v1/content/blocks?placement=home
     */
    public function index(Request $request)
    {
        $request->validate([
            'placement' => 'required|string',
            'include_items' => 'boolean',
            'per_block_limit' => 'integer|min:1|max:50',
        ]);

        $placement = $request->query('placement');
        $includeItems = $request->boolean('include_items', true);
        $limit = $request->integer('per_block_limit', 10);
        
        $user = $request->user('api'); // Explicitly check 'api' guard for optional auth

        // 1. Fetch Blocks (Only Visible to User context)
        // Note: scopeVisibleTo requires user tier. For optional auth, we pass null if guest.
        $userTier = $user?->currentMembership?->membershipTier;

        $blocks = CmsBlock::active()
            ->where('placement', $placement)
            ->visibleTo($user, $userTier)
            ->orderBy('sort_order', 'asc')
            ->get();

        // 2. Resolve Each Block
        $resolved = $blocks->map(function ($block) use ($user, $includeItems, $limit) {
            return $this->resolver->resolve($block, $user, $includeItems, $limit);
        });

        return response()->json([
            'success' => true,
            'data' => $resolved,
        ]);
    }

    /**
     * Get a single block detail.
     * GET /api/v1/content/blocks/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user('api');
        $userTier = $user?->currentMembership?->membershipTier;

        $block = CmsBlock::active()
            ->visibleTo($user, $userTier)
            ->findOrFail($id);

        $resolved = $this->resolver->resolve($block, $user, true, 20, true);

        return response()->json([
            'success' => true,
            'data' => $resolved,
        ]);
    }
}
