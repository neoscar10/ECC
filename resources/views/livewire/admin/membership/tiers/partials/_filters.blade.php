<div class="card-header border-0">
    <div class="row g-4 align-items-center">
        <div class="col-sm">
            <div class="search-box">
                <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search tiers...">
                <i class="ri-search-line search-icon"></i>
            </div>
        </div>
        <div class="col-sm-auto ms-auto">
            @role('super_admin')
            <div class="hstack gap-2">
                <button type="button" class="btn btn-success add-btn" wire:click="create"><i class="ri-add-line align-bottom me-1"></i> Add Tier</button>
            </div>
            @endrole
        </div>
    </div>
</div>
