<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Sales Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                        <li class="breadcrumb-item active">Sales</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <x-admin.kpi-card 
                title="Total Orders" 
                :value="$totalCount" 
                icon="ri-shopping-bag-line" 
                color="primary" 
                link="#" 
            />
        </div>
        <div class="col-xl-6">
            <x-admin.kpi-card 
                title="Total Revenue" 
                :value="$totalRevenue" 
                prefix="₹" 
                icon="ri-money-dollar-circle-line" 
                color="success" 
                link="#" 
            />
        </div>
    </div>

    <x-admin.report-filters exportAction="export" />

    <div class="row">
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
</div>
