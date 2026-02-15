<div>
    <x-admin.reports.partials._report_header 
        title="Sales Report" 
        :backRoute="route('admin.reports.index')" 
        :breadcrumbs="['Sales' => '#']" 
    />

    <div class="row">
        <div class="col-xl-6">
            <x-admin.kpi-card 
                title="Total Orders" 
                :value="$kpis['total_count']" 
                icon="ri-shopping-bag-line" 
                color="primary" 
                action="viewKpiDetails('total_orders')" 
            />
        </div>
        <div class="col-xl-6">
            <x-admin.kpi-card 
                title="Total Revenue" 
                :value="$kpis['total_revenue']" 
                prefix="₹" 
                icon="ri-money-dollar-circle-line" 
                color="success" 
                action="viewKpiDetails('total_revenue')" 
            />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Orders by Source</h4>
                </div>
                <div class="card-body position-relative">
                    <div id="orders_source_chart" wire:ignore style="min-height: 280px;"></div>
                    <div class="chart-empty-state d-none py-5 text-center">
                        <i class="ri-shopping-bag-3-line display-4 text-light"></i>
                        <h5 class="mt-2 text-muted">No data for selected range</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Revenue by Source (INR)</h4>
                </div>
                <div class="card-body position-relative">
                    <div id="revenue_source_chart" wire:ignore style="min-height: 280px;"></div>
                    <div class="chart-empty-state d-none py-5 text-center">
                        <i class="ri-money-dollar-circle-line display-4 text-light"></i>
                        <h5 class="mt-2 text-muted">No data for selected range</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="search-box">
                                <input type="text" class="form-control" placeholder="Search order # or customer..." wire:model.live.debounce.400ms="search">
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
                            <select class="form-select" wire:model.live="source">
                                <option value="all">All Sources</option>
                                <option value="shop">Shop</option>
                                <option value="auction">Auction</option>
                                <option value="archive">Archive</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-primary dropdown-toggle w-100" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="ri-download-2-line align-bottom me-1"></i> Download
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <h6 class="dropdown-header">Export Sales Data</h6>
                                    <a class="dropdown-item" href="javascript:void(0);" wire:click="export('current')">Current Filters</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="javascript:void(0);" wire:click="export('all')">Export All</a>
                                    <a class="dropdown-item" href="javascript:void(0);" wire:click="export('shop')">Shop Summary</a>
                                    <a class="dropdown-item" href="javascript:void(0);" wire:click="export('auction')">Auction Summary</a>
                                    <a class="dropdown-item" href="javascript:void(0);" wire:click="export('archive')">Archive Summary</a>
                                </div>
                            </div>
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
                                    <th>Date</th>
                                    <th>Order #</th>
                                    <th>Source</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $sale)
                                <tr>
                                    <td>{{ $sale->paid_at ? \Carbon\Carbon::parse($sale->paid_at)->format('d M, Y H:i') : 'N/A' }}</td>
                                    <td><span class="fw-medium">{{ $sale->order_number }}</span></td>
                                    <td>
                                        <span class="badge {{ $sale->source === 'shop' ? 'bg-primary' : ($sale->source === 'auction' ? 'bg-info' : 'bg-secondary') }}">
                                            {{ ucfirst($sale->source) }}
                                        </span>
                                    </td>
                                    <td>{{ $sale->customer_name }}</td>
                                    <td>₹{{ number_format($sale->total_amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success text-uppercase">{{ $sale->status }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="avatar-md mx-auto mb-3">
                                            <div class="avatar-title bg-light text-primary rounded-circle fs-3">
                                                <i class="ri-search-line"></i>
                                            </div>
                                        </div>
                                        <h5>No sales records found</h5>
                                        <p class="text-muted">Adjust your filters or try a different date range.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $sales->links() }}
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
            document.getElementById('reportTableSection').scrollIntoView({ behavior: 'smooth' });
        });

        // Initial load
        document.addEventListener('livewire:initialized', () => {
            $wire.call('refresh');
        });
    </script>
    @endscript
</div>
