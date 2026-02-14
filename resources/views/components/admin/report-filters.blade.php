@props([
    'dateRange' => true,
    'statusOptions' => [],
    'search' => true,
])

<div class="card">
    <div class="card-body">
        <div class="row g-3">
            @if($search)
            <div class="col-xxl-4 col-sm-6">
                <div class="search-box">
                    <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search for something...">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </div>
            @endif

            @if($dateRange)
            <div class="col-xxl-3 col-sm-6">
                <input type="date" class="form-control" wire:model.live="startDate" placeholder="Start Date">
            </div>
            <div class="col-xxl-3 col-sm-6">
                <input type="date" class="form-control" wire:model.live="endDate" placeholder="End Date">
            </div>
            @endif

            @if(!empty($statusOptions))
            <div class="col-xxl-2 col-sm-4">
                <select class="form-control" wire:model.live="status">
                    <option value="">All Statuses</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            
            <div class="col-xxl-2 col-sm-4 d-flex gap-2">
                <button type="button" class="btn btn-primary w-100" wire:click="refresh">
                    <i class="ri-refresh-line align-bottom me-1"></i> Refresh
                </button>
                @if(isset($exportAction))
                <button type="button" class="btn btn-soft-secondary w-100" wire:click="{{ $exportAction }}">
                    <i class="ri-file-download-line align-bottom me-1"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>
</div>
