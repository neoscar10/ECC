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
                link="{{ route('admin.archive.orders.index') }}" 
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
                link="{{ route('admin.membership.applications') }}" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="New Enquiries" 
                :value="$kpis['new_enquiries'] ?? 0" 
                icon="ri-chat-voice-line" 
                color="danger" 
                link="{{ route('admin.enquiries.index') }}" 
            />
        </div>
    </div>

    <div class="row">
        <!-- Sales Trend Placeholder -->
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Sales Overview</h4>
                    <div>
                        <button type="button" class="btn btn-soft-secondary btn-sm">ALL</button>
                        <button type="button" class="btn btn-soft-secondary btn-sm">1M</button>
                        <button type="button" class="btn btn-soft-primary btn-sm">6M</button>
                    </div>
                </div>
                <div class="card-body p-0 pb-2">
                    <div class="w-100">
                        <div id="sales-overview-chart" data-colors='["--vz-primary", "--vz-success", "--vz-danger"]' class="apex-charts" dir="ltr" style="height: 370px;"></div>
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
                        <a href="{{ route('admin.membership.applications') }}" class="btn btn-soft-info btn-sm">View All</a>
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
                                            <a href="{{ route('admin.membership.applications') }}" class="btn btn-sm btn-soft-primary">Review</a>
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
                                            <a href="{{ route('admin.shop.inventory') }}" class="btn btn-sm btn-soft-primary">Restock</a>
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
</div>

@push('scripts')
<script>
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
                }
            },
            stroke: {
                curve: 'smooth',
                width: [2]
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
                size: 0
            },
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                title: {
                    text: 'Revenue in ₹',
                },
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (y) {
                        if (typeof y !== "undefined") {
                            return "₹ " + y.toFixed(0);
                        }
                        return y;
                    }
                }
            },
            colors: ["#405189"]
        };

        var chart = new ApexCharts(document.querySelector("#sales-overview-chart"), options);
        chart.render();
    }

    renderCharts();
</script>
@endpush