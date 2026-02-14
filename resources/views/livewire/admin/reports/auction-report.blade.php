<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Auction Performance Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                        <li class="breadcrumb-item active">Auctions</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <x-admin.kpi-card 
                title="Total Lots in View" 
                :value="$totalLots" 
                icon="ri-auction-line" 
                color="primary" 
                link="#" 
            />
        </div>
        <div class="col-xl-6">
            <x-admin.kpi-card 
                title="Total Engagement (Bids)" 
                :value="$totalBids" 
                icon="ri-hammer-line" 
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
                                        @if($lot->current_highest_bid > 0)
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
</div>
