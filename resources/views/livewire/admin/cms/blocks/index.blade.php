<div>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">CMS Blocks</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">CMS</a></li>
                            <li class="breadcrumb-item active">Blocks</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card" id="blockList">
                    <div class="card-header border-0">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm-3">
                                <h5 class="card-title mb-0 flex-grow-1">Blocks List</h5>
                            </div>
                            <div class="col-sm-auto ms-auto">
                                <div class="hstack gap-2">
                                    <button type="button" class="btn btn-primary add-btn" wire:click="create"><i class="ri-add-line align-bottom me-1"></i> Add Block</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body border border-dashed border-end-0 border-start-0">
                         <div class="row g-2 align-items-center">
                            <div class="col-md-3 service-filter">
                                <div class="search-box">
                                    <input type="text" class="form-control search" placeholder="Search for blocks..." wire:model.live.debounce.500ms="search">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <div>
                                    <select class="form-control" wire:model.live="filterPlacement" style="min-width: 140px;">
                                        <option value="">All Placements</option>
                                        <option value="home">Home</option>
                                        <option value="explore">Explore</option>
                                        <option value="profile">Profile</option>
                                        <option value="announcements">Announcements</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <div>
                                    <select class="form-control" wire:model.live="filterType" style="min-width: 120px;">
                                        <option value="">All Types</option>
                                        <option value="card">Card</option>
                                        <option value="banner">Banner</option>
                                        <option value="slider">Slider</option>
                                        <option value="text">Text</option>
                                    </select>
                                </div>
                            </div>
                             <div class="col-md-auto">
                                <div>
                                    <select class="form-control" wire:model.live="filterStatus" style="min-width: 120px;">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                         <div class="table-responsive table-card mb-1">
                            <table class="table align-middle table-nowrap" id="blockTable">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th scope="col" style="width: 50px;"></th>
                                        <th class="sort" data-sort="placement">Placement</th>
                                        <th class="sort" data-sort="block">Block</th>
                                        <th class="sort" data-sort="type">Type</th>
                                        <th class="sort" data-sort="visibility">Visibility</th>
                                        <th class="sort" data-sort="status">Status</th>
                                        <th class="sort" data-sort="action">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all" wire:sortable="updateOrder">
                                    @forelse($blocks as $block)
                                        <tr wire:sortable.item="{{ $block->id }}" wire:key="block-{{ $block->id }}">
                                            <td class="text-center" style="cursor: move;" wire:sortable.handle>
                                                <i class="ri-drag-move-2-line fs-18 text-muted"></i>
                                            </td>
                                            <td><span class="badge bg-light text-dark text-uppercase">{{ $block->placement }}</span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if(isset($block->content['image_url']))
                                                        <div class="flex-shrink-0 me-2">
                                                            <img src="{{ $block->content['image_url'] }}" alt="" class="avatar-xs rounded-3">
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h5 class="fs-14 mb-0"><a href="javascript:void(0);" class="text-body">{{ $block->title }}</a></h5>
                                                        <p class="text-muted mb-0">{{ Str::limit($block->content['body'] ?? $block->content['subtitle'] ?? '', 30) }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-info-subtle text-info text-uppercase">{{ $block->type }}</span></td>
                                            <td>
                                                @if($block->restriction_mode === 'public')
                                                    <span class="badge bg-success-subtle text-success">Public</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Restricted</span>
                                                    @if($block->blur_enabled)
                                                        <span class="badge bg-secondary-subtle text-secondary">+ Blur</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if($block->is_active)
                                                    <span class="badge bg-success-subtle text-success text-uppercase">Active</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger text-uppercase">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown d-inline-block">
                                                    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-fill align-middle"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a href="javascript:void(0);" class="dropdown-item edit-item-btn" wire:click="edit({{ $block->id }})"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit</a></li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item remove-item-btn" wire:confirm="Are you sure you want to delete this block?" wire:click="delete({{ $block->id }})">
                                                                <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No blocks found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end">
                            {{ $blocks->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modals -->
    <!-- Create/Edit Modal with Pill Stepper -->
    <div class="modal fade" id="createBlockModal" tabindex="-1" aria-labelledby="createBlockModalLabel" aria-hidden="true" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createBlockModalLabel">{{ $isEditMode ? 'Edit Content Block' : 'Create Content Block' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Wizard Navigation (Auction Lot Style) -->
                    <div class="archive-product-stepper mb-5">
                        <div class="ap-stepper d-flex justify-content-between position-relative">
                            <!-- Step 1: Basic Details -->
                            <div class="ap-step">
                                <button class="ap-pill btn {{ $createStep === 1 ? 'active' : '' }} {{ $createStep > 1 ? 'done' : '' }}" 
                                    wire:click="goToStep(1)" type="button" @if(!$isEditMode && $createStep < 1) disabled @endif>
                                    <span class="step-icon">@if($createStep > 1) <i class="ri-check-line"></i> @else 1 @endif</span>
                                    <span class="step-label d-none d-sm-block">Basic Details</span>
                                </button>
                            </div>

                            <!-- Step 2: Content Type -->
                            <div class="ap-step">
                                <button class="ap-pill btn {{ $createStep === 2 ? 'active' : '' }} {{ $createStep > 2 ? 'done' : '' }}" 
                                    wire:click="goToStep(2)" type="button" @if(!$isEditMode && $createStep < 2) disabled @endif>
                                    <span class="step-icon">@if($createStep > 2) <i class="ri-check-line"></i> @else 2 @endif</span>
                                    <span class="step-label d-none d-sm-block">Content Type</span>
                                </button>
                            </div>

                            <!-- Step 3: Content Builder -->
                            <div class="ap-step">
                                <button class="ap-pill btn {{ $createStep === 3 ? 'active' : '' }} {{ $createStep > 3 ? 'done' : '' }}" 
                                    wire:click="goToStep(3)" type="button" @if(!$isEditMode && $createStep < 3) disabled @endif>
                                    <span class="step-icon">@if($createStep > 3) <i class="ri-check-line"></i> @else 3 @endif</span>
                                    <span class="step-label d-none d-sm-block">Content Builder</span>
                                </button>
                            </div>

                            <!-- Step 4: Access Settings -->
                            <div class="ap-step">
                                <button class="ap-pill btn {{ $createStep === 4 ? 'active' : '' }} {{ $createStep > 4 ? 'done' : '' }}" 
                                    wire:click="goToStep(4)" type="button" @if(!$isEditMode && $createStep < 4) disabled @endif>
                                    <span class="step-icon">@if($createStep > 4) <i class="ri-check-line"></i> @else 4 @endif</span>
                                    <span class="step-label d-none d-sm-block">Access Settings</span>
                                </button>
                            </div>

                            <!-- Step 5: Review Details -->
                            <div class="ap-step">
                                <button class="ap-pill btn {{ $createStep === 5 ? 'active' : '' }} {{ $createStep > 5 ? 'done' : '' }}" 
                                    wire:click="goToStep(5)" type="button" @if(!$isEditMode && $createStep < 5) disabled @endif>
                                    <span class="step-icon">@if($createStep > 5) <i class="ri-check-line"></i> @else 5 @endif</span>
                                    <span class="step-label d-none d-sm-block">Review</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Validation Errors:</strong>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form wire:submit.prevent="store" id="blockForm">
                        <div class="tab-content text-muted">
                            @if($createStep === 1) @include('livewire.admin.cms.blocks.partials._step1-basic')
                            @elseif($createStep === 2) @include('livewire.admin.cms.blocks.partials._step2-type')
                            @elseif($createStep === 3) @include('livewire.admin.cms.blocks.partials._step3-builder', ['builderSummary' => $this->builderSummary])
                            @elseif($createStep === 4) @include('livewire.admin.cms.blocks.partials._step4-access')
                            @elseif($createStep === 5) @include('livewire.admin.cms.blocks.partials._step5-review')
                            @endif
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="closeModal" data-bs-dismiss="modal">Cancel</button>
                    <div class="d-flex gap-2">
                        @if($createStep > 1)
                            <button type="button" class="btn btn-light" wire:click="prevStep">Back</button>
                        @endif
                        
                        {{-- Save Changes (Edit Mode Only, Steps 1-4) --}}
                        @if($isEditMode && $createStep < 5)
                            <button type="button" class="btn btn-success" wire:click="store" wire:loading.attr="disabled" wire:target="store">
                                <span wire:loading.remove wire:target="store">Save Changes</span>
                                <span wire:loading wire:target="store">Saving...</span>
                            </button>
                        @endif

                        @if($createStep < 5)
                            <button type="button" class="btn btn-primary" wire:click="nextStep" wire:loading.attr="disabled">
                                Next Step <i class="ri-arrow-right-line align-middle ms-1"></i>
                            </button>
                        @else
                            <button type="submit" form="blockForm" class="btn btn-success" wire:loading.attr="disabled">
                                <span wire:loading.remove>{{ $isEditMode ? 'Update Block' : 'Create Block' }}</span>
                                <span wire:loading>Saving...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script for Modal Handling & Scoped Styles -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            var createModal = new bootstrap.Modal(document.getElementById('createBlockModal'));
            Livewire.on('show-create-modal', () => { createModal.show(); });
            Livewire.on('hide-create-modal', () => { createModal.hide(); });
            Livewire.on('refresh-blocks', () => { createModal.hide(); });
        });
    </script>
    <style>
    /* Scoped Stepper Styles (Cloned from Auction/Archive) */
    .archive-product-stepper .ap-stepper { gap: 10px; }
    .archive-product-stepper .ap-step { flex: 1 1 0; position: relative; display: flex; justify-content: center; }
    .archive-product-stepper .ap-step:not(:last-child)::after {
        content: ""; position: absolute; top: 50%; left: calc(50% + 100px); right: -100px; height: 3px; background: var(--vz-light); transform: translateY(-50%); z-index: 1;
    }
    @media (max-width: 576px) {
        .archive-product-stepper .ap-step:not(:last-child)::after { left: calc(50% + 40px); right: -40px; }
    }
    .archive-product-stepper .ap-pill { 
        position: relative; z-index: 2; background: var(--vz-card-bg-custom, #fff); border: 2px solid var(--vz-light); border-radius: 999px; padding: 8px 16px; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; color: var(--vz-body-color); flex-direction: row; width: auto; height: auto;
    }
    .archive-product-stepper .ap-pill:hover { border-color: var(--vz-primary); }
    .archive-product-stepper .ap-pill.active { border-color: var(--vz-success); color: var(--vz-success); background-color: var(--vz-card-bg-custom, #fff); }
    .archive-product-stepper .ap-pill.done { border-color: var(--vz-success); color: var(--vz-success); background-color: rgba(10, 187, 135, 0.1); }
    .archive-product-stepper .step-icon { 
        display: flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; background-color: var(--vz-light); color: var(--vz-muted); font-size: 11px; font-weight: 700; transition: all 0.3s ease;
    }
    .archive-product-stepper .ap-pill.active .step-icon, .archive-product-stepper .ap-pill.done .step-icon { background-color: var(--vz-success); color: #fff; }
    </style>
</div>
