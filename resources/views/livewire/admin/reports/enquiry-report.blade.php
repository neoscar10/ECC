<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Enquiries Analysis Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                        <li class="breadcrumb-item active">Enquiries</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <x-admin.kpi-card 
                title="Total Enquiries in View" 
                :value="$totalCount" 
                icon="ri-chat-voice-line" 
                color="danger" 
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
                                    <th>Source</th>
                                    <th>Subject / Contact</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($enquiries as $enq)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($enq->created_at)->format('d M, Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{ $enq->type === 'archive' ? 'bg-primary' : ($enq->type === 'auction' ? 'bg-info' : 'bg-secondary') }}">
                                            {{ ucfirst($enq->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $enq->subject }}</td>
                                    <td>
                                        <span class="badge {{ $enq->status === 'new' ? 'bg-danger' : 'bg-success' }}">
                                            {{ ucfirst($enq->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="avatar-md mx-auto mb-3">
                                            <div class="avatar-title bg-light text-primary rounded-circle fs-3">
                                                <i class="ri-chat-history-line"></i>
                                            </div>
                                        </div>
                                        <h5>No enquiries found</h5>
                                        <p class="text-muted">Try adjusting your filters.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $enquiries->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
