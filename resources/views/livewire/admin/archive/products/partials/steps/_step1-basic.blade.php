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
