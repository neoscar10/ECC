<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Currency</label>
        <input type="text" class="form-control" wire:model="currency" readonly>
    </div>
    <div class="col-md-3">
        <label class="form-label">Starting Price <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">{{ $currency }}</span>
            <input type="number" step="0.01" class="form-control" wire:model="starting_price">
        </div>
        @error('starting_price') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Reserve Price</label>
        <div class="input-group">
            <span class="input-group-text">{{ $currency }}</span>
            <input type="number" step="0.01" class="form-control" wire:model="min_selling_price">
        </div>
        <small class="text-muted">Optional minimum selling price</small>
    </div>
    <div class="col-md-3">
        <label class="form-label">Min Increment</label>
        <div class="input-group">
            <span class="input-group-text">{{ $currency }}</span>
            <input type="number" step="0.01" class="form-control" wire:model="min_increment">
        </div>
    </div>
    
    <div class="col-12"><hr class="text-muted"></div>
    
    <div class="col-md-6">
        <label class="form-label">Starts At <span class="text-danger">*</span></label>
        <input type="datetime-local" class="form-control" wire:model="starts_at">
        @error('starts_at') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Ends At <span class="text-danger">*</span></label>
        <input type="datetime-local" class="form-control" wire:model="ends_at">
        @error('ends_at') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
