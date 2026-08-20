<div>
    {{-- Breadcrumbs --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Archive Order Details</h4>
                <div class="page-title-right">
                    <a href="{{ route('admin.archive.orders.index') }}" class="btn btn-sm btn-soft-secondary me-2">
                        <i class="ri-arrow-left-line align-middle me-1"></i> Back to Archive Orders
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert --}}
    @include('livewire.admin.partials._alerts')

    <div class="row">
        <div class="col-xl-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order: {{ $order->order_number }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase fw-semibold mb-3">Item Details</h6>
                            <p class="mb-1"><strong>Title:</strong> {{ $order->product ? $order->product->title : 'N/A' }}</p>
                            <p class="mb-1"><strong>Quantity:</strong> {{ $order->qty }}</p>
                            <p class="mb-1"><strong>Unit Price:</strong> INR {{ number_format($order->unit_price_inr, 2) }}</p>
                            <p class="mb-1"><strong>Total:</strong> INR {{ number_format($order->subtotal_inr, 2) }}</p>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase fw-semibold mb-3">Buyer Information</h6>
                            @if($order->buyer_type === 'registered' && $order->buyer)
                                <p class="mb-1"><strong>Name:</strong> <a href="{{ route('admin.users.index') }}?search={{ urlencode($order->buyer->email) }}">{{ $order->buyer->name }}</a></p>
                                <p class="mb-1"><strong>Email:</strong> {{ $order->buyer->email }}</p>
                            @else
                                <p class="mb-1"><strong>Name (Guest):</strong> {{ $order->external_name }}</p>
                                <p class="mb-1"><strong>Email:</strong> {{ $order->external_email ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Phone:</strong> {{ $order->external_phone ?? 'N/A' }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Activity & Notes (Audit Trail)</h5>
                </div>
                <div class="card-body">
                    <p style="white-space: pre-wrap;" class="mb-0">{{ $order->notes ?? 'No notes or activities recorded for this order.' }}</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge {{ $order->status === 'completed' ? 'bg-success' : ($order->status === 'cancelled' ? 'bg-danger' : 'bg-secondary') }} fs-14">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                    <p class="mb-1 text-muted">Sold At: {{ $order->sold_at ? \Carbon\Carbon::parse($order->sold_at)->format('d M, Y h:i A') : 'N/A' }}</p>
                    <p class="mb-0 text-muted">Logged By: {{ $order->logger ? $order->logger->name : 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
