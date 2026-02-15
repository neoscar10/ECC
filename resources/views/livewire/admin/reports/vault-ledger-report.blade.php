<div>
    <x-admin.reports.partials._report_header 
        title="Vault Ledger Report" 
        :backRoute="route('admin.reports.index')" 
        :breadcrumbs="['Vault' => '#']" 
    />

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Total Transactions" 
                :value="$kpis['total_transactions']" 
                icon="ri-exchange-line" 
                color="primary" 
                action="viewKpiDetails('total_transactions')" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Currently Locked" 
                :value="$kpis['currently_locked']" 
                icon="ri-lock-2-line" 
                color="success" 
                action="viewKpiDetails('currently_locked')" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Total Removed" 
                :value="$kpis['total_removed']" 
                icon="ri-delete-bin-line" 
                color="danger" 
                action="viewKpiDetails('total_removed')" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Unique Users" 
                :value="$kpis['unique_users']" 
                icon="ri-user-heart-line" 
                color="info" 
                link="#" 
            />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-xl-4">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Status Distribution</h4>
                </div>
                <div class="card-body">
                    <div id="vault_status_chart" wire:ignore style="min-height: 280px;"></div>
                    <div class="chart-empty-state d-none py-5 text-center">
                        <i class="ri-pie-chart-2-line display-4 text-light"></i>
                        <h5 class="mt-2 text-muted">No transactions found</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Vault Activity Trend</h4>
                </div>
                <div class="card-body">
                    <div id="vault_activity_chart" wire:ignore style="min-height: 280px;"></div>
                    <div class="chart-empty-state d-none py-5 text-center">
                        <i class="ri-pulse-line display-4 text-light"></i>
                        <h5 class="mt-2 text-muted">No activity in this period</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Filter Row -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="search-box">
                                <input type="text" class="form-control" placeholder="Search user or item..." wire:model.live.debounce.400ms="search">
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
                            <div class="dropdown">
                                <button class="btn btn-soft-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="ri-download-2-line align-bottom me-1"></i> Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" wire:click.prevent="export('current')">Current View</a></li>
                                    <li><a class="dropdown-item" href="#" wire:click.prevent="export('locked')">Locked Only</a></li>
                                    <li><a class="dropdown-item" href="#" wire:click.prevent="export('removed')">Removed Only</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
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
                                    <th>User</th>
                                    <th>Item Details</th>
                                    <th class="text-end">Price</th>
                                    <th>Status</th>
                                    <th>Locked At</th>
                                    <th>Removed At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                        {{ strtoupper(substr($item->user?->name ?? 'U', 0, 1)) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="fs-13 mb-0">{{ $item->user?->name }}</h5>
                                                <p class="text-muted mb-0 fs-12">{{ $item->user?->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <h5 class="fs-14 mb-1">{{ $item->item_title }}</h5>
                                        <span class="badge badge-soft-dark">{{ $item->item_ref }}</span>
                                    </td>
                                    <td class="text-end">
                                        @if($item->price > 0)
                                            ₹{{ number_format($item->price, 2) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->status === 'locked' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $item->locked_at ? $item->locked_at->format('d M, Y H:i') : 'N/A' }}</td>
                                    <td>
                                        @if($item->removed_at)
                                            {{ $item->removed_at->format('d M, Y H:i') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown">
                                                <i class="ri-more-fill align-middle"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.vault-access.show', $item->user_id) }}" wire:navigate>
                                                        <i class="ri-safe-2-line align-bottom me-2 text-muted"></i> View User Vault
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.users.admin') }}?search={{ urlencode($item->user?->email) }}" wire:navigate>
                                                        <i class="ri-user-line align-bottom me-2 text-muted"></i> View User
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="avatar-md mx-auto mb-3">
                                            <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-2">
                                                <i class="ri-safe-line"></i>
                                            </div>
                                        </div>
                                        <h5>No vault records found</h5>
                                        <p class="text-muted">Adjust your search or filters to see vault history.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.reports.partials._kpi_details_modal')

    @script
    <script>
        $wire.on('open-kpi-modal', () => {
            const modal = new bootstrap.Modal(document.getElementById('kpiDetailsModal'));
            modal.show();
        });

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
