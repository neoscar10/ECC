<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Payment Operations Dashboard</h4>
                <div class="page-title-right">
                    <button wire:click="refresh" class="btn btn-soft-info btn-sm">
                        <i class="ri-refresh-line align-bottom"></i> Refresh Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats row -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Total Revenue" 
                :value="$metrics['total_revenue']" 
                prefix="₹" 
                icon="ri-money-dollar-circle-line" 
                color="success" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Revenue Today" 
                :value="$metrics['revenue_today']" 
                prefix="₹" 
                icon="ri-calendar-event-line" 
                color="info" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="This Month" 
                :value="$metrics['revenue_this_month']" 
                prefix="₹" 
                icon="ri-calendar-line" 
                color="primary" 
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-admin.kpi-card 
                title="Success Rate %" 
                :value="$metrics['success_rate']" 
                suffix="%" 
                icon="ri-checkbox-circle-line" 
                color="warning" 
            />
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Successful Payments</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">{{ number_format($metrics['successful_count']) }}</span></h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded-circle fs-3"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Failed Payments</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">{{ number_format($metrics['failed_count']) }}</span></h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-3"><i class="ri-close-circle-line text-danger"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Pending Payments</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">{{ number_format($metrics['pending_count']) }}</span></h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded-circle fs-3"><i class="ri-time-line text-warning"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Avg Transaction Value</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0">₹<span class="counter-value">{{ number_format($metrics['avg_transaction_value'], 2) }}</span></h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded-circle fs-3"><i class="ri-wallet-line text-info"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue break downs -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Revenue by Gateway</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th>Gateway</th>
                                    <th class="text-end">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($revenueByGateway as $row)
                                    <tr>
                                        <td><strong>{{ $row['label'] }}</strong></td>
                                        <td class="text-end">₹{{ number_format($row['value'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">No records found</td>
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
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Revenue by Payment Purpose</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th>Purpose</th>
                                    <th class="text-end">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($revenueByPurpose as $row)
                                    <tr>
                                        <td><strong>{{ $row['label'] }}</strong></td>
                                        <td class="text-end">₹{{ number_format($row['value'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">No records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Revenue Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Daily Revenue Trend (Last 30 Days)</h4>
                </div>
                <div class="card-body p-0 pb-2">
                    <div class="w-100" wire:ignore>
                        <div id="payments-daily-chart" class="apex-charts" dir="ltr" style="height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var dailyChart;
    
    document.addEventListener('livewire:navigated', function () {
        renderDailyChart();
    });

    function renderDailyChart() {
        var options = {
            series: [{
                name: 'Revenue',
                type: 'area',
                data: @json($trend['values'] ?? [])
            }],
            chart: {
                height: 350,
                type: 'line',
                toolbar: { show: false }
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
            labels: @json($trend['labels'] ?? []),
            xaxis: {
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: function (y) {
                        return "₹ " + y.toLocaleString();
                    }
                }
            },
            colors: ["#405189"]
        };

        if (dailyChart) {
            dailyChart.destroy();
        }
        
        var chartEl = document.querySelector("#payments-daily-chart");
        if (chartEl) {
            dailyChart = new ApexCharts(chartEl, options);
            dailyChart.render();
        }
    }

    renderDailyChart();

    document.addEventListener('livewire:initialized', () => {
        Livewire.on('refreshChart', () => {
            renderDailyChart();
        });
    });
</script>
@endpush
