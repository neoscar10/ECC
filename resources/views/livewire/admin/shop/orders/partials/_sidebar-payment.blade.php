@php
    $latestPayment = $this->order->latestPayment;
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Payment Details</h5>
        @if($latestPayment)
            @switch($latestPayment->status)
                @case('paid')
                    <span class="badge bg-success-subtle text-success text-uppercase">Paid</span>
                    @break
                @case('pending')
                    <span class="badge bg-warning-subtle text-warning text-uppercase">Pending</span>
                    @break
                @case('failed')
                    <span class="badge bg-danger-subtle text-danger text-uppercase">Failed</span>
                    @break
                @case('initiated')
                    <span class="badge bg-secondary-subtle text-secondary text-uppercase">Initiated</span>
                    @break
                @default
                    <span class="badge bg-light text-dark text-uppercase">{{ $latestPayment->status }}</span>
            @endswitch
        @else
            <span class="badge bg-light text-dark text-uppercase">No Payment Attempted</span>
        @endif
    </div>
    
    <div class="card-body">
        @if($latestPayment)
            <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0 table-sm">
                    <tbody>
                        <tr>
                            <td class="text-muted" style="width: 140px;">Payment ID</td>
                            <td class="fw-medium">#{{ $latestPayment->id }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gateway</td>
                            <td class="fw-medium text-capitalize">{{ $latestPayment->gateway }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Amount</td>
                            <td class="fw-medium text-danger">{{ $latestPayment->currency }} {{ number_format($latestPayment->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gateway Order ID</td>
                            <td class="fw-medium">
                                @if($latestPayment->gateway_order_id)
                                    <code class="text-dark">{{ $latestPayment->gateway_order_id }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gateway Payment ID</td>
                            <td class="fw-medium">
                                @if($latestPayment->gateway_payment_id)
                                    <code class="text-dark">{{ $latestPayment->gateway_payment_id }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Paid At</td>
                            <td class="fw-medium">
                                @if($latestPayment->paid_at)
                                    {{ $latestPayment->paid_at->format('d M Y, h:i A') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @if($latestPayment->status === 'failed' || $latestPayment->failure_message)
                            <tr>
                                <td class="text-muted text-danger">Failure Reason</td>
                                <td class="fw-medium text-danger">
                                    {{ $latestPayment->failure_message ?? 'Unknown gateway failure' }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Created At</td>
                            <td class="fw-medium">{{ $latestPayment->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-3 text-muted">
                <i class="ri-information-line fs-2 mb-2"></i>
                <p class="mb-0">No payment transaction records found for this order.</p>
                @if($this->order->payment_status !== 'paid')
                    <button wire:click="markPaid" class="btn btn-sm btn-success mt-3 w-100">
                        Mark as Paid (Manual)
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
