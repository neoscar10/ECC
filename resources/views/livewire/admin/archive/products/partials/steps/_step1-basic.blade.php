<div class="row g-3">
    <!-- Title & Category -->
    <div class="col-md-6">
        <label class="form-label">Title <span class="text-danger">*</span></label>
        <input type="text" class="form-control" wire:model="title" placeholder="Enter product title">
        @error('title') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select class="form-select" wire:model="categoryId">
            <option value="">Select Category</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->title }}</option>
            @endforeach
        </select>
        @error('categoryId') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Price Range & Quantity -->
    <div class="col-md-4">
        <label class="form-label">Min Price (Price from)</label>
        <input type="number" class="form-control" wire:model="priceMin" placeholder="e.g. 1000">
        @error('priceMin') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Max Price (Price to)</label>
        <input type="number" class="form-control" wire:model="priceMax" placeholder="e.g. 5000">
        @error('priceMax') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Quantity <span class="text-danger">*</span></label>
        <input type="number" min="1" class="form-control" wire:model="quantity" placeholder="1">
        @error('quantity') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>
    
    <!-- Shipping Dimensions -->
    <div class="col-12 mt-2">
        <div class="border rounded p-3" style="border-color: var(--bs-border-color) !important;">
            <h6 class="mb-1 fw-semibold" style="font-size: 0.85rem;">
                <i class="mdi mdi-package-variant-closed me-1"></i>Shipping Dimensions
                <span class="text-muted fw-normal">(optional)</span>
            </h6>
            <p class="text-muted mb-3" style="font-size: 0.75rem;">Used for physical delivery courier fee calculation. Volumetric weight = L × B × H ÷ 5000.</p>
            <div class="row g-2">
                <div class="col-md-3 col-6">
                    <label class="form-label" style="font-size: 0.8rem;">Weight (kg)</label>
                    <input type="number" step="0.001" min="0.001" max="999.999" class="form-control form-control-sm" wire:model="weight_kg" placeholder="e.g. 0.5">
                    @error('weight_kg') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label" style="font-size: 0.8rem;">Length (cm)</label>
                    <input type="number" step="0.01" min="0.1" max="999.99" class="form-control form-control-sm" wire:model="length_cm" placeholder="e.g. 20">
                    @error('length_cm') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label" style="font-size: 0.8rem;">Breadth (cm)</label>
                    <input type="number" step="0.01" min="0.1" max="999.99" class="form-control form-control-sm" wire:model="breadth_cm" placeholder="e.g. 15">
                    @error('breadth_cm') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label" style="font-size: 0.8rem;">Height (cm)</label>
                    <input type="number" step="0.01" min="0.1" max="999.99" class="form-control form-control-sm" wire:model="height_cm" placeholder="e.g. 10">
                    @error('height_cm') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Descriptions -->
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea class="form-control" wire:model="descriptionUnlocked" rows="4" placeholder="Enter product description..."></textarea>
        @error('descriptionUnlocked') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Scheduling -->
    <div class="col-12">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="goLiveNow" wire:model.live="goLiveNow">
            <label class="form-check-label" for="goLiveNow">Go Live Now</label>
        </div>
    </div>
    @if(!$goLiveNow)
        <div class="col-md-6">
            <label class="form-label">Go Live At <span class="text-danger">*</span></label>
            <input type="datetime-local" class="form-control" wire:model="goLiveAt">
            @error('goLiveAt') <span class="text-danger text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="col-md-6 pt-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="allowsEarlyAccess" wire:model="allowsEarlyAccess">
                <label class="form-check-label" for="allowsEarlyAccess">Enable Early Access</label>
            </div>
        </div>
    @endif
</div>
