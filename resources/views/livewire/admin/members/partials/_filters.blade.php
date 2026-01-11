<div class="card-header border-0">
    <div class="row g-4 align-items-center">
        <div class="col-sm-3">
            <div class="search-box">
                <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search members...">
                <i class="ri-search-line search-icon"></i>
            </div>
        </div>
        <div class="col-sm-auto ms-auto">
            <div class="hstack gap-2">
                <select class="form-select" wire:model.live="tierFilter">
                    <option value="">All Tiers</option>
                    @foreach($tiers as $tier)
                        <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                    @endforeach
                </select>
                <select class="form-select" wire:model.live="statusFilter">
                    <option value="active">Active Members</option>
                    <option value="cancelled">Deactivated</option>
                    <option value="expired">Expired</option>
                    <option value="">All Statuses</option>
                </select>
            </div>
        </div>
    </div>
</div>
