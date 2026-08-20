<div class="card h-100">
    <div class="card-header">
        <div class="d-flex align-items-center">
            <h5 class="card-title flex-grow-1 mb-0">Shipping Address</h5>
        </div>
    </div>
    <div class="card-body">
        @php
            $addr = $this->order->shipping_address_snapshot;
        @endphp
        @if($addr)
            <h5 class="fs-14 mb-2">{{ $addr['full_name'] ?? ($addr['label'] ?? 'Unknown Name') }}</h5>
            <p class="text-muted mb-0">
                {{ $addr['line1'] ?? '' }}<br>
                @if(!empty($addr['line2'])) {{ $addr['line2'] }}<br> @endif
                {{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }}<br>
                {{ $addr['postal_code'] ?? '' }}<br>
                {{ $addr['country'] ?? '' }}<br>
                <span class="mt-2 d-block"><i class="ri-phone-line me-1"></i> {{ $addr['phone'] ?? 'No Phone' }}</span>
            </p>
        @else
            <p class="text-muted mb-0">No shipping address provided.</p>
        @endif

        <hr class="border-dashed">

        <h6 class="fs-13 fw-semibold text-uppercase">Delivery Details</h6>
        <div class="mt-2">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Delivery Type:</span>
                <span class="fw-medium">
                    @if($this->order->delivery_type === 'negotiated')
                        <span class="badge bg-secondary-subtle text-secondary">Negotiated (To be discussed)</span>
                    @else
                        <span class="badge bg-primary-subtle text-primary">Courier</span>
                    @endif
                </span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="text-muted">Shipping Fee Payment:</span>
                <span class="fw-medium">
                    @if($this->order->delivery_type === 'negotiated')
                        @if($this->order->delivery_payment_status === 'paid')
                            <span class="badge bg-success-subtle text-success">Paid</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Needs Negotiation</span>
                        @endif
                    @else
                        @if($this->order->payment_status === 'paid')
                            <span class="badge bg-success-subtle text-success">Paid</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Unpaid</span>
                        @endif
                    @endif
                </span>
            </div>
            
            @if($this->order->delivery_type === 'negotiated')
                @if($this->order->delivery_payment_status !== 'paid')
                <div class="mt-3">
                    <button wire:click.prevent="openMarkDeliveryPaidModal" class="btn btn-sm btn-success w-100">
                        Mark Shipping as Paid
                    </button>
                </div>
                @endif
            @endif
        </div>
    </div>
</div>
