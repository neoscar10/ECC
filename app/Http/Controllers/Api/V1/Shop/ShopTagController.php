<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\ShopTag;
use App\Http\Resources\Shop\ShopTagResource;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShopTagController extends Controller
{
    use ApiResponse;

    /**
     * List active tags.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShopTag::active()->with('group');

        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->has('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $query->orderBy('sort_order')->orderBy('name');

        $tags = $query->paginate($request->input('per_page', 50));

        return $this->success(
            ShopTagResource::collection($tags),
            'Tags retrieved successfully.',
            200,
            [
                'pagination' => [
                    'page' => $tags->currentPage(),
                    'per_page' => $tags->perPage(),
                    'total' => $tags->total(),
                    'last_page' => $tags->lastPage(),
                ]
            ]
        );
    }

    /**
     * Get tag details.
     */
    public function show($id): JsonResponse
    {
        $tag = ShopTag::active()->with('group')->findOrFail($id);

        return $this->success(
            new ShopTagResource($tag),
            'Tag retrieved successfully.'
        );
    }
}
