<div>
    <x-admin.reports.partials._report_header 
        title="Membership Report" 
        :backRoute="route('admin.reports.index')" 
        :breadcrumbs="['Membership' => '#']" 
    />

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card title="Total Members" :value="$kpis['total']" icon="ri-group-line" color="info" action="viewKpiDetails('total')" />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card title="Active Members" :value="$kpis['active']" icon="ri-user-follow-line" color="success" action="viewKpiDetails('active')" />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card title="Expired Members" :value="$kpis['expired']" icon="ri-user-unfollow-line" color="danger" action="viewKpiDetails('expired')" />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card title="Expiring Soon" :value="$kpis['expiring_soon']" icon="ri-time-line" color="warning" action="viewKpiDetails('expiring_soon')" />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-xl-4">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Members by Tier</h4>
                </div>
                <div class="card-body position-relative">
                    <div id="membersByTierChart" wire:ignore style="min-height: 280px;"></div>
                    <div class="chart-empty-state d-none py-5 text-center">
                        <i class="ri-pie-chart-line display-4 text-light"></i>
                        <h5 class="mt-2 text-muted">No data found</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Status Breakdown</h4>
                </div>
                <div class="card-body position-relative">
                    <div id="statusBreakdownChart" wire:ignore style="min-height: 280px;"></div>
                    <div class="chart-empty-state d-none py-5 text-center">
                        <i class="ri-donut-chart-line display-4 text-light"></i>
                        <h5 class="mt-2 text-muted">No data found</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Signups Trend</h4>
                </div>
                <div class="card-body position-relative">
                    <div id="signupsTrendChart" wire:ignore style="min-height: 280px;"></div>
                    <div class="chart-empty-state d-none py-5 text-center">
                        <i class="ri-line-chart-line display-4 text-light"></i>
                        <h5 class="mt-2 text-muted">No data found</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                @if($expiringSoonOnly)
                <div class="col-12">
                    <div class="alert alert-warning alert-dismissible fade show mb-0 py-2" role="alert">
                        <strong>Expiring soon filter active!</strong> Showing members expiring within 30 days.
                        <button type="button" class="btn-close py-2" wire:click="clearKpiFilter" aria-label="Close"></button>
                    </div>
                </div>
                @endif
                <div class="col-lg-3">
                    <div class="search-box">
                        <input type="text" class="form-control" placeholder="Search name or email..." wire:model.live.debounce.400ms="search">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
                <div class="col-lg-2">
                    <input type="date" class="form-control" wire:model.live="startDate">
                </div>
                <div class="col-lg-2">
                    <input type="date" class="form-control" wire:model.live="endDate">
                </div>
                <div class="col-lg-2">
                    <select class="form-select" wire:model.live="status">
                        <option value="">All Statuses</option>
                        @foreach($statusOptions as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <select class="form-select" wire:model.live="tierId">
                        <option value="">All Tiers</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1">
                    <div class="btn-group w-100">
                        <button type="button" class="btn btn-soft-primary dropdown-toggle w-100" data-bs-toggle="dropdown">
                            <i class="ri-download-2-line"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Export Data</h6>
                            <a class="dropdown-item" href="javascript:void(0);" wire:click="export('current')">Current View</a>
                            <a class="dropdown-item" href="javascript:void(0);" wire:click="export('tier_summary')">Tier Summary</a>
                            <a class="dropdown-item" href="javascript:void(0);" wire:click="export('status_summary')">Status Summary</a>
                            <a class="dropdown-item" href="javascript:void(0);" wire:click="export('expiring_soon')">Expiring Soon (30d)</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="reportTableSection">
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
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($memberships as $m)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                                        {{ strtoupper(substr($m->user?->name ?? '?', 0, 1)) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="fs-14 mb-0">{{ $m->user?->name }}</h5>
                                                <p class="text-muted mb-0 fs-12">{{ $m->user?->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-info text-info">{{ $m->membershipTier?->name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $m->status === 'active' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} text-uppercase">
                                            {{ ucfirst($m->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $m->started_at ? $m->started_at->format('d M, Y') : 'N/A' }}</td>
                                    <td>
                                        @if($m->expires_at)
                                            @php
                                                $daysLeft = $m->expires_at->diffInDays(now(), false);
                                                $isWarning = $daysLeft > -30 && $m->status === 'active';
                                            @endphp
                                            <span class="{{ $isWarning ? 'text-warning fw-medium' : '' }}">
                                                {{ $m->expires_at->format('d M, Y') }}
                                                @if($isWarning) <i class="ri-error-warning-line align-bottom" title="Expiring soon"></i> @endif
                                            </span>
                                        @else
                                            <span class="badge bg-soft-info text-info">Lifetime</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown">
                                                <i class="ri-more-fill align-middle"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.membership.members', ['search' => $m->user?->email]) }}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View Member</a></li>
                                                <li><a class="dropdown-item" href="#"><i class="ri-edit-fill align-bottom me-2 text-muted"></i> Edit Membership</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="avatar-md mx-auto mb-4">
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

    @script
    <script>
        $wire.on('report:scrollToTable', () => {
            const el = document.getElementById('reportTableSection');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        });

        // Initial load
        document.addEventListener('livewire:initialized', () => {
            $wire.call('refresh');
        });
    </script>
    @endscript
</div>
