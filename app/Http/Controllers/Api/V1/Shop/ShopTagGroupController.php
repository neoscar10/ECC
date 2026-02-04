<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\ShopTagGroup;
use App\Http\Resources\Shop\ShopTagGroupResource;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShopTagGroupController extends Controller
{
    use ApiResponse;

    /**
     * List active tag groups.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShopTagGroup::active();

        if ($request->has('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        // Include empty groups? Default false.
        $includeEmpty = $request->input('include_empty', false);
        if (!$includeEmpty) {
            $query->has('tags');
        }

        $query->withCount('tags')->orderBy('sort_order')->orderBy('name');

        $groups = $query->paginate($request->input('per_page', 50));

        return $this->success(
            ShopTagGroupResource::collection($groups),
            'Tag groups retrieved successfully.',
            200,
            [
                'pagination' => [
                    'page' => $groups->currentPage(),
                    'per_page' => $groups->perPage(),
                    'total' => $groups->total(),
                    'last_page' => $groups->lastPage(),
                ]
            ]
        );
    }

    /**
     * Get group details.
     */
    public function show($id): JsonResponse
    {
        $group = ShopTagGroup::active()->withCount('tags')->findOrFail($id);
        
        // Load tags (paginated via separate request usually, but if small, we can load)
        // Contract says: "Group detail: group info + tags (paginated) OR group info + tags_count + has_tags"
        // Let's just return the group info + count here. Client can fetch tags via /shop/tags?group_id=...
        
        return $this->success(
            new ShopTagGroupResource($group),
            'Tag group retrieved successfully.'
        );
    }
}
