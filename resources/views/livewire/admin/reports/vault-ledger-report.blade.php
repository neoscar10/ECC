<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Vault Ledger Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                        <li class="breadcrumb-item active">Vault Ledger</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <x-admin.kpi-card 
                title="Total Vault Transactions in View" 
                :value="$totalItems" 
                icon="ri-safe-2-line" 
                color="warning" 
                link="#" 
            />
        </div>
    </div>

    <x-admin.report-filters exportAction="export" :statusOptions="$statusOptions" />

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
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Locked At</th>
                                    <th>Removed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                <tr>
                                    <td>{{ $item->user?->name }}</td>
                                    <td>
                                        <h5 class="fs-14 mb-1">{{ $item->item_title }}</h5>
                                        <p class="text-muted mb-0">{{ $item->item_ref }}</p>
                                    </td>
                                    <td>{{ number_format($item->price, 2) }} {{ $item->currency }}</td>
                                    <td>
                                        <span class="badge {{ $item->status === 'locked' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $item->locked_at ? $item->locked_at->format('d M, Y H:i') : 'N/A' }}</td>
                                    <td>{{ $item->removed_at ? $item->removed_at->format('d M, Y H:i') : '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="avatar-md mx-auto mb-3">
                                            <div class="avatar-title bg-light text-primary rounded-circle fs-3">
                                                <i class="ri-safe-line"></i>
                                            </div>
                                        </div>
                                        <h5>No vault records found</h5>
                                        <p class="text-muted">Adjust filters to see historical vault activity.</p>
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
</div>
