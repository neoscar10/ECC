<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label">Placement <span class="text-danger">*</span></label>
        <select class="form-select" wire:model.live="placement">
            <option value="">Select Placement...</option>
            <option value="home">Home</option>
            <option value="explore">Explore</option>
            <option value="profile">Profile</option>
            <option value="announcements">Announcements</option>
        </select>
        @error('placement') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>

    <div class="col-md-5">
        <label class="form-label">Internal Title <span class="text-danger">*</span></label>
        <input type="text" class="form-control" wire:model.live="title" placeholder="e.g. Homepage Hero">
        <div class="form-text">Admin-only label for identification.</div>
        @error('title') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>

    <div class="col-md-2 mt-4 pt-4">
        <div class="form-check form-switch form-switch-lg">
            <input class="form-check-input" type="checkbox" role="switch" id="isActiveSwitch" wire:model.live="isActive">
            <label class="form-check-label" for="isActiveSwitch">Active Status</label>
        </div>
    </div>
</div>
