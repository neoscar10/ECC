<div class="card-header border-0">
    <div class="row g-4 align-items-center">
        <div class="col-sm-3">
            <div class="search-box">
                <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search applicant...">
                <i class="ri-search-line search-icon"></i>
            </div>
        </div>
        <div class="col-sm-auto ms-auto">
            <div class="hstack gap-2">
                <select class="form-select" wire:model.live="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="under_review">Under Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
    </div>
</div>
