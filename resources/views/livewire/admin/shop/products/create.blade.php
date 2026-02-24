<div class="modal fade" id="createProductModal" aria-labelledby="createProductModalLabel" aria-hidden="true" wire:ignore.self data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createProductModalLabel">
                    @if($variationsOnlyMode)
                        Manage Variations
                    @else
                        {{ $isEditMode ? 'Edit Product' : 'Add Product' }}
                    @endif
                </h5>
                <button type="button" class="btn-close" wire:click="closeModal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Stepper Navigation -->
                @if(!$variationsOnlyMode)
                <div class="shop-product-stepper mb-5">
                    <div class="sp-stepper d-flex justify-content-between position-relative">
                        <!-- Step 1: Basic -->
                        <div class="sp-step">
                            <button class="sp-pill btn {{ $createStep === 1 ? 'active' : '' }} {{ $createStep > 1 ? 'done' : '' }}" 
                                wire:click="$set('createStep', 1)" type="button" @if(!$isEditMode && $createStep < 1) disabled @endif>
                                <span class="step-icon">@if($createStep > 1) <i class="ri-check-line"></i> @else 1 @endif</span>
                                <span class="step-label d-none d-sm-block">Basic Info</span>
                            </button>
                        </div>
                        <!-- Step 2: Media (NEW) -->
                        <div class="sp-step">
                            <button class="sp-pill btn {{ $createStep === 2 ? 'active' : '' }} {{ $createStep > 2 ? 'done' : '' }}" 
                                wire:click="$set('createStep', 2)" type="button" @if(!$isEditMode && $createStep < 2) disabled @endif>
                                <span class="step-icon">@if($createStep > 2) <i class="ri-check-line"></i> @else 2 @endif</span>
                                <span class="step-label d-none d-sm-block">Media</span>
                            </button>
                        </div>
                        <!-- Step 3: Attributes -->
                        <div class="sp-step">
                            <button class="sp-pill btn {{ $createStep === 3 ? 'active' : '' }} {{ $createStep > 3 ? 'done' : '' }}" 
                                wire:click="$set('createStep', 3)" type="button" @if(!$isEditMode && $createStep < 3) disabled @endif>
                                <span class="step-icon">@if($createStep > 3) <i class="ri-check-line"></i> @else 3 @endif</span>
                                <span class="step-label d-none d-sm-block">Attributes</span>
                            </button>
                        </div>
                        <!-- Step 4: Variations -->
                        <div class="sp-step">
                            <button class="sp-pill btn {{ $createStep === 4 ? 'active' : '' }} {{ $createStep > 4 ? 'done' : '' }}" 
                                wire:click="$set('createStep', 4)" type="button" @if(!$isEditMode && $createStep < 4) disabled @endif>
                                <span class="step-icon">@if($createStep > 4) <i class="ri-check-line"></i> @else 4 @endif</span>
                                <span class="step-label d-none d-sm-block">Variations</span>
                            </button>
                        </div>
                        <!-- Step 5: Review -->
                        <div class="sp-step">
                            <button class="sp-pill btn {{ $createStep === 5 ? 'active' : '' }} {{ $createStep > 5 ? 'done' : '' }}" 
                                wire:click="$set('createStep', 5)" type="button" @if(!$isEditMode && $createStep < 5) disabled @endif>
                                <span class="step-icon">@if($createStep > 5) <i class="ri-check-line"></i> @else 5 @endif</span>
                                <span class="step-label d-none d-sm-block">Review</span>
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Correction Needed:</strong>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <!-- Wizard Content -->
                <form wire:submit.prevent="{{ $isEditMode ? 'updateProduct' : 'storeProduct' }}" id="productForm">
                    <div class="tab-content text-muted p-2">
                        <!-- Step 1: Basic Info -->
                        @if($createStep === 1)
                            <div class="tab-pane active fade show" id="step1">
                                @include('livewire.admin.shop.products.partials.steps._step1-basic')
                            </div>
                        @endif

                        <!-- Step 2: Media -->
                        @if($createStep === 2)
                            <div class="tab-pane active fade show" id="step2">
                                @include('livewire.admin.shop.products.partials.steps._step2-media')
                            </div>
                        @endif

                        <!-- Step 3: Attributes -->
                        @if($createStep === 3)
                            <div class="tab-pane active fade show" id="step3">
                                @include('livewire.admin.shop.products.partials.steps._step3-attributes')
                            </div>
                        @endif

                        <!-- Step 4: Variations -->
                        @if($createStep === 4)
                            <div class="tab-pane active fade show" id="step4">
                                @include('livewire.admin.shop.products.partials.steps._step4-variations')
                            </div>
                        @endif

                        <!-- Step 5: Review -->
                        @if($createStep === 5)
                            <div class="tab-pane active fade show" id="step5">
                                @include('livewire.admin.shop.products.partials.steps._step5-review')
                            </div>
                        @endif
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                {{-- Always keep ONE cancel button --}}
                <button type="button" class="btn btn-light" wire:click="closeModal" data-bs-dismiss="modal">
                    Cancel
                </button>

                @if($variationsOnlyMode)
                     <button type="button" class="btn btn-success" wire:click="saveVariationsOnly">
                        <i class="ri-save-line align-bottom me-1"></i> Save Changes
                    </button>
                @else
                    <div class="d-flex gap-2">
                        {{-- Show Back from step 2+ only --}}
                        @if($createStep > 1)
                            <button type="button" class="btn btn-light" wire:click="prevStep">
                                Back
                            </button>
                        @endif

                        @if($createStep < 5)
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
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Variation Gallery Modal -->
<div class="modal fade" id="variationGalleryModal" tabindex="-1" aria-hidden="true" wire:ignore.self style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Variation Gallery</h5>
                <button type="button" class="btn-close" wire:click="closeVariationGallery" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($activeVariationGroupIndex !== null && $activeVariationValueIndex !== null)
                    <div class="mb-3">
                        <label class="form-label">Upload Images</label>
                        <div class="dropzone p-4 border-dashed text-center rounded" 
                             style="cursor: pointer; position: relative;">
                             {{-- Fix #1: Bind to single-level property --}}
                             <input type="file" wire:model="activeGalleryUploads" multiple accept="image/png,image/jpeg"
                                class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                style="cursor: pointer;">
                             <div class="text-muted">
                                <i class="ri-upload-cloud-2-line fs-24"></i>
                                <p class="mb-0 mt-2">Click or Drop images here</p>
                             </div>
                        </div>
                         <div wire:loading wire:target="activeGalleryUploads" class="text-center mt-2 text-primary">
                            <span class="spinner-border spinner-border-sm me-1"></span> Uploading...
                        </div>
                        @error('activeGalleryUploads.*') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                        {{-- 1. Display STAGED images (previously saved in temp state) --}}
                        @foreach($activeGalleryExistingImages as $idx => $img)
                            <div class="col-4 col-sm-3 position-relative">
                                <div class="border rounded overflow-hidden position-relative" style="padding-top: 100%;">
                                    {{-- Handle TemporaryUploadedFile vs String Path if we support edit mode later --}}
                                    @if(is_object($img) && method_exists($img, 'temporaryUrl'))
                                        <img src="{{ $img->temporaryUrl() }}" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
                                    @else
                                        {{-- Fallback or Path --}}
                                        <div class="position-absolute top-0 start-0 w-100 h-100 bg-light d-flex align-items-center justify-content-center">
                                            <i class="ri-image-line text-muted"></i>
                                        </div>
                                    @endif
                                    
                                    <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 m-1 shadow-sm"
                                        wire:click="removeVariationGalleryImage('staged', {{ $idx }})">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                    <span class="position-absolute bottom-0 start-0 badge bg-success m-1 font-size-10">Saved</span>
                                </div>
                            </div>
                        @endforeach

                        {{-- 2. Display PENDING uploads --}}
                        @if($activeGalleryUploads)
                            @foreach($activeGalleryUploads as $idx => $img)
                                <div class="col-4 col-sm-3 position-relative">
                                    <div class="border rounded overflow-hidden position-relative border-primary" style="padding-top: 100%;">
                                        <img src="{{ $img->temporaryUrl() }}" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="opacity: 0.8;">
                                        <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 m-1 shadow-sm"
                                            wire:click="removeVariationGalleryImage('pending', {{ $idx }})">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                        <span class="position-absolute bottom-0 start-0 badge bg-warning text-dark m-1 font-size-10">Pending</span>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    
                    @if(empty($activeGalleryExistingImages) && empty($activeGalleryUploads))
                        <p class="text-center text-muted mt-3">No images uploaded yet.</p>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" wire:click="closeVariationGallery">Cancel</button>
                <button type="button" class="btn btn-primary" wire:click="saveVariationGalleryImages">Save & Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Conflict Modal -->
<div class="modal fade" id="galleryConflictModal" tabindex="-1" aria-hidden="true" wire:ignore.self style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-subtle">
                <h5 class="modal-title text-danger"><i class="ri-error-warning-line me-1"></i> Gallery Control Limit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($galleryConflict)
                    <p class="mb-3">Only one variation group can control the product gallery images.</p>
                    <div class="alert alert-warning mb-0">
                        Currently, <strong>{{ $galleryConflict['existing'] }}</strong> is set as the gallery control group.
                        <br><br>
                        To enable gallery control for <strong>{{ $galleryConflict['attempted'] }}</strong>, you must first disable 'Has Images' on <strong>{{ $galleryConflict['existing'] }}</strong>.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK, I Understand</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        // ... (Gallery Modal Logic - existing) ...
        const galleryModalEl = document.getElementById('variationGalleryModal');
        const galleryModal = new bootstrap.Modal(galleryModalEl);

        Livewire.on('open-variation-gallery-modal', () => {
            galleryModal.show();
        });

        Livewire.on('hide-variation-gallery-modal', () => {
            galleryModal.hide();
        });
        
        // Conflict Modal
        const conflictModalEl = document.getElementById('galleryConflictModal');
        const conflictModal = new bootstrap.Modal(conflictModalEl);
        
        Livewire.on('show-gallery-conflict-modal', () => {
            conflictModal.show();
        });
    });
</script>

<style>
/* Reusing stepper styles from Archive */
.shop-product-stepper .sp-stepper { gap: 2px; }
.shop-product-stepper .sp-step { flex: 1; position: relative; display: flex; justify-content: center; }
.shop-product-stepper .sp-step:not(:last-child)::after {
    content: ""; position: absolute; top: 50%; left: 70%; right: -30%; height: 2px; background: var(--vz-light); transform: translateY(-50%); z-index: 1;
}
.shop-product-stepper .sp-pill {
    position: relative; z-index: 2; background: #fff; border: 1px solid var(--vz-border-color); border-radius: 50px; padding: 8px 18px; display: flex; align-items: center; gap: 8px; transition: all 0.2s; white-space: nowrap;
}
.shop-product-stepper .sp-pill.active { border-color: var(--vz-success); color: var(--vz-success); background: #fff; box-shadow: 0 0 0 2px rgba(10,187,135,0.1); }
.shop-product-stepper .sp-pill.done { border-color: var(--vz-success); background: rgba(10,187,135,0.1); color: var(--vz-success); }
.shop-product-stepper .sp-pill:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
