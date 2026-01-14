<?php

namespace App\Http\Controllers\Api\V1\Archive;

use App\Http\Controllers\Controller;
use App\Http\Resources\Archive\ArchiveCategoryResource;
use App\Models\Archive\ArchiveCategory;
use App\Services\Archive\ArchiveAccessService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ArchiveCategoryController extends Controller
{
    use ApiResponse;

    protected $accessService;

    public function __construct(ArchiveAccessService $accessService)
    {
        $this->accessService = $accessService;
    }

    /**
     * List accessible categories.
     */
    public function index(Request $request)
    {
        $perPage = min($request->input('per_page', 15), 50);
        $includeLocked = filter_var($request->input('include_locked', false), FILTER_VALIDATE_BOOLEAN);
        $tierId = $this->accessService->resolveUserTierId($request->user());

        $query = ArchiveCategory::query();

        // Standard Filters
        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        // Access Scope
        $this->accessService->applyAccessibleScope($query, $tierId, $includeLocked);

        // Sorting
        $sort = $request->input('sort', 'sort_order');
        if ($sort === 'newest') $query->latest();
        elseif ($sort === 'oldest') $query->oldest();
        elseif ($sort === 'title') $query->orderBy('title');
        else $query->orderBy('sort_order')->orderBy('title');

        $categories = $query->paginate($perPage);

        // Post-process accessibility if locked included
        $categories->getCollection()->transform(function ($category) use ($tierId) {
            $category->is_accessible = $this->accessService->isAccessible($category, $tierId);
            return $category;
        });

        // Use standard response wrapper or just return resource collection
        // Project uses ApiResponse trait, but usually collections are returned directly or wrapped
        // Let's assume Resource::collection for pagination meta
        return ArchiveCategoryResource::collection($categories)
            ->additional([
                'success' => true,
                'message' => 'Categories fetched successfully.'
            ]);
    }

    /**
     * Show single category.
     */
    public function show(Request $request, $slugOrId)
    {
        // Try slug first, then ID
        $category = ArchiveCategory::where('slug', $slugOrId)
            ->orWhere('id', $slugOrId)
            ->first();

        if (!$category || !$category->is_active) {
            return $this->error('Category not found.', 404);
        }

        $tierId = $this->accessService->resolveUserTierId($request->user());
        $isAccessible = $this->accessService->isAccessible($category, $tierId);

        if (!$isAccessible) {
            // Check if we strictly deny or show locked data partial
            // Typically show -> strictly access denied
            return $this->error('You do not have permission to view this category.', 403);
        }

        $resource = new ArchiveCategoryResource($category);
        $resource->setAccessible(true);

        return $this->success($resource, 'Category retrieved.');
    }
}
