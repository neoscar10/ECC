<?php

namespace App\Http\Controllers\Api\V1\Archive;

use App\Http\Controllers\Controller;
use App\Http\Resources\Archive\ArchiveProductResource;
use App\Models\Archive\ArchiveProduct;
use Illuminate\Http\Request;

class ArchiveProductController extends Controller
{
    public function index(Request $request)
    {
        $query = ArchiveProduct::query()
            ->with(['category', 'images', 'restrictedMinTier']) // Eager load common relations
            ->where('is_active', true);

        if ($request->has('category_id')) {
            $query->where('archive_category_id', $request->category_id);
        }

        // Sorting
        $query->orderBy('sort_order')->orderBy('created_at', 'desc');

        $products = $query->paginate(20);

        return $this->success(ArchiveProductResource::collection($products));
    }

    public function show($id)
    {
        $product = ArchiveProduct::with([
            'category', 
            'images', 
            'restrictedMinTier', 
            'restrictedPrivateTier',
            'attachments',
            'earlyAccessWindows'
        ])
        ->where('is_active', true)
        ->findOrFail($id);

        return $this->success(new ArchiveProductResource($product));
    }
}
