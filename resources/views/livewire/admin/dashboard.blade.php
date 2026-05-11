<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Admin Dashboard</h4>
                <div class="page-title-right">
                    <button wire:click="refresh" class="btn btn-soft-info btn-sm">
                        <i class="ri-refresh-line align-bottom"></i> Refresh Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Total Revenue" 
                :value="$kpis['total_sales'] ?? 0" 
                prefix="₹" 
                icon="ri-money-dollar-circle-line" 
                color="success" 
                action="openRevenueModal" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Active Members" 
                :value="$kpis['active_members'] ?? 0" 
                icon="ri-user-star-line" 
                color="info" 
                link="{{ route('admin.membership.members') }}" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Pending Applications" 
                :value="$kpis['pending_applications'] ?? 0" 
                icon="ri-file-list-3-line" 
                color="warning" 
                link="{{ route('admin.membership.applications', ['statusFilter' => 'submitted']) }}" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="New Enquiries" 
                :value="$kpis['new_enquiries'] ?? 0" 
                icon="ri-chat-voice-line" 
                color="danger" 
                action="openEnquiriesModal" 
            />
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6">
            <x-admin.kpi-card 
                title="Total Shop Products" 
                :value="$kpis['total_shop_products'] ?? 0" 
                icon="ri-store-2-line" 
                color="primary" 
                link="{{ route('admin.shop.products') }}" 
            />
        </div>
        <div class="col-xl-4 col-md-6">
            <x-admin.kpi-card 
                title="Total Archive Items" 
                :value="$kpis['total_archive_items'] ?? 0" 
                icon="ri-archive-line" 
                color="secondary" 
                link="{{ route('admin.archive.products') }}" 
            />
        </div>
        <div class="col-xl-4 col-md-6">
            <x-admin.kpi-card 
                title="Total Auction Lots" 
                :value="$kpis['total_auction_lots'] ?? 0" 
                icon="ri-auction-line" 
                color="info" 
                link="{{ route('admin.auctions.lots.index') }}" 
            />
        </div>
    </div>

    <div class="row">
        <!-- Sales Trend Placeholder -->
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex flex-wrap gap-3">
                    <h4 class="card-title mb-0 flex-grow-1">Sales Overview</h4>
                    
                    <!-- Source Selector -->
                    <div class="flex-shrink-0">
                        <select wire:model.live="chartSource" class="form-select form-select-sm border-0 bg-light">
                            <option value="all">All Sources</option>
                            <option value="shop">Shop Sales</option>
                            <option value="other">Auctions & Archive</option>
                        </select>
                    </div>

                    <!-- Range Selector -->
                    <div class="flex-shrink-0">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" wire:click="$set('chartRange', 'today')" class="btn {{ $chartRange == 'today' ? 'btn-primary' : 'btn-soft-primary' }}">Today</button>
                            <button type="button" wire:click="$set('chartRange', '1w')" class="btn {{ $chartRange == '1w' ? 'btn-primary' : 'btn-soft-primary' }}">1W</button>
                            <button type="button" wire:click="$set('chartRange', '1m')" class="btn {{ $chartRange == '1m' ? 'btn-primary' : 'btn-soft-primary' }}">1M</button>
                            <button type="button" wire:click="$set('chartRange', 'custom')" class="btn {{ $chartRange == 'custom' ? 'btn-primary' : 'btn-soft-primary' }}">Custom</button>
                        </div>
                    </div>

                    @if($chartRange == 'custom')
                    <div class="flex-shrink-0 d-flex gap-2 align-items-center animate__animated animate__fadeIn">
                        <input type="date" wire:model.live="chartStartDate" class="form-control form-control-sm border-0 bg-light" style="width: 140px;">
                        <span class="text-muted">-</span>
                        <input type="date" wire:model.live="chartEndDate" class="form-control form-control-sm border-0 bg-light" style="width: 140px;">
                    </div>
                    @endif
                </div>
                <div class="card-body p-0 pb-2">
                    <div class="w-100" wire:ignore>
                        <div id="sales-overview-chart" class="apex-charts" dir="ltr" style="height: 370px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Pending Applications</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('admin.membership.applications', ['statusFilter' => 'submitted']) }}" class="btn btn-soft-info btn-sm">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Tier</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($queues['pending_applications'] as $app)
                                    <tr>
                                        <td>
                                                <div class="flex-grow-1">{{ $app->user?->name ?? 'Unknown User' }}</div>
                                        </td>
                                        <td>{{ $app->membershipTier?->name }}</td>
                                        <td>{{ $app->created_at->format('d M, Y') }}</td>
                                        <td>
                                            <button type="button" wire:click="view({{ $app->id }})" class="btn btn-sm btn-soft-primary">Review</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <p class="text-muted mb-0">No pending applications</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Recent New Enquiries</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-soft-info btn-sm">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th>Source</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($queues['new_enquiries'] as $enq)
                                    <tr>
                                        <td><span class="badge bg-info-subtle text-info">{{ $enq['type'] }}</span></td>
                                        <td>{{ Str::limit($enq['subject'], 30) }}</td>
                                        <td>{{ $enq['date']->diffForHumans() }}</td>
                                        <td>
                                            <a href="{{ $enq['route'] }}" class="btn btn-sm btn-soft-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <p class="text-muted mb-0">No new enquiries</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
             <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Low Stock Alerts</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('admin.shop.inventory') }}" class="btn btn-soft-info btn-sm">Manage Inventory</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Variation</th>
                                    <th>Current Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($queues['low_stock'] as $variation)
                                    <tr>
                                        <td>{{ $variation->group?->product?->title }}</td>
                                        <td>{{ $variation->caption }}</td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger">{{ $variation->stock_qty }} left</span>
                                        </td>
                                        <td>
                                            <a href="{{ $variation->restock_url ?? route('admin.shop.inventory') }}" class="btn btn-sm btn-soft-primary">Restock</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <p class="text-muted mb-0">Inventory is healthy</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('livewire.admin.membership.applications.partials._view-modal')
    @include('livewire.admin.membership.applications.partials._review-modals')

    <!-- Enquiries Breakdown Modal -->
    <div class="modal fade" id="enquiriesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header p-3 bg-light">
                    <h5 class="modal-title">New Enquiries Breakdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.archive.enquiries', ['status' => 'new']) }}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                            <div class="flex-shrink-0 avatar-xs">
                                <div class="avatar-title bg-info-subtle text-info rounded-circle">
                                    <i class="ri-archive-line"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 fw-semibold">Archive Enquiries</h6>
                                <p class="text-muted mb-0 small">Product and collection enquiries</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-info-subtle text-info fs-12">{{ $kpis['enquiry_breakdown']['archive'] ?? 0 }} New</span>
                            </div>
                        </a>
                        <a href="{{ route('admin.auctions.enquiries', ['status' => 'new']) }}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                            <div class="flex-shrink-0 avatar-xs">
                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                    <i class="ri-auction-line"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 fw-semibold">Auction Enquiries</h6>
                                <p class="text-muted mb-0 small">Bidding and lot enquiries</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-primary-subtle text-primary fs-12">{{ $kpis['enquiry_breakdown']['auction'] ?? 0 }} New</span>
                            </div>
                        </a>
                        <a href="{{ route('admin.enquiries.index', ['status' => 'new']) }}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                            <div class="flex-shrink-0 avatar-xs">
                                <div class="avatar-title bg-success-subtle text-success rounded-circle">
                                    <i class="ri-chat-voice-line"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 fw-semibold">General Enquiries</h6>
                                <p class="text-muted mb-0 small">General contact and platform enquiries</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-success-subtle text-success fs-12">{{ $kpis['enquiry_breakdown']['general'] ?? 0 }} New</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="modal-footer p-3 justify-content-center bg-light-subtle">
                    <p class="text-muted mb-0 small text-center">Click on a section to view and manage enquiries.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Breakdown Modal -->
    <div class="modal fade" id="revenueModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header p-3 bg-light">
                    <h5 class="modal-title">Revenue Breakdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.shop.orders', ['filterPaymentStatus' => 'paid']) }}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                            <div class="flex-shrink-0 avatar-xs">
                                <div class="avatar-title bg-success-subtle text-success rounded-circle">
                                    <i class="ri-shopping-cart-2-line"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 fw-semibold">Shop Revenue</h6>
                                <p class="text-muted mb-0 small">Direct platform sales</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-success-subtle text-success fs-14">₹ {{ number_format($kpis['revenue_breakdown']['shop'] ?? 0) }}</span>
                            </div>
                        </a>
                        <a href="{{ route('admin.archive.orders.index', ['status' => 'completed']) }}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                            <div class="flex-shrink-0 avatar-xs">
                                <div class="avatar-title bg-info-subtle text-info rounded-circle">
                                    <i class="ri-archive-line"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 fw-semibold">Archive Revenue</h6>
                                <p class="text-muted mb-0 small">Product enquiry conversions</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-info-subtle text-info fs-14">₹ {{ number_format($kpis['revenue_breakdown']['archive'] ?? 0) }}</span>
                            </div>
                        </a>
                        <a href="{{ route('admin.auctions.orders.index', ['status' => 'completed']) }}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                            <div class="flex-shrink-0 avatar-xs">
                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                    <i class="ri-auction-line"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 fw-semibold">Auction Revenue</h6>
                                <p class="text-muted mb-0 small">Lot bidding successes</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-primary-subtle text-primary fs-14">₹ {{ number_format($kpis['revenue_breakdown']['auction'] ?? 0) }}</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="modal-footer p-3 justify-content-center bg-light-subtle">
                    <p class="text-muted mb-0 small text-center">Click on a source to view corresponding paid orders.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var salesChart;
    
    document.addEventListener('livewire:navigated', function () {
        renderCharts();
    });

    function renderCharts() {
        var options = {
            series: [{
                name: 'Revenue',
                type: 'area',
                data: @json($kpis['sales_trend']['values'] ?? [])
            }],
            chart: {
                height: 370,
                type: 'line',
                toolbar: {
                    show: false,
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                }
            },
            stroke: {
                curve: 'smooth',
                width: [3]
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    inverseColors: false,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [20, 100, 100, 100]
                },
            },
            labels: @json($kpis['sales_trend']['labels'] ?? []),
            markers: {
                size: 0,
                strokeWidth: 2,
                hover: {
                    size: 6,
                }
            },
            xaxis: {
                type: '{{ ($kpis['sales_trend']['is_hourly'] ?? false) ? 'category' : 'datetime' }}',
                tickAmount: 8,
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                }
            },
            yaxis: {
                title: {
                    text: 'Revenue in ₹',
                    style: {
                        color: '#878a99',
                        fontWeight: 500,
                    }
                },
                labels: {
                    formatter: function (y) {
                        return "₹ " + y.toLocaleString();
                    }
                }
            },
            grid: {
                show: true,
                borderColor: '#f1f1f1',
                padding: {
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 10
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (y) {
                        if (typeof y !== "undefined") {
                            return "₹ " + y.toLocaleString();
                        }
                        return y;
                    }
                }
            },
            colors: ["#405189"]
        };

        if (salesChart) {
            salesChart.destroy();
        }
        
        var chartEl = document.querySelector("#sales-overview-chart");
        if (chartEl) {
            salesChart = new ApexCharts(chartEl, options);
            salesChart.render();
        }
    }

    renderCharts();

    document.addEventListener('livewire:initialized', () => {
        // Handle chart data updates
        Livewire.on('chartDataUpdated', (eventData) => {
            var data = eventData[0];
            if (salesChart) {
                salesChart.updateOptions({
                    xaxis: {
                        type: data.is_hourly ? 'category' : 'datetime',
                        categories: data.labels,
                        tickAmount: 8
                    },
                    series: [{
                        data: data.values
                    }]
                });
            }
        });

        // Handle enquiries modal
        window.addEventListener('open-enquiries-modal', event => {
            var modalEl = document.getElementById('enquiriesModal');
            if (modalEl) {
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        });

        // Handle revenue modal
        window.addEventListener('open-revenue-modal', event => {
            var modalEl = document.getElementById('revenueModal');
            if (modalEl) {
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        });
    });
</script>
@include('livewire.admin.membership.applications.partials._scripts')
@endpush