<div class="modal fade" id="createProductModal" tabindex="-1" aria-labelledby="createProductModalLabel" aria-hidden="true" wire:ignore.self data-bs-backdrop="static"
     >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createProductModalLabel">{{ $isEditMode ? 'Edit Product' : 'Add Product' }}</h5>
                <button type="button" class="btn-close" wire:click="closeModal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Wizard Navigation -->
                <div class="archive-product-stepper mb-5">
                    <div class="ap-stepper d-flex justify-content-between position-relative">
                        <!-- Step 1: Basic Details -->
                        <div class="ap-step">
                            <button class="ap-pill btn {{ $createStep === 1 ? 'active' : '' }} {{ $createStep > 1 ? 'done' : '' }}" 
                                wire:click="goToStep(1)" type="button"
                                @if(!$isEditMode && $createStep < 1) disabled @endif>
                                <span class="step-icon">
                                    @if($createStep > 1) <i class="ri-check-line"></i> @else 1 @endif
                                </span>
                                <span class="step-label d-none d-sm-block">Basic Details</span>
                            </button>
                        </div>

                        <!-- Step 2: Media Files -->
                        <div class="ap-step">
                            <button class="ap-pill btn {{ $createStep === 2 ? 'active' : '' }} {{ $createStep > 2 ? 'done' : '' }}" 
                                wire:click="goToStep(2)" type="button"
                                @if(!$isEditMode && $createStep < 2) disabled @endif>
                                <span class="step-icon">
                                    @if($createStep > 2) <i class="ri-check-line"></i> @else 2 @endif
                                </span>
                                <span class="step-label d-none d-sm-block">Media Files</span>
                            </button>
                        </div>

                        <!-- Step 3: Access Settings -->
                        <div class="ap-step">
                            <button class="ap-pill btn {{ $createStep === 3 ? 'active' : '' }} {{ $createStep > 3 ? 'done' : '' }}" 
                                wire:click="goToStep(3)" type="button"
                                @if(!$isEditMode && $createStep < 3) disabled @endif>
                                <span class="step-icon">
                                    @if($createStep > 3) <i class="ri-check-line"></i> @else 3 @endif
                                </span>
                                <span class="step-label d-none d-sm-block">Access Settings</span>
                            </button>
                        </div>

                        <!-- Step 4: Review Details -->
                        <div class="ap-step">
                            <button class="ap-pill btn {{ $createStep === 4 ? 'active' : '' }} {{ $createStep > 4 ? 'done' : '' }}" 
                                wire:click="goToStep(4)" type="button"
                                @if(!$isEditMode && $createStep < 4) disabled @endif>
                                <span class="step-icon">
                                    @if($createStep > 4) <i class="ri-check-line"></i> @else 4 @endif
                                </span>
                                <span class="step-label d-none d-sm-block">Review Details</span>
                            </button>
                        </div>
                    </div>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>There were some errors with your submission:</strong>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                <!-- Wizard Content -->
                <form wire:submit.prevent="{{ $isEditMode ? 'updateProduct' : 'storeProduct' }}" id="productForm">
                    <div class="tab-content text-muted">
                        @if($createStep === 1)
                            <div class="tab-pane active" id="step1" role="tabpanel">
                                @include('livewire.admin.archive.products.partials.steps._step1-basic')
                            </div>
                        @elseif($createStep === 2)
                            <div class="tab-pane active" id="step2" role="tabpanel">
                                @include('livewire.admin.archive.products.partials.steps._step2-media')
                            </div>
                        @elseif($createStep === 3)
                            <div class="tab-pane active" id="step3" role="tabpanel">
                                @include('livewire.admin.archive.products.partials.steps._step3-restriction')
                            </div>
                        @elseif($createStep === 4)
                            <div class="tab-pane active" id="step4" role="tabpanel">
                                @include('livewire.admin.archive.products.partials.steps._step4-review')
                            </div>
                        @endif
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                {{-- Always keep ONE cancel button --}}
                <button type="button" class="btn btn-light" wire:click="closeModal" data-bs-dismiss="modal">Cancel</button>
                
                <div class="d-flex gap-2">
                    {{-- Back button: Step 2+ --}}
                    @if($createStep > 1)
                        <button type="button" class="btn btn-light" wire:click="prevStep">Back</button>
                    @endif
                    
                    {{-- Save Changes: Inline save for Edit Mode (Steps 1-3) --}}
                    @if($isEditMode && $createStep < 4)
                         <button type="button" class="btn btn-success" wire:click="updateProduct" wire:loading.attr="disabled" wire:target="updateProduct">
                            <span wire:loading.remove wire:target="updateProduct">Save Changes</span>
                            <span wire:loading wire:target="updateProduct">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Saving...
                            </span>
                        </button>
                    @endif

                    {{-- Next Step (1-3) or Submit (4) --}}
                    @if($createStep < 4)
                        <button type="button" class="btn btn-primary" wire:click="nextStep" wire:loading.attr="disabled">
                            Next Step <i class="ri-arrow-right-line align-middle ms-1"></i>
                        </button>
                    @else
                        <button type="submit" form="productForm" class="btn btn-success" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ $isEditMode ? 'Update Product' : 'Create Product' }}</span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Saving...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Scoped Stepper Styles */
.archive-product-stepper .ap-stepper { 
    gap: 24px; 
}
.archive-product-stepper .ap-step { 
    flex: 1 1 0; 
    position: relative; 
    display: flex; 
    justify-content: center; 
}
/* Connector Line - Only visible on non-last steps */
.archive-product-stepper .ap-step:not(:last-child)::after {
    content: "";
    position: absolute;
    top: 50%;
    /* Start from center + approx half pill width + spacing */
    left: calc(50% + 100px); 
    right: -100px; /* Extend into next item's space */
    height: 3px;
    background: var(--vz-light);
    transform: translateY(-50%);
    z-index: 1;
}

/* Adjust connector length for responsive if needed */
@media (max-width: 576px) {
    .archive-product-stepper .ap-step:not(:last-child)::after {
        left: calc(50% + 40px);
        right: -40px;
    }
}

.archive-product-stepper .ap-pill { 
    position: relative; 
    z-index: 2; 
    background: var(--vz-card-bg-custom, #fff); 
    border: 2px solid var(--vz-light); 
    border-radius: 999px; 
    padding: 10px 22px; 
    display: flex; 
    align-items: center; 
    gap: 10px;
    transition: all 0.3s ease;
    color: var(--vz-body-color);
}

.archive-product-stepper .ap-pill:hover {
    border-color: var(--vz-primary);
}

.archive-product-stepper .ap-pill.active {
    border-color: var(--vz-success);
    color: var(--vz-success);
    background-color: var(--vz-card-bg-custom, #fff);
}

.archive-product-stepper .ap-pill.done {
    border-color: var(--vz-success);
    color: var(--vz-success);
    background-color: rgba(10, 187, 135, 0.1);
}

.archive-product-stepper .step-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: var(--vz-light);
    color: var(--vz-muted);
    font-size: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
}

.archive-product-stepper .ap-pill.active .step-icon,
.archive-product-stepper .ap-pill.done .step-icon {
    background-color: var(--vz-success);
    color: #fff;
}
</style>


