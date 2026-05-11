<div>
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Product Details</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.shop.products.index') }}">Shop</a></li>
                        <li class="breadcrumb-item active">Product Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- Gallery Card --}}
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">{{ $product->title }}</h5>
                        <div>
                            @if($product->is_active)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8 mb-3 mb-lg-0">
                            @php
                                $activeImg = $this->activeImage;
                                $mainPath = null;
                                if ($activeImg) {
                                    $mainPath = preg_replace('#^public/#', '', str_replace('\\','/', $activeImg->path));
                                }
                            @endphp
                            
                            <div class="bg-light rounded d-flex align-items-center justify-content-center border p-2" 
                                 style="height: 420px; overflow: hidden;">
                                @if($mainPath)
                                    <img src="{{ Storage::url($mainPath) }}" 
                                         class="w-100 h-100 object-fit-contain" 
                                         alt="Product Image">
                                @else
                                    <div class="text-center text-muted">
                                        <i class="ri-image-line fs-48"></i>
                                        <p class="mt-2">No Image Selected</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="d-flex flex-row flex-lg-column gap-2 overflow-auto" style="max-height: 420px;">
                                @forelse($product->images->sortBy('sort_order') as $img)
                                    @php 
                                        $thumbPath = preg_replace('#^public/#', '', str_replace('\\','/', $img->path));
                                        $isActive = $activeImageId === $img->id;
                                    @endphp
                                    <div wire:key="thumb-{{ $img->id }}" 
                                         wire:click="selectImage({{ $img->id }})"
                                         class="d-flex align-items-center justify-content-center flex-shrink-0 cursor-pointer border rounded bg-white p-1 {{ $isActive ? 'border-primary border-2 shadow-sm' : 'border-light' }}"
                                         style="width: 80px; height: 80px; transition: all 0.2s;"
                                         role="button">
                                        <img src="{{ Storage::url($thumbPath) }}" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                                    </div>
                                @empty
                                    <div class="text-muted fs-12">No images available.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Description Card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Product Description</h5>
                </div>
                <div class="card-body">
                    <div class="text-muted">
                        {!! $product->description !!}
                    </div>
                </div>
            </div>

            {{-- Variations Table (if applicable) --}}
            @if($product->variationGroups->isNotEmpty())
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Variations & Stock</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-nowrap table-sm align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Variation</th>
                                    <th>Type</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-end">Threshold</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->variationGroups as $group)
                                    @foreach($group->values as $value)
                                        @php $variant = $value->variant; @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($group->presentation_type === 'color')
                                                        <div class="flex-shrink-0 avatar-xs me-2">
                                                            <div class="avatar-title rounded-circle border" style="background-color: {{ $value->color_hex }}"></div>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="fs-13 mb-0">{{ $group->name }}: {{ $value->caption }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ ucfirst($group->presentation_type) }}</td>
                                            <td class="text-center">
                                                @if($variant)
                                                    @if($variant->stock_qty <= $product->low_stock_threshold)
                                                        <span class="badge bg-danger-subtle text-danger">{{ $variant->stock_qty }} units</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success">{{ $variant->stock_qty }} units</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-muted">{{ $product->low_stock_threshold }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" wire:click="requestEdit">
                            <i class="ri-edit-2-line align-middle me-1"></i> Edit Product
                        </button>
                        <a href="{{ route('admin.shop.inventory', ['search' => $product->title]) }}" class="btn btn-soft-info">
                            <i class="ri-history-line align-middle me-1"></i> Manage Inventory
                        </a>
                    </div>
                </div>
            </div>

            {{-- Summary Stats --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">General Info</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Base Price</td>
                                    <td class="px-0 text-end fw-semibold">{{ $product->currency }} {{ number_format($product->base_price, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Categories</td>
                                    <td class="px-0 text-end">
                                        @forelse($product->categories as $cat)
                                            <span class="badge bg-light text-primary">{{ $cat->name }}</span>
                                        @empty
                                            <span class="text-muted">None</span>
                                        @endforelse
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Total Stock</td>
                                    <td class="px-0 text-end">
                                        @if($product->variationGroups->isEmpty())
                                            {{ $product->stock_qty ?? 0 }} units
                                        @else
                                            @php
                                                $totalStock = 0;
                                                foreach($product->variationGroups as $g) {
                                                    foreach($g->values as $v) {
                                                        if($v->variant) $totalStock += $v->variant->stock_qty;
                                                    }
                                                }
                                            @endphp
                                            {{ $totalStock }} units (across variants)
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Created At</td>
                                    <td class="px-0 text-end text-muted fs-12">{{ $product->created_at->format('d M Y, H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Deactivation Reason (if inactive) --}}
            @if(!$product->is_active && $product->deactivation_reason)
            <div class="card border-danger border-dashed">
                <div class="card-header bg-danger-subtle">
                    <h5 class="card-title mb-0 text-danger">Deactivation Reason</h5>
                </div>
                <div class="card-body">
                    <p class="text-danger mb-0">{{ $product->deactivation_reason }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- reusable edit modal --}}
    <livewire:admin.shop.products.index :key="'shop-product-edit-modal-ref'" />
</div>
