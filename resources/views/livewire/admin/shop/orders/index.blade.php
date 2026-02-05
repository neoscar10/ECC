<div>
    {{-- Breadcrumbs --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Shop Orders</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Shop</a></li>
                        <li class="breadcrumb-item active">Orders</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert --}}
    @include('livewire.admin.partials._alerts')

    {{-- Main Card --}}
    <div class="card">
        <div class="card-header border-0">
            <div class="row g-4">
                <div class="col-sm-auto">
                    {{-- Actions if needed --}}
                </div>
                <div class="col-sm">
                    <div class="d-flex justify-content-sm-end">
                        <div class="search-box ms-2">
                            <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search Orders...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- Filters --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="">All Statuses</option>
                        <option value="pending_payment">Pending Payment</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="fulfilled">Fulfilled</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterPaymentStatus">
                        <option value="">All Payment Statuses</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive table-card mb-1">
                <table class="table align-middle table-nowrap">
                    <thead class="table-light text-muted">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td><a href="{{ route('admin.shop.orders.show', $order->id) }}" class="fw-medium link-primary">{{ $order->order_number }}</a></td>
                                <td>
                                    @if($order->user)
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">{{ $order->user->name }}</div>
                                        </div>
                                    @else
                                        <span class="text-muted">Unknown User</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $order->placed_at ? $order->placed_at->format('d M Y, h:i A') : '-' }}
                                </td>
                                <td>
                                    {{ $order->currency }} {{ number_format($order->total_amount, 2) }}
                                </td>
                                <td>
                                    @php
                                        $pymtClass = match($order->payment_status) {
                                            'paid' => 'success',
                                            'unpaid' => 'warning',
                                            'failed' => 'danger',
                                            'refunded' => 'info',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge badge-soft-{{ $pymtClass }} text-uppercase">{{ str_replace('_', ' ', $order->payment_status) }}</span>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($order->status) {
                                            'fulfilled' => 'success',
                                            'paid' => 'info',
                                            'pending_payment' => 'warning',
                                            'cancelled' => 'danger',
                                            'failed' => 'dark',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge badge-soft-{{ $statusClass }} text-uppercase">{{ str_replace('_', ' ', $order->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.shop.orders.show', $order->id) }}" class="btn btn-sm btn-soft-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="noresult py-4">
                                        <div class="text-center">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                            <h5 class="mt-2">No Orders Found</h5>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-2">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>
