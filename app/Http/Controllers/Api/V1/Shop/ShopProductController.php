<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\ShopProductDetailResource;
use App\Http\Resources\Shop\ShopProductResource;
use App\Models\Shop\ShopCategory;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopTagGroup;
use App\Services\Shop\ShopProductService;
use App\Support\ApiResponse;
use App\Validation\Shop\ShopRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class ShopProductController extends Controller
{
    use ApiResponse;

    protected ShopProductService $shopProductService;

    public function __construct(ShopProductService $shopProductService)
    {
        $this->shopProductService = $shopProductService;
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(ShopRules::listing());

        $filters = $request->only(['q', 'category_ids', 'tags', 'price_min', 'price_max', 'sort', 'in_stock']);
        
        $products = $this->shopProductService->getProducts(
            $filters,
            $request->input('per_page', 20)
        );

        $products->getCollection()->loadMissing(['variationGroups.values', 'categories', 'tags']);

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
                'filters_applied' => $filters
            ]
        );
    }

    public function show($id): JsonResponse
    {
        $product = ShopProduct::with([
            'images', 
            'categories.parent', 
            'tags.group', 
            'variationGroups.values.images', // Eager load value images
            'sizeGuide'
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
        $validation = \Illuminate\Support\Facades\Validator::make($request->all(), ShopRules::suggestions());

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
