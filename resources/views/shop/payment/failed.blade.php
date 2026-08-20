<x-dynamic-component :component="(isset($payment) && $payment->purpose === \App\Support\Payments\PaymentPurpose::ARCHIVE_ENQUIRY_PAYMENT) ? 'layouts.guest' : 'layouts.web-app'" title="Payment Failed">
    @php
        $paymentId = request('payment_id');
        $payment = $paymentId ? \App\Models\Payment::find($paymentId) : null;
        $order = $payment?->payable;
        $canRetry = $payment && in_array($payment->status, [\App\Support\Payments\PaymentStatus::INITIATED, \App\Support\Payments\PaymentStatus::PENDING, \App\Support\Payments\PaymentStatus::FAILED]) 
                    && $order && $order->payment_status !== 'paid';
    @endphp

    <div class="ecc-container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card bg-dark text-white shadow p-5 border-danger">
                    <i class="mdi mdi-alert-circle text-danger mb-3" style="font-size: 64px;"></i>
                    <h2 class="text-danger mb-3">Payment Failed</h2>
                    
                    <p class="mb-4 text-muted">
                        We could not process your payment successfully. Your payment might have been declined by your bank or the gateway window was closed.
                    </p>

                    @if($payment)
                        <div class="bg-secondary bg-opacity-25 rounded p-3 mb-4 text-start">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Order Number:</span>
                                <strong>{{ $order->order_number ?? 'N/A' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Amount:</span>
                                <strong>₹{{ number_format($payment->amount, 2) }}</strong>
                            </div>
                            @if($payment->failure_message)
                                <div class="mt-2 text-danger">
                                    <small><strong>Reason:</strong> {{ $payment->failure_message }}</small>
                                </div>
                            @endif
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="d-flex justify-content-center gap-3 mt-4">
                        @if($payment && $payment->purpose === \App\Support\Payments\PaymentPurpose::ARCHIVE_ENQUIRY_PAYMENT)
                            <a href="{{ route('home') }}" class="btn btn-outline-light">Return to ECC</a>
                            <a href="{{ route('payments.retry', $payment->id) }}" class="btn btn-warning">Retry Payment</a>
                        @else
                            <a href="{{ route('shop.cart') }}" class="btn btn-outline-light">Return to Cart</a>
                            @if($canRetry)
                                <a href="{{ route('payments.retry', $payment->id) }}" class="btn btn-warning">Retry Payment</a>
                            @else
                                <a href="{{ route('shop.checkout') }}" class="btn btn-warning">Try Again</a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
