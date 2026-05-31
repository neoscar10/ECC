<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Payment Configuration Audit Logs</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Configuration Actions Activity Trail</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-nowrap mb-0">
                    <thead class="text-muted table-light">
                        <tr>
                            <th>ID</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>IP Address</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $audit)
                            <tr>
                                <td><strong>#{{ $audit->id }}</strong></td>
                                <td>{{ $audit->admin?->name ?? 'System' }}<br><small class="text-muted">{{ $audit->admin?->email }}</small></td>
                                <td><span class="badge bg-primary-subtle text-primary">{{ strtoupper($audit->action) }}</span></td>
                                <td><code>{{ $audit->ip_address }}</code></td>
                                <td>
                                    @if($audit->old_value)
                                        <pre class="mb-0 bg-light p-1 rounded" style="font-size: 11px;"><code>{{ json_encode($audit->old_value) }}</code></pre>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($audit->new_value)
                                        <pre class="mb-0 bg-light p-1 rounded" style="font-size: 11px;"><code>{{ json_encode($audit->new_value) }}</code></pre>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $audit->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No audit logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $audits->links() }}
            </div>
        </div>
    </div>
</div>
