<div class="card">
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
    </div>
</div>
