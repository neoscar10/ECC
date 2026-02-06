<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-lg-center flex-lg-row flex-column">
            <div class="flex-grow-1">
                <h4 class="fs-16 mb-1">Order #{{ $this->order->order_number }}</h4>
                <p class="text-muted mb-0">
                    Placed <span class="fw-medium">{{ $this->order->placed_at->format('d M Y, h:i A') }}</span>
                    &nbsp;|&nbsp;
                    Customer: <span class="fw-medium">{{ $this->order->user->name ?? 'Guest' }}</span>
                    &nbsp;|&nbsp;
                    Items: <span class="fw-medium">{{ $this->order->items->sum('quantity') }}</span>
                </p>
            </div>
            <div class="mt-3 mt-lg-0">
                <div class="d-flex align-items-center gap-3">
                    {{-- Total Amount --}}
                    <div>
                        <h5 class="fs-18 mb-0 text-primary fw-bold">
                            {{ $this->order->currency }} {{ number_format($this->order->total_amount, 2) }}
                        </h5>
                    </div>

                    {{-- Badges --}}
                    <div>
                        @php
                            $pymtClass = match($this->order->payment_status) {
                                'paid' => 'success',
                                'pending' => 'warning text-dark',
                                'failed' => 'danger',
                                'refunded' => 'info',
                                default => 'secondary'
                            };
                            $statusClass = match($this->order->status) {
                                'fulfilled', 'delivered' => 'success',
                                'packed', 'processing' => 'info',
                                'shipped' => 'warning text-dark',
                                'cancelled', 'returned' => 'danger',
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $pymtClass }} me-1">{{ ucfirst(str_replace('_', ' ', $this->order->payment_status)) }}</span>
                        <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $this->order->status)) }}</span>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 ms-2">
                        @if($this->order->payment_status !== 'paid' && $this->order->status !== 'cancelled')
                            <button wire:click="markPaid" 
                                    wire:confirm="Are you sure you want to mark this order as PAID?"
                                    class="btn btn-success btn-sm">
                                <i class="ri-check-double-line align-bottom me-1"></i> Mark Paid
                            </button>
                        @endif

                        @if(!in_array($this->order->status, ['cancelled', 'delivered', 'returned']))
                            <button wire:click="confirmCancel" class="btn btn-danger btn-sm">
                                <i class="ri-close-circle-line align-bottom me-1"></i> Cancel Order
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
