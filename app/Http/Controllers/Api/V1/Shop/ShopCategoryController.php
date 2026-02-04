<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\ShopCategory;
use App\Http\Resources\Shop\ShopCategoryResource;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShopCategoryController extends Controller
{
    use ApiResponse;

    /**
     * List categories.
     * By default returns root categories.
     * Use ?parent_id={id} to get children of a specific folder.
     * Use ?q={term} to search within that scope.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShopCategory::active();

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->roots();
        }

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        // Include simple Children Count for UI
        $query->withCount('children');

        // Sorting
        $query->orderBy('sort_order')->orderBy('name');

        // Pagination
        $perPage = $request->input('per_page', 20);
        $categories = $query->paginate($perPage);

        return $this->success(
            ShopCategoryResource::collection($categories),
            'Categories retrieved successfully.',
            200,
            [
                'pagination' => [
                    'page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'last_page' => $categories->lastPage(),
                ]
            ]
        );
    }

    /**
     * Get a lightweight tree structure.
     * Useful for drawer navigation or menus.
     * ?depth=1..5
     */
    public function tree(Request $request): JsonResponse
    {
        $depth = max(1, min((int) $request->input('depth', 3), 5));

        // Eager load children recursively up to depth
        $with = [];
        $current = 'children';
        for ($i = 0; $i < $depth; $i++) {
            $with[] = $current; // children
            $current .= '.children'; // children.children
        }

        $roots = ShopCategory::active()->roots()
            ->with($with)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success(
            ShopCategoryResource::collection($roots),
            'Category tree retrieved successfully.'
        );
    }

    /**
     * Get details of a specific category.
     */
    public function show($id): JsonResponse
    {
        $category = ShopCategory::active()->withCount('children')->findOrFail($id);

        return $this->success(
            new ShopCategoryResource($category),
            'Category retrieved successfully.'
        );
    }

    /**
     * Explicitly get children of a category.
     * Same as index?parent_id={id} but semantic.
     */
    public function children(Request $request, $id): JsonResponse
    {
        // Ensure parent exists and is active
        $parent = ShopCategory::active()->findOrFail($id);

        $query = $parent->children()->active(); // Using relationship

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $query->withCount('children')->orderBy('sort_order')->orderBy('name');

        $perPage = $request->input('per_page', 20);
        $children = $query->paginate($perPage);

        return $this->success(
            ShopCategoryResource::collection($children),
            'Children retrieved successfully.',
            200,
            [
                'pagination' => [
                    'page' => $children->currentPage(),
                    'per_page' => $children->perPage(),
                    'total' => $children->total(),
                    'last_page' => $children->lastPage(),
                ]
            ]
        );
    }
}
