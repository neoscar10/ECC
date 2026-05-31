<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Payment Transaction Logs</h4>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search User</label>
                    <input type="text" wire:model.live="searchUser" class="form-control" placeholder="Name or email...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Gateway</label>
                    <select wire:model.live="filterGateway" class="form-select">
                        <option value="">All</option>
                        <option value="razorpay">Razorpay</option>
                        <option value="cashfree">Cashfree</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Purpose</label>
                    <select wire:model.live="filterPurpose" class="form-select">
                        <option value="">All</option>
                        <option value="shop_order">Shop Order</option>
                        <option value="membership_upgrade">Membership Upgrade</option>
                        <option value="membership_renewal">Membership Renewal</option>
                        <option value="vault_delivery">Vault Delivery</option>
                        <option value="auction_settlement">Auction Settlement</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select wire:model.live="filterStatus" class="form-select">
                        <option value="">All</option>
                        <option value="initiated">Initiated</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button wire:click="resetFilters" class="btn btn-light w-100"><i class="ri-refresh-line align-bottom me-1"></i> Reset Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead class="text-muted table-light">
                        <tr>
                            <th>Payment ID</th>
                            <th>User</th>
                            <th>Purpose</th>
                            <th>Gateway</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td><strong>#{{ $payment->id }}</strong></td>
                                <td>{{ $payment->user?->name ?? 'Guest User' }}<br><small class="text-muted">{{ $payment->user?->email }}</small></td>
                                <td><span class="badge bg-info-subtle text-info">{{ ucfirst(str_replace('_', ' ', $payment->purpose)) }}</span></td>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($payment->gateway) }}</span></td>
                                <td>₹{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $payment->status === 'paid' ? 'success' : ($payment->status === 'failed' ? 'danger' : 'warning') }}">
                                        {{ strtoupper($payment->status) }}
                                    </span>
                                </td>
                                <td>{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    <button wire:click="selectPayment({{ $payment->id }})" class="btn btn-sm btn-soft-primary">
                                        <i class="ri-eye-line align-bottom"></i> View Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">No records found matching filters.</td>
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

    <!-- Transaction Detail Modal -->
    <div wire:ignore.self class="modal fade" id="paymentDetailModal" tabindex="-1" aria-labelledby="paymentDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                @if($selectedPayment)
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentDetailModalLabel">Payment Transaction Details #{{ $selectedPayment->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="$dispatch('close-payment-modal')"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted fs-11 text-uppercase">Payment Info</label>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td>Amount:</td>
                                        <td><strong>₹{{ number_format($selectedPayment->amount, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Purpose:</td>
                                        <td><code>{{ $selectedPayment->purpose }}</code></td>
                                    </tr>
                                    <tr>
                                        <td>Status:</td>
                                        <td><span class="badge bg-{{ $selectedPayment->status === 'paid' ? 'success' : 'danger' }}">{{ strtoupper($selectedPayment->status) }}</span></td>
                                    </tr>
                                    @if($selectedPayment->paid_at)
                                        <tr>
                                            <td>Paid At:</td>
                                            <td>{{ $selectedPayment->paid_at->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted fs-11 text-uppercase">Gateway Info</label>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td>Gateway:</td>
                                        <td><strong>{{ ucfirst($selectedPayment->gateway) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Gateway Order ID:</td>
                                        <td><code>{{ $selectedPayment->gateway_order_id }}</code></td>
                                    </tr>
                                    <tr>
                                        <td>Gateway Transaction ID:</td>
                                        <td><code>{{ $selectedPayment->gateway_payment_id }}</code></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if($selectedPayment->status === 'failed')
                            <div class="alert alert-danger mt-3 mb-0">
                                <strong>Failure Details:</strong><br>
                                Code: <code>{{ $selectedPayment->failure_code }}</code><br>
                                Message: {{ $selectedPayment->failure_message }}
                            </div>
                        @endif

                        <h6 class="mt-4 mb-2 text-uppercase text-muted fs-11">Events & Webhook History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Event Type</th>
                                        <th>Gateway Event ID</th>
                                        <th>Signature Valid</th>
                                        <th>Processed At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedPayment->events as $event)
                                        <tr>
                                            <td><code>{{ $event->event_type }}</code></td>
                                            <td><code>{{ $event->gateway_event_id }}</code></td>
                                            <td><span class="badge bg-{{ $event->signature_valid ? 'success' : 'danger' }}">{{ $event->signature_valid ? 'Yes' : 'No' }}</span></td>
                                            <td>{{ $event->processed_at->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No webhook/gateway events logged yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <h6 class="mt-4 mb-2 text-uppercase text-muted fs-11">Metadata (JSON)</h6>
                        <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;"><code>{{ json_encode($selectedPayment->meta, JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                @endif
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="$dispatch('close-payment-modal')">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    $wire.on('open-payment-modal', () => {
        var modal = new bootstrap.Modal(document.getElementById('paymentDetailModal'));
        modal.show();
    });

    $wire.on('close-payment-modal', () => {
        var modalEl = document.getElementById('paymentDetailModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    });
</script>
@endscript
