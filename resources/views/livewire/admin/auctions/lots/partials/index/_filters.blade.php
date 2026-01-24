<form>
    <div class="row g-3">
    <div class="col-xxl-4 col-sm-6">
        <div class="search-box">
            <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search lots...">
            <i class="ri-search-line search-icon"></i>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-3">
         <div class="input-light">
            <select class="form-control" wire:model.live="filterStatus">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="upcoming">Upcoming</option>
                <option value="live">Live</option>
                <option value="ended">Ended</option>
            </select>
        </div>
    </div>
    </div>
</form>
