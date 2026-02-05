<div class="review-dashboard">
    {{-- A) Top Header Strip --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border border-dashed">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                         @if($reviewData['media']['primary_url'])
                            <img src="{{ $reviewData['media']['primary_url'] }}" class="img-fluid rounded border" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <span class="avatar-title bg-soft-primary text-primary rounded fs-2">
                                 <i class="ri-shopping-bag-3-line"></i>
                            </span>
                        @endif
                    </div>
                    <div>
                        <h5 class="fs-15 mb-1 fw-bold">{{ $reviewData['basic']['title'] ?: 'Untitled Product' }}</h5>
                        <div class="d-flex gap-2">
                            <span class="badge {{ $reviewData['basic']['is_active'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                {{ $reviewData['basic']['is_active'] ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="text-muted fs-12">Mode: {{ $isEditMode ? 'Edit' : 'Create' }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-none d-md-flex align-items-center gap-4 text-center">
                    <div>
                        <h6 class="mb-0 fs-14 fw-bold">{{ $reviewData['media']['count'] }}</h6>
                        <span class="text-muted fs-11 text-uppercase">Images</span>
                    </div>
                    <div class="vr" style="height: 24px;"></div>
                    <div>
                        <h6 class="mb-0 fs-14 fw-bold">{{ count($reviewData['categories']) }}</h6>
                        <span class="text-muted fs-11 text-uppercase">Cats</span>
                    </div>
                    <div class="vr" style="height: 24px;"></div>
                    <div>
                        <h6 class="mb-0 fs-14 fw-bold">{{ count($reviewData['tags']) }}</h6>
                        <span class="text-muted fs-11 text-uppercase">Tags</span>
                    </div>
                    <div class="vr" style="height: 24px;"></div>
                    <div>
                        <h6 class="mb-0 fs-14 fw-bold">{{ count($reviewData['variations']) }}</h6>
                        <span class="text-muted fs-11 text-uppercase">Vars</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Column 1: Main Content --}}
        <div class="col-12">
            <div class="vstack gap-4">
            
                {{-- 1) Basic Info --}}
                <div class="card shadow-none border mb-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Basic Info</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" wire:click="goToStep(1)">Edit</button>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                             <div>
                                 <label class="text-muted text-uppercase fs-11 mb-1">Product Title</label>
                                 <h6 class="fs-14 fw-semibold mb-0">{{ $reviewData['basic']['title'] ?: '-' }}</h6>
                             </div>
                             <div class="text-end">
                                 <label class="text-muted text-uppercase fs-11 mb-1">Base Price</label>
                                 <h6 class="fs-14 fw-semibold mb-0">{{ $reviewData['basic']['currency'] }} {{ number_format($reviewData['basic']['price'], 2) }}</h6>
                             </div>
                        </div>
                        @if($reviewData['basic']['description'])
                            <div class="mt-3 bg-light p-3 rounded">
                                <label class="text-muted text-uppercase fs-11 mb-1">Description Snippet</label>
                                <p class="mb-0 text-muted fs-13">{{ Str::limit(strip_tags($reviewData['basic']['description']), 150) }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 2) Media Gallery --}}
                <div class="card shadow-none border mb-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Media Gallery</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" wire:click="goToStep(2)">Edit</button>
                    </div>
                    <div class="card-body">
                        @if($reviewData['media']['count'] > 0)
                            <div class="d-flex gap-4">
                                @if($reviewData['media']['primary_url'])
                                    <div class="flex-shrink-0">
                                        <img src="{{ $reviewData['media']['primary_url'] }}" class="rounded shadow-sm border" style="width: 120px; height: 120px; object-fit: cover;">
                                        <div class="text-center mt-2">
                                            <span class="badge bg-success-subtle text-success fs-10">Primary</span>
                                        </div>
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap gap-2">
                                        {{-- Show first 6 existing --}}
                                        @foreach(collect($existingImages)->take(6) as $img)
                                             <img src="{{ Storage::url($img->image_path) }}" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                        @endforeach
                                        {{-- Show first 6 new (if space) --}}
                                        @foreach(collect($newImages)->take(6 - count($existingImages)) as $img)
                                             <img src="{{ $img->temporaryUrl() }}" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                        @endforeach
                                        
                                        @if($reviewData['media']['count'] > 6)
                                            <div class="d-flex align-items-center justify-content-center bg-light rounded border text-muted fw-medium fs-12" style="width: 50px; height: 50px;">
                                                +{{ $reviewData['media']['count'] - 6 }}
                                            </div>
                                        @endif
                                    </div>
                                    <p class="mt-3 text-muted fs-13 mb-0">{{ $reviewData['media']['count'] }} images configured for the product gallery.</p>
                                </div>
                            </div>
                        @else
                             <div class="text-center py-4 bg-light rounded text-muted">
                                 <i class="ri-image-line fs-24 d-block mb-2"></i>
                                 No images uploaded yet.
                             </div>
                        @endif
                    </div>
                </div>

                {{-- 3) Categories --}}
                <div class="card shadow-none border mb-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Categories</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" wire:click="goToStep(3)">Edit</button>
                    </div>
                    <div class="card-body">
                        @if(count($reviewData['categories']) > 0)
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($reviewData['categories'] as $cat)
                                    <div class="badge badge-soft-info p-2 text-start fw-normal text-dark border border-info-subtle">
                                        <span class="fs-13 fw-medium d-block">{{ $cat['name'] }}</span>
                                        <span class="d-block text-muted fs-10 mt-1">{{ $cat['path'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted fst-italic mb-0">No categories selected.</p>
                        @endif
                    </div>
                </div>

                {{-- 4) Tags --}}
                <div class="card shadow-none border mb-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Tags</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" wire:click="goToStep(3)">Edit</button>
                    </div>
                    <div class="card-body">
                         @if(count($reviewData['tags']) > 0)
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($reviewData['tags'] as $tag)
                                    <div class="d-flex flex-column bg-light px-3 py-2 rounded border">
                                        <span class="text-uppercase text-muted fs-10 mb-1 fw-bold">{{ $tag['group'] }}</span>
                                        <span class="fs-13 fw-medium text-dark">{{ $tag['value'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted fst-italic mb-0">No tags selected.</p>
                        @endif
                    </div>
                </div>

                {{-- 5) Variations & Inventory --}}
                <div class="card shadow-none border mb-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Variations & Inventory</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" wire:click="goToStep(4)">Edit</button>
                    </div>
                    <div class="card-body p-0">
                        @if(empty($reviewData['variations']))
                            <div class="p-4 text-center">
                                <div class="avatar-sm mx-auto mb-3">
                                    <div class="avatar-title bg-light text-muted rounded-circle fs-20">
                                        <i class="ri-box-3-line"></i>
                                    </div>
                                </div>
                                <h6 class="fs-14 fw-medium">Simple Product</h6>
                                <p class="text-muted fs-13 mb-0">No variations configured. This product acts as a single SKU.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-borderless table-nowrap mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Group Name</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Gallery Ctrl</th>
                                            <th scope="col">Values</th>
                                            <th scope="col" class="text-end">Total Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reviewData['variations'] as $vIndex => $grp)
                                            <tr class="border-bottom">
                                                <td class="fw-medium">
                                                    <a class="text-dark d-block" data-bs-toggle="collapse" href="#reviewVarGroup{{ $loop->index }}" role="button">
                                                        <i class="ri-arrow-down-s-line me-1"></i> {{ $grp['name'] }}
                                                    </a>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">{{ ucfirst($grp['type']) }}</span></td>
                                                <td>
                                                    @if($grp['has_images'])
                                                        <span class="text-primary">Yes</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $grp['values_count'] }}</td>
                                                <td class="text-end fw-bold">{{ $grp['stock_total'] }}</td>
                                            </tr>
                                            <!-- Collapsible Details -->
                                            <tr class="collapse show" id="reviewVarGroup{{ $loop->index }}">
                                                <td colspan="5" class="bg-soft-light p-3">
                                                    <div class="card shadow-none border mb-0">
                                                        <div class="card-body p-0">
                                                            <table class="table table-sm table-nowrap mb-0">
                                                                <thead class="text-muted">
                                                                    <tr>
                                                                        <th>Value</th>
                                                                        <th>Price</th>
                                                                        <th>Stock</th>
                                                                        <th>Default</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($grp['values'] as $val)
                                                                        <tr>
                                                                            <td class="fw-medium">{{ $val['label'] }}</td>
                                                                            <td>
                                                                                <small class="text-muted">{{ $reviewData['basic']['currency'] }}</small> 
                                                                                {{ $val['price'] > 0 ? $val['price'] : 'Base' }}
                                                                            </td>
                                                                            <td>
                                                                                @if($val['stock'] > 0)
                                                                                    {{ $val['stock'] }}
                                                                                @else
                                                                                    <span class="badge bg-danger-subtle text-danger">Out of stock</span>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                @if($val['is_default'])
                                                                                    <i class="ri-check-line text-success fs-16"></i>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Last Row: Warnings, Pricing & CTA --}}
                
                @if(!$reviewData['checklist']['media'] || !$reviewData['checklist']['categories'])
                     <div class="card border border-warning-subtle shadow-none mb-0">
                         <div class="card-body">
                            @if(!$reviewData['checklist']['media'])
                                <div class="d-flex align-items-center text-warning mb-1">
                                    <i class="ri-alert-line me-2"></i>
                                    <div class="fs-13">Product has no images. It's recommended to add at least one image.</div>
                                </div>
                            @endif
                            @if(!$reviewData['checklist']['categories'])
                                <div class="d-flex align-items-center text-danger">
                                    <i class="ri-error-warning-line me-2"></i>
                                    <div class="fs-13">No category selected. Product might be hard to find.</div>
                                </div>
                            @endif
                         </div>
                     </div>
                @endif

                @if(count($reviewData['variations']) > 0)
                    <div class="card bg-info-subtle border-0 mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-start text-info">
                                <i class="ri-information-fill me-2 mt-1"></i>
                                <p class="mb-0 fs-13">
                                    <strong>Pricing Rule:</strong> Final price shown to buyer is determined by the selected variation value (overriding base price if set).
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="text-center py-2">
                    <p class="text-muted fs-13 mb-0">Ready to publish? Click "Create" or "Update" below.</p>
                </div>

            </div>
        </div>
    </div>
</div>
