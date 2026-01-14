<div class="row g-3">
    <div class="col-xxl-4 col-sm-6">
        <div class="search-box">
            <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search for category...">
            <i class="ri-search-line search-icon"></i>
        </div>
    </div>
    
    <div class="col-xxl-2 col-sm-4">
        <div>
            <select class="form-select" wire:model.live="filterVisibility">
                <option value="">All Visibility</option>
                <option value="public">Public</option>
                <option value="restricted">Restricted</option>
            </select>
        </div>
    </div>
</div>
