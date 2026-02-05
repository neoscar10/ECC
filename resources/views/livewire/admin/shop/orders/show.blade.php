<div>
    {{-- Breadcrumbs --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Order {{ $this->order->order_number }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Shop</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.shop.orders') }}">Orders</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @include('livewire.admin.partials._alerts')

    <div class="row">
        <div class="col-xl-9">
            
            {{-- Order Items --}}
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title flex-grow-1 mb-0">Items</h5>
                        <div class="flex-shrink-0">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col" class="text-end">Unit Price</th>
                                    <th scope="col" class="text-end">Quantity</th>
                                    <th scope="col" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->order->items as $item)
                                    <tr>
                                        <td>
                                            <h5 class="fs-15 truncate-1 mb-0">{{ $item->title_snapshot }}</h5>
                                            <div class="text-muted mt-1">
                                                @foreach($item->variationValues as $val)
                                                    <span class="badge badge-soft-secondary">{{ $val->caption }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            {{ $this->order->currency }} {{ number_format($item->unit_price, 2) }}
                                        </td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        <td class="text-end fw-medium">
                                            {{ $this->order->currency }} {{ number_format($item->line_total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="border-top border-top-dashed">
                                    <td colspan="2"></td>
                                    <td colspan="2" class="fw-medium p-0">
                                        <table class="table table-borderless mb-0">
                                            <tbody>
                                                <tr>
                                                    <td>Sub Total :</td>
                                                    <td class="text-end">{{ $this->order->currency }} {{ number_format($this->order->subtotal, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Shipping :</td> // Placeholder
                                                    <td class="text-end">{{ $this->order->currency }} {{ number_format($this->order->shipping_fee, 2) }}</td>
                                                </tr>
                                                <tr class="border-top border-top-dashed">
                                                    <th scope="row">Total :</th>
                                                    <th class="text-end">{{ $this->order->currency }} {{ number_format($this->order->total_amount, 2) }}</th>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- Timeline / Status --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">Payment Status: 
                                <span class="badge badge-soft-{{ $this->order->payment_status === 'paid' ? 'success' : 'warning' }}">
                                    {{ strtoupper($this->order->payment_status) }}
                                </span>
                            </p>
                            @if($this->order->paid_at)
                                <p class="text-muted mb-0">Paid At: {{ $this->order->paid_at->format('d M Y, h:i A') }}</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">Fulfillment Status: 
                                <span class="badge badge-soft-{{ $this->order->status === 'cancelled' ? 'danger' : 'info' }}">
                                    {{ strtoupper($this->order->status) }}
                                </span>
                            </p>
                            <p class="text-muted mb-0">Placed At: {{ $this->order->placed_at->format('d M Y, h:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-xl-3">
            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if($this->order->payment_status !== 'paid' && $this->order->status !== 'cancelled')
                        <button class="btn btn-success w-100 mb-2" wire:click="markPaid" wire:confirm="Are you sure you want to mark this order as PAID?">
                            <i class="ri-checkbox-circle-line align-bottom me-1"></i> Mark as Paid
                        </button>
                    @endif

                    @if($this->order->status !== 'cancelled' && $this->order->payment_status === 'unpaid')
                        <button class="btn btn-soft-danger w-100" wire:click="confirmCancel">
                            <i class="ri-close-circle-line align-bottom me-1"></i> Cancel Order
                        </button>
                    @endif
                </div>
            </div>

            {{-- Customer Details --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer Details</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 vstack gap-3">
                        <li>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="fs-14 mb-1">{{ $this->order->user->name ?? 'Unknown' }}</h5>
                                    <p class="text-muted mb-0">{{ $this->order->user->email ?? '' }}</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Shipping Address --}}
            @if($this->order->shipping_address_snapshot)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Shipping Address</h5>
                </div>
                <div class="card-body">
                    @php $addr = $this->order->shipping_address_snapshot; @endphp
                    <h5 class="fs-14 mb-1">{{ $addr['full_name'] ?? '' }}</h5>
                    <p class="text-muted mb-0">
                        {{ $addr['line1'] ?? '' }}<br>
                        {{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} {{ $addr['postal_code'] ?? '' }}<br>
                        {{ $addr['country'] ?? '' }}<br>
                        {{ $addr['phone'] ?? '' }}
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Cancel Modal --}}
    @if($showCancelModal)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Order</h5>
                    <button type="button" class="btn-close" wire:click="$set('showCancelModal', false)"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Are you sure you want to cancel this order? Stock will be restored.</p>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" wire:model="cancelReason" rows="3" placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="$set('showCancelModal', false)">Close</button>
                    <button type="button" class="btn btn-danger" wire:click="cancelOrder">Confirm Cancel</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
