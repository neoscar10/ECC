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
                        <h6 class="mb-0 fs-14 fw-bold">{{ count($combinations) }}</h6>
                        <span class="text-muted fs-11 text-uppercase">SKUs</span>
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
                                        @foreach(collect($existingImages)->take(6) as $img)
                                             <img src="{{ Storage::url($img->image_path) }}" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                        @endforeach
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

                {{-- 3) Combinations & Inventory (HIGHER PRIORITY) --}}
                <div class="card shadow-none border mb-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Combination Matrix</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" wire:click="goToStep(5)">Edit Combinations</button>
                    </div>
                    <div class="card-body p-0">
                        @if(empty($combinations))
                            <div class="p-4 text-center">
                                <h6 class="fs-14 fw-medium">Simple Product</h6>
                                <p class="text-muted fs-13 mb-0">No combinations configured.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-borderless table-nowrap mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Combination</th>
                                            <th>SKU</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Default</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($combinations as $key => $combo)
                                            <tr class="border-bottom">
                                                <td class="fw-medium">
                                                    @foreach($combo['labels'] as $label)
                                                        <span class="badge bg-light text-dark border me-1">{{ $label }}</span>
                                                    @endforeach
                                                </td>
                                                <td><small class="text-muted">{{ $combo['sku'] ?: '-' }}</small></td>
                                                <td>{{ $reviewData['basic']['currency'] }} {{ number_format($combo['price'], 2) }}</td>
                                                <td>
                                                    @if($combo['stock'] > 0)
                                                        <span class="badge bg-success-subtle text-success">{{ $combo['stock'] }} units</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Out of Stock</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($combo['is_default'])
                                                        <i class="ri-check-line text-success fw-bold"></i>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 4) Structure (Variation Groups) --}}
                <div class="card shadow-none border mb-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Variation Structure</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" wire:click="goToStep(4)">Edit Variation Groups</button>
                    </div>
                    <div class="card-body">
                         @if(empty($reviewData['variations']))
                            <p class="text-muted fs-13 mb-0">Simple product structure.</p>
                        @else
                            <div class="d-flex flex-wrap gap-4">
                                @foreach($reviewData['variations'] as $grp)
                                    <div>
                                        <label class="text-muted text-uppercase fs-11 mb-1 fw-bold">{{ $grp['name'] }}</label>
                                        <div class="fs-13">
                                            {{ $grp['values_count'] }} values ({{ ucfirst($grp['type']) }})
                                            @if($grp['has_images']) <i class="ri-image-2-line text-primary ms-1" title="Gallery Control"></i> @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="text-center py-2">
                    <p class="text-muted fs-13 mb-0">Ready to publish? Click "{{ $isEditMode ? 'Update Product' : 'Create Product' }}" below.</p>
                </div>

            </div>
        </div>
    </div>
</div>
