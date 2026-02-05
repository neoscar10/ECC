<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\ShopProductDetailResource;
use App\Http\Resources\Shop\ShopProductResource;
use App\Models\Shop\ShopCategory;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopTagGroup;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class ShopProductController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = ShopProduct::query()->active()->with('images', 'categories', 'tags.group');

        // Text Search
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->q . '%')
                  ->orWhere('description', 'like', '%' . $request->q . '%');
            });
        }

        // Category Filter (OR logic)
        if ($request->filled('category_ids')) {
            // Support both ?category_ids=1,2 and ?category_ids[]=1
            $catIds = is_array($request->category_ids) 
                ? $request->category_ids 
                : explode(',', $request->category_ids);
                
            $query->whereHas('categories', function (Builder $q) use ($catIds) {
                $q->whereIn('shop_categories.id', $catIds);
            });
        }

        // Tags Filter (AND across groups)
        // Request Usage: ?tags[brands]=12&tags[model]=33
        if ($request->filled('tags') && is_array($request->tags)) {
            foreach ($request->tags as $groupSlug => $tagId) {
                if (!$tagId) continue;
                // We filter by tag ID. Group Slug validation is implicit if we assume ID is correct.
                // However, strictly speaking, we just need to ensure the product has this specific tag.
                $query->whereHas('tags', function ($q) use ($tagId) {
                    $q->where('shop_tags.id', $tagId);
                });
            }
        }

        // Price Range
        if ($request->filled('price_min')) {
            $query->where('base_price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('base_price', '<=', $request->price_max);
        }
        
        // In Stock Filter
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
            case 'price_low':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_desc':
            case 'price_high':
                $query->orderBy('base_price', 'desc');
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->latest(); // created_at desc
                break;
        }

        $products = $query->paginate($request->input('per_page', 20));

        return $this->success(
            ShopProductResource::collection($products),
            'Products fetched successfully.',
            200,
            [
                'pagination' => [
                    'page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
                'filters_applied' => $request->only(['q', 'category_ids', 'tags', 'price_min', 'price_max', 'sort', 'in_stock'])
            ]
        );
    }

    public function show($id): JsonResponse
    {
        $product = ShopProduct::with([
            'images', 
            'categories.parent', 
            'tags.group', 
            'variationGroups.values.images' // Eager load value images
        ])->active()->findOrFail($id);

        return $this->success(
            new ShopProductDetailResource($product),
            'Product fetched successfully.'
        );
    }

    /**
     * Data to build filter UI.
     */
    public function filters(): JsonResponse
    {
        // 1. Category Tree
        // Recursive load children
        $categories = ShopCategory::active()->roots()
            ->with(['children' => function($q) {
                // Load deep enough roughly
                $q->with('children')->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
            
        // Formatter for category tree
        $formatCats = function($cats) use (&$formatCats) {
            return $cats->map(function($c) use ($formatCats) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'children' => $formatCats($c->children),
                ];
            });
        };

        // 2. Tag Groups
        $tagGroups = ShopTagGroup::with(['tags' => function($q) {
            $q->orderBy('sort_order')->orderBy('name');
        }])->orderBy('sort_order')->get();

        $formattedTagGroups = $tagGroups->map(fn($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'slug' => $g->slug,
            'items' => $g->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name]),
        ]);

        // 3. Price Range
        $minPrice = ShopProduct::active()->min('base_price');
        $maxPrice = ShopProduct::active()->max('base_price');

        return $this->success([
            'currency' => 'INR', // Or dynamic if needed
            'price_range' => [
                'min' => number_format($minPrice ?? 0, 2, '.', ''),
                'max' => number_format($maxPrice ?? 0, 2, '.', ''),
            ],
            'sort_options' => [
                ['key' => 'newest', 'label' => 'Newest'],
                ['key' => 'price_asc', 'label' => 'Price: Low to High'],
                ['key' => 'price_desc', 'label' => 'Price: High to Low'],
                ['key' => 'title_asc', 'label' => 'Name: A-Z'],
                ['key' => 'title_desc', 'label' => 'Name: Z-A'],
            ],
            'categories' => $formatCats($categories),
            'tag_groups' => $formattedTagGroups
        ], 'Filters fetched successfully.');
    }

    /**
     * Search Autocomplete.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validation = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'q' => 'required|string|min:2',
            'limit' => 'integer|min:1|max:20'
        ]);

        if ($validation->fails()) {
            return $this->error('Validation error', 422, $validation->errors()->toArray());
        }

        $query = ShopProduct::query()
            ->active()
            ->with('images') // Needed for thumb
            ->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->q . '%');
            })
            ->limit($request->input('limit', 8))
            ->get();

        $data = $query->map(function ($p) {
             $img = $p->images->first();
             return [
                 'id' => $p->id,
                 'title' => $p->title,
                 'slug' => $p->slug,
                 'primary_image_url' => $img ? url('storage/' . $img->image_path) : null
             ];
        });

        return $this->success($data, 'Suggestions fetched successfully.');
    }
}
