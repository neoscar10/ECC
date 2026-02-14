<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Membership Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                        <li class="breadcrumb-item active">Membership</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <x-admin.kpi-card 
                title="Total Members in View" 
                :value="$totalCount" 
                icon="ri-user-star-line" 
                color="info" 
                link="#" 
            />
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-xxl-3 col-sm-6">
                    <div class="search-box">
                        <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search by name or email...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>

                <div class="col-xxl-2 col-sm-6">
                    <input type="date" class="form-control" wire:model.live="startDate" placeholder="Start Date">
                </div>
                <div class="col-xxl-2 col-sm-6">
                    <input type="date" class="form-control" wire:model.live="endDate" placeholder="End Date">
                </div>

                <div class="col-xxl-2 col-sm-6">
                    <select class="form-control" wire:model.live="status">
                        <option value="">All Statuses</option>
                        @foreach($statusOptions as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xxl-2 col-sm-6">
                    <select class="form-control" wire:model.live="tierId">
                        <option value="">All Tiers</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-xxl-1 col-sm-6 d-flex gap-2">
                    <button type="button" class="btn btn-soft-secondary w-100" wire:click="export">
                        <i class="ri-file-download-line align-bottom"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Tier</th>
                                    <th>Status</th>
                                    <th>Joined At</th>
                                    <th>Expires At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($memberships as $m)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="fs-14 mb-1">{{ $m->user?->name }}</h5>
                                                <p class="text-muted mb-0">{{ $m->user?->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $m->membershipTier?->name }}</td>
                                    <td>
                                        <span class="badge {{ $m->status === 'active' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($m->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $m->started_at ? $m->started_at->format('d M, Y') : 'N/A' }}</td>
                                    <td>
                                        @if($m->expires_at)
                                            {{ $m->expires_at->format('d M, Y') }}
                                        @else
                                            <span class="text-info">Lifetime</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="avatar-md mx-auto mb-3">
                                            <div class="avatar-title bg-light text-primary rounded-circle fs-3">
                                                <i class="ri-user-search-line"></i>
                                            </div>
                                        </div>
                                        <h5>No members found</h5>
                                        <p class="text-muted">Adjust your filters to see more results.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $memberships->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
