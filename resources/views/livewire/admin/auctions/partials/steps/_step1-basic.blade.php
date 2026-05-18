<div class="row g-3">
    <!-- Title -->
    <div class="col-12">
        <label class="form-label">Lot Title <span class="text-danger">*</span></label>
        <input type="text" class="form-control" wire:model="title" placeholder="Enter lot title">
        @error('title') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Pricing (Parity with Archive Price Range) -->
    <div class="col-md-4">
        <label class="form-label">Starting Price <span class="text-danger">*</span></label>
        <input type="number" class="form-control" wire:model="starting_price" placeholder="e.g. 1000">
        @error('starting_price') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Reserve Price (Optional)</label>
        <input type="number" class="form-control" wire:model="min_selling_price" placeholder="Minimum selling price">
        @error('min_selling_price') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Min Increment <span class="text-danger">*</span></label>
        <input type="number" class="form-control" wire:model="min_increment" placeholder="e.g. 100">
        @error('min_increment') <span class="text-danger text-sm">{{ $message }}</span> @enderror
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

    <!-- Description -->
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea class="form-control" wire:model="description" rows="4" placeholder="Enter lot description..."></textarea>
        @error('description') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Scheduling (Parity with Archive) -->
    <!-- Scheduling (Parity with Archive) -->
    <div class="col-12">
        <div class="d-flex gap-4 flex-wrap">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="goLiveNow" wire:model.live="goLiveNow">
                <label class="form-check-label" for="goLiveNow">Go Live Now</label>
            </div>
            
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="outcomeToggle" 
                       @checked($outcome_decision_mode === 'admin') 
                       wire:click="$set('outcome_decision_mode', '{{ $outcome_decision_mode === 'system' ? 'admin' : 'system' }}')">
                <label class="form-check-label" for="outcomeToggle">
                    Outcome Decision: <span class="fw-semibold {{ $outcome_decision_mode === 'admin' ? 'text-warning' : 'text-success' }}">
                        {{ $outcome_decision_mode === 'admin' ? 'Admin (Manual)' : 'System (Automatic)' }}
                    </span>
                </label>
            </div>
        </div>
        <div class="form-text mt-2">
            @if($outcome_decision_mode === 'system')
                System (Automatic): Winner/Unsold is decided automatically when time ends.
            @else
                Admin (Manual): Auction stops at end time and waits for admin decision.
            @endif
        </div>
    </div>
    @if(!$goLiveNow)

    
        <div class="col-md-4">
            <label class="form-label">Start Time <span class="text-danger">*</span></label>
            <input type="datetime-local" class="form-control" wire:model="starts_at">
            @error('starts_at') <span class="text-danger text-sm">{{ $message }}</span> @enderror
        </div>
        
        
    @endif
    
    <div class="col-md-4">
        <label class="form-label">End Time <span class="text-danger">*</span></label>
        <input type="datetime-local" class="form-control" wire:model="ends_at">
        @error('ends_at') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>

    @if(!$goLiveNow)
        <div class="col-12">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="earlyAccess" wire:model="allowsEarlyAccess">
                <label class="form-check-label" for="earlyAccess">Allow Early Access</label>
            </div>
             <div class="form-text mt-0">If enabled, you can configure early access windows after creating the lot.</div>
        </div>
    @endif
</div>
