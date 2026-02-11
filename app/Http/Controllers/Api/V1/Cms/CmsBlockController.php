<?php

namespace App\Http\Controllers\Api\V1\Cms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\CmsBlockResource;
use App\Models\Cms\CmsBlock;
use Illuminate\Http\Request;

class CmsBlockController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $userTier = $user?->currentMembership?->membershipTier;

        // Use the scope to filter VISIBLE blocks
        // This mirrors ArchiveProduct::scopeVisibleTo
        $blocks = CmsBlock::active()
            ->visibleTo($user, $userTier)
            ->orderBy('sort_order', 'asc')
            ->get();

        return CmsBlockResource::collection($blocks);
    }
}
