<div>
    <div class="modal fade" id="createAuctionModal" tabindex="-1" aria-labelledby="createAuctionModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAuctionModalLabel">{{ $isEditMode ? 'Edit Auction Lot' : 'Create Auction Lot' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Wizard Navigation -->
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

                            <!-- Step 2: Media Files -->
                            <div class="ap-step">
                                <button class="ap-pill btn {{ $createStep === 2 ? 'active' : '' }} {{ $createStep > 2 ? 'done' : '' }}" 
                                    wire:click="goToStep(2)" type="button" @if(!$isEditMode && $createStep < 2) disabled @endif>
                                    <span class="step-icon">@if($createStep > 2) <i class="ri-check-line"></i> @else 2 @endif</span>
                                    <span class="step-label d-none d-sm-block">Media Files</span>
                                </button>
                            </div>

                            <!-- Step 3: Anti-Sniping -->
                            <div class="ap-step">
                                <button class="ap-pill btn {{ $createStep === 3 ? 'active' : '' }} {{ $createStep > 3 ? 'done' : '' }}" 
                                    wire:click="goToStep(3)" type="button" @if(!$isEditMode && $createStep < 3) disabled @endif>
                                    <span class="step-icon">@if($createStep > 3) <i class="ri-check-line"></i> @else 3 @endif</span>
                                    <span class="step-label d-none d-sm-block">Anti-Sniping</span>
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
                                    <span class="step-label d-none d-sm-block">Review Details</span>
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
                    
                    <form wire:submit.prevent="save" id="auctionForm">
                        <div class="tab-content text-muted">
                            {{-- Include Steps --}}
                            @if($createStep === 1) @include('livewire.admin.auctions.partials.steps._step1-basic')
                            @elseif($createStep === 2) @include('livewire.admin.auctions.partials.steps._step2-media')
                            @elseif($createStep === 3) @include('livewire.admin.auctions.partials.steps._step3-sniping')
                            @elseif($createStep === 4) @include('livewire.admin.auctions.partials.steps._step4-access')
                            @elseif($createStep === 5) @include('livewire.admin.auctions.partials.steps._step5-review')
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
                             <button type="button" class="btn btn-success" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">Save Changes</span>
                                <span wire:loading wire:target="save">Saving...</span>
                            </button>
                        @endif

                        @if($createStep < 5)
                            <button type="button" class="btn btn-primary" wire:click="nextStep" wire:loading.attr="disabled">
                                Next Step <i class="ri-arrow-right-line align-middle ms-1"></i>
                            </button>
                        @else
                            <button type="submit" form="auctionForm" class="btn btn-success" wire:loading.attr="disabled">
                                <span wire:loading.remove>{{ $isEditMode ? 'Update Lot' : 'Create Lot' }}</span>
                                <span wire:loading>Saving...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('livewire:initialized', () => {
            var createModal = new bootstrap.Modal(document.getElementById('createAuctionModal'));
            Livewire.on('show-create-modal', () => { createModal.show(); });
            Livewire.on('hide-create-modal', () => { createModal.hide(); });
        });
    </script>
    <style>
    /* Scoped Stepper Styles (Cloned from Archive) */
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
