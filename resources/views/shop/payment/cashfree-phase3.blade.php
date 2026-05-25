<x-layouts.web-app title="Cashfree Payment — Session Created">
    <div class="ecc-container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

                {{-- Phase 3 Banner --}}
                <div class="alert alert-info border-0 mb-4 d-flex align-items-start gap-3">
                    <i class="mdi mdi-flask-outline fs-4 mt-1"></i>
                    <div>
                        <strong>Developer Debug View — Phase 3</strong><br>
                        <small class="text-muted">
                            Cashfree order/session created successfully. The full payment checkout UI (Cashfree SDK integration)
                            will be implemented in Phase 4. This page confirms the backend session creation is working.
                        </small>
                    </div>
                </div>

                {{-- Session Created Card --}}
                <div class="card bg-dark text-white shadow border-success mb-4">
                    <div class="card-header border-success d-flex align-items-center gap-2">
                        <i class="mdi mdi-check-circle text-success fs-5"></i>
                        <strong>Cashfree Order / Payment Session Created</strong>
                        <span class="badge bg-{{ $environment === 'production' ? 'danger' : 'warning' }} ms-auto">
                            {{ strtoupper($environment) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <table class="table table-dark table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width: 40%">Internal Payment ID</td>
                                    <td><strong>#{{ $payment->id }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Gateway</td>
                                    <td><span class="badge bg-primary">cashfree</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Cashfree Order ID</td>
                                    <td><code>{{ $payment->gateway_order_id ?? 'N/A' }}</code></td>
                                </tr>
                                @if($cfOrderId)
                                <tr>
                                    <td class="text-muted">cf_order_id</td>
                                    <td><code>{{ $cfOrderId }}</code></td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="text-muted">Payment Session ID</td>
                                    <td>
                                        @if($paymentSessionId)
                                            <code class="text-success">{{ Str::limit($paymentSessionId, 60, '...') }}</code>
                                        @else
                                            <span class="text-danger">Missing</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Amount</td>
                                    <td><strong>₹{{ number_format($payment->amount, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Currency</td>
                                    <td>{{ $payment->currency }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Payment Status</td>
                                    <td>
                                        <span class="badge bg-{{ $payment->status === 'pending' ? 'warning text-dark' : 'secondary' }}">
                                            {{ strtoupper($payment->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Environment</td>
                                    <td>
                                        <span class="badge bg-{{ $environment === 'production' ? 'danger' : 'info text-dark' }}">
                                            {{ strtoupper($environment) }}
                                        </span>
                                    </td>
                                </tr>
                                @if(!empty($checkoutData['return_url']))
                                <tr>
                                    <td class="text-muted">Return URL</td>
                                    <td><small class="text-muted">{{ $checkoutData['return_url'] }}</small></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Phase 4 Info --}}
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-body text-center py-4">
                        <i class="mdi mdi-clock-outline text-warning fs-1 mb-3 d-block"></i>
                        <h5 class="text-warning">Cashfree Checkout UI — Coming in Phase 4</h5>
                        <p class="text-muted mb-0">
                            The Cashfree JS SDK / mobile SDK checkout will be wired in Phase 4 using the
                            <code>payment_session_id</code> above. Payment will remain in <em>pending</em> state
                            until the user completes checkout and Phase 4 verification is triggered.
                        </p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('shop.orders') }}" class="btn btn-outline-light">
                        <i class="mdi mdi-arrow-left me-1"></i>Back to Orders
                    </a>
                    <a href="{{ route('payments.failed', ['payment_id' => $payment->id]) }}" class="btn btn-outline-danger">
                        Cancel Payment
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-layouts.web-app>
