<div>
    <x-admin.reports.partials._report_header 
        title="Auction Performance Report" 
        :backRoute="route('admin.reports.index')" 
        :breadcrumbs="['Auctions' => '#']" 
    />

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Total Lots" 
                :value="$kpis['total_lots']" 
                icon="ri-auction-line" 
                color="primary" 
                action="viewKpiDetails('total_lots')" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Total Engagement" 
                :value="$kpis['total_bids']" 
                icon="ri-hammer-line" 
                color="warning" 
                action="viewKpiDetails('total_bids')" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Auction Revenue" 
                :value="$kpis['total_revenue']" 
                prefix="₹"
                icon="ri-money-dollar-circle-line" 
                color="success" 
                action="viewKpiDetails('total_revenue')" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Success Rate" 
                :value="$kpis['success_rate']" 
                suffix="%"
                icon="ri-checkbox-circle-line" 
                color="info" 
                action="viewKpiDetails('success_rate')" 
            />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-xl-4">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Lots by Status</h4>
                </div>
                <div class="card-body">
                    <div id="status_donut_chart" wire:ignore style="min-height: 280px;"></div>
                    <div class="chart-empty-state d-none py-5 text-center">
                        <i class="ri-pie-chart-line display-4 text-light"></i>
                        <h5 class="mt-2 text-muted">No data available</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-height-100" id="reportTrendSection">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Bids Trend</h4>
                </div>
                <div class="card-body">
                    <div id="bids_trend_chart" wire:ignore style="min-height: 280px;"></div>
                    <div class="chart-empty-state d-none py-5 text-center">
                        <i class="ri-pulse-line display-4 text-light"></i>
                        <h5 class="mt-2 text-muted">No bids recorded in this period</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Filter Row -->
        <div class="col-lg-12" id="reportTableSection">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="search-box">
                                <input type="text" class="form-control" placeholder="Search lot or ref #..." wire:model.live.debounce.400ms="search">
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
                            <button type="button" class="btn btn-primary w-100" wire:click="export">
                                <i class="ri-download-2-line align-bottom me-1"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Lot Details</th>
                                    <th>Status</th>
                                    <th>Start Price</th>
                                    <th>Highest Bid</th>
                                    <th>Bids</th>
                                    <th>End Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lots as $lot)
                                <tr>
                                    <td>
                                        <h5 class="fs-14 mb-1">{{ $lot->title }}</h5>
                                        <p class="text-muted mb-0">{{ $lot->reference_number }}</p>
                                    </td>
                                    <td>
                                        <span class="badge {{ $lot->status === 'live' ? 'bg-success' : ($lot->status === 'ended' ? 'bg-primary' : 'bg-warning') }}">
                                            {{ ucfirst($lot->status) }}
                                        </span>
                                    </td>
                                    <td>₹{{ number_format($lot->starting_price, 2) }}</td>
                                    <td>
                                        @if(($lot->current_highest_bid ?? 0) > 0)
                                            ₹{{ number_format($lot->current_highest_bid, 2) }}
                                        @else
                                            <span class="text-muted">No bids</span>
                                        @endif
                                    </td>
                                    <td>{{ $lot->bids_count }}</td>
                                    <td>{{ $lot->ends_at ? $lot->ends_at->format('d M, Y H:i') : 'N/A' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="avatar-md mx-auto mb-3">
                                            <div class="avatar-title bg-light text-primary rounded-circle fs-3">
                                                <i class="ri-auction-line"></i>
                                            </div>
                                        </div>
                                        <h5>No auction lots found</h5>
                                        <p class="text-muted">Try changing your filters or status selection.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $lots->links() }}
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

        $wire.on('report:scrollToTrend', () => {
            const el = document.getElementById('reportTrendSection');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        });

        // Initial load
        document.addEventListener('livewire:initialized', () => {
            $wire.call('refresh');
        });
    </script>
    @endscript
</div>
