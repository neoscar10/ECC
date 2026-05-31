<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Failed Payment Center</h4>
            </div>
        </div>
    </div>

    <!-- Analytics row -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Most Common Failure Reasons</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <thead>
                                <tr class="text-muted table-light">
                                    <th>Reason</th>
                                    <th class="text-end">Occurrences</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commonFailures as $row)
                                    <tr>
                                        <td><code>{{ $row['failure_message'] }}</code></td>
                                        <td class="text-end font-semibold">{{ $row['count'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">No failure data logged yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Failure Rate by Gateway</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <thead>
                                <tr class="text-muted table-light">
                                    <th>Gateway</th>
                                    <th>Failed count</th>
                                    <th class="text-end">Failure Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($failureRates as $row)
                                    <tr>
                                        <td><strong>{{ $row['gateway'] }}</strong></td>
                                        <td>{{ $row['failed'] }}</td>
                                        <td class="text-end text-danger font-semibold">{{ $row['rate'] }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No gateway data logged yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table list -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Failed Payments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-nowrap mb-0">
                    <thead class="text-muted table-light">
                        <tr>
                            <th>Payment ID</th>
                            <th>User</th>
                            <th>Purpose</th>
                            <th>Gateway</th>
                            <th>Amount</th>
                            <th>Failure Code</th>
                            <th>Failure Message</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td><strong>#{{ $payment->id }}</strong></td>
                                <td>{{ $payment->user?->name ?? 'Guest' }}<br><small class="text-muted">{{ $payment->user?->email }}</small></td>
                                <td><span class="badge bg-warning-subtle text-warning">{{ ucfirst(str_replace('_', ' ', $payment->purpose)) }}</span></td>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($payment->gateway) }}</span></td>
                                <td>₹{{ number_format($payment->amount, 2) }}</td>
                                <td><code>{{ $payment->failure_code }}</code></td>
                                <td><span class="text-danger">{{ $payment->failure_message }}</span></td>
                                <td>{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">No failed payments.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</div>
