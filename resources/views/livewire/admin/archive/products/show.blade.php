<div>
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Archive Item Details</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.archive.products') }}">Archive</a></li>
                        <li class="breadcrumb-item active">Item Details</li>
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
                            @php
                                $isLive = $product->go_live_now || ($product->go_live_at && now()->gte($product->go_live_at));
                            @endphp
                            @if($isLive)
                                <span class="badge bg-success-subtle text-success">Live</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Scheduled</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8 mb-3 mb-lg-0">
                            <div class="bg-light rounded d-flex align-items-center justify-content-center border p-2" 
                                 style="height: 420px; overflow: hidden;">
                                @if($activeImage)
                                    <img src="{{ Storage::url($activeImage->image_path) }}" 
                                         class="w-100 h-100 object-fit-contain" 
                                         alt="Archive Image">
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
                                        $isActive = $activeImageId === $img->id;
                                    @endphp
                                    <div wire:key="thumb-{{ $img->id }}" 
                                         wire:click="selectImage({{ $img->id }})"
                                         class="d-flex align-items-center justify-content-center flex-shrink-0 cursor-pointer border rounded bg-white p-1 {{ $isActive ? 'border-primary border-2 shadow-sm' : 'border-light' }}"
                                         style="width: 80px; height: 80px; transition: all 0.2s;"
                                         role="button">
                                        <img src="{{ Storage::url($img->image_path) }}" class="img-fluid" style="max-height: 100%; object-fit: contain;">
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
                    <h5 class="card-title mb-0">Historical Description</h5>
                </div>
                <div class="card-body">
                    <div class="text-muted">
                        {!! $product->description !!}
                    </div>
                </div>
            </div>

            {{-- 360 Images --}}
            @if($product->images360->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">360 Degree View Assets</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($product->images360 as $img360)
                            @php $p360 = preg_replace('#^public/#', '', str_replace('\\','/', $img360->image_path)); @endphp
                            <div class="col-md-2">
                                <div class="border rounded p-1">
                                    <img src="{{ Storage::url($p360) }}" class="img-fluid rounded">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 text-muted fs-12">
                        <i class="ri-information-line me-1"></i> These assets are used for the 360 viewer in the member portal.
                    </div>
                </div>
            </div>
            @endif

            {{-- Attachments --}}
            @if($product->attachments->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historical Documents & Attachments</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($product->attachments as $att)
                            <div class="list-group-item d-flex align-items-center px-0">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm">
                                        <div class="avatar-title bg-light text-primary rounded fs-20">
                                            <i class="ri-file-line"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $att->title }}</h6>
                                    <p class="text-muted mb-0 fs-11">{{ strtoupper($att->type) }} File</p>
                                </div>
                                <div class="flex-shrink-0">
                                    @php $attPath = preg_replace('#^public/#', '', str_replace('\\','/', $att->path)); @endphp
                                    <a href="{{ Storage::url($attPath) }}" target="_blank" class="btn btn-icon btn-ghost-primary btn-sm">
                                        <i class="ri-download-line"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
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
                            <i class="ri-edit-2-line align-middle me-1"></i> Edit Item
                        </button>
                        <a href="{{ route('admin.archive.enquiries', ['search' => $product->title]) }}" class="btn btn-soft-info">
                            <i class="ri-question-answer-line align-middle me-1"></i> View Enquiries
                        </a>
                    </div>
                </div>
            </div>

            {{-- Consolidated Information Card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Item Information</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Price Range</td>
                                    <td class="px-0 text-end fw-semibold">
                                        {{ $product->currency }} {{ number_format($product->price_min_amount) }} - {{ number_format($product->price_max_amount) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Quantity</td>
                                    <td class="px-0 text-end">{{ $product->quantity ?? 1 }} items</td>
                                </tr>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Era/Date</td>
                                    <td class="px-0 text-end">{{ $product->era_tag ?? 'N/A' }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td class="px-0 fw-medium text-muted pt-2">Restriction Mode</td>
                                    <td class="px-0 text-end pt-2">
                                        @if($product->restriction_mode == 'public')
                                            <span class="badge bg-success-subtle text-success">Public</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Restricted</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Category</td>
                                    <td class="px-0 text-end fw-semibold text-primary">
                                        {{ $product->category?->title ?? 'None' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Blur Enabled</td>
                                    <td class="px-0 text-end">
                                        @if($product->blur_enabled)
                                            <span class="text-danger"><i class="ri-eye-off-line me-1"></i> Yes</span>
                                        @else
                                            <span class="text-success"><i class="ri-eye-line me-1"></i> No</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 fw-medium text-muted">Early Access</td>
                                    <td class="px-0 text-end">
                                        @if($product->early_access_enabled)
                                            <span class="text-primary">Enabled</span>
                                        @else
                                            <span class="text-muted">Disabled</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if($product->visibilityTiers->isNotEmpty())
                    <div class="mt-3 border-top pt-2">
                        <p class="text-muted fs-11 mb-1 fw-bold text-uppercase">Visible To Tiers</p>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($product->visibilityTiers as $tier)
                                <span class="badge bg-light text-muted">{{ $tier->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- reusable edit modal --}}
    <livewire:admin.archive.products.index :modalsOnly="true" :key="'archive-product-edit-modal-ref'" />
</div>
