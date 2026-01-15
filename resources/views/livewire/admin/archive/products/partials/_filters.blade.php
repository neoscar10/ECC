<form>
    <div class="row g-3">

    <div class="col-xxl-3 col-sm-4">
        <div class="search-box">
            <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search products...">
            <i class="ri-search-line search-icon"></i>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-2">
        <div class="input-light">
            <select class="form-control" wire:model.live="filterCategory">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-2">
             <div class="input-light">
                <select class="form-control" wire:model.live="filterRestriction">
                    <option value="">All Visibilities</option>
                    <option value="public">Public</option>
                    <option value="restricted">Restricted</option>
                </select>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-2">
             <div class="input-light">
                <select class="form-control" wire:model.live="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="live">Live</option>
                    <option value="scheduled">Scheduled</option>
                </select>
            </div>
        </div>
        <div class="col-xxl-2 col-sm-2">
            <div class="input-light">
               <select class="form-control" wire:model.live="filterEarlyAccess">
                   <option value="">Early Access</option>
                   <option value="yes">Yes</option>
                   <option value="no">No</option>
               </select>
           </div>
       </div>
    </div>
</form>
