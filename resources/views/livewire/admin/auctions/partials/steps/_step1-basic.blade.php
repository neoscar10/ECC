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

    <!-- Description -->
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea class="form-control" wire:model="description" rows="4" placeholder="Enter lot description..."></textarea>
        @error('description') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Scheduling (Parity with Archive) -->
    <div class="col-12">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="goLiveNow" wire:model.live="goLiveNow">
            <label class="form-check-label" for="goLiveNow">Go Live Now</label>
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
