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
            <div class="row g-2">
                {{-- Filters & Search in one row --}}
                <div class="col-lg-3">
                    <div class="search-box">
                        <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search Orders...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
                <div class="col-lg-2">
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="">All Fulfillment</option>
                        <option value="placed">Placed</option>
                        <option value="paid">Paid</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="processing">Processing</option>
                        <option value="packed">Packed</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <select class="form-select" wire:model.live="filterPaymentStatus">
                        <option value="">All Payment</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    {{-- Optional Actions like Export can go here --}}
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            {{-- Table --}}
            <div class="table-responsive table-card mb-1">
                <table class="table table-nowrap align-middle">
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
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h5 class="fs-14 mb-1">{{ $order->user->name ?? 'Guest' }}</h5>
                                            <p class="text-muted mb-0">{{ $order->user->email ?? '' }}</p>
                                        </div>
                                    </div>
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
                                            'pending' => 'warning text-dark',
                                            'failed' => 'danger',
                                            'refunded' => 'info',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $pymtClass }}">{{ ucfirst($order->payment_status) }}</span>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($order->status) {
                                            'delivered', 'fulfilled' => 'success',
                                            'shipped' => 'warning text-dark',
                                            'paid' => 'primary',
                                            'packed', 'processing', 'confirmed' => 'info',
                                            'cancelled', 'returned' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a href="{{ route('admin.shop.orders.show', $order->id) }}" class="dropdown-item"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                            <li><a href="#" wire:click.prevent="openStatusModal({{ $order->id }})" class="dropdown-item"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Manage Status</a></li>
                                        </ul>
                                    </div>
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

    {{-- Status Modal --}}
    @if($showStatusModal)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" wire:click="closeStatusModal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between">
                            <span class="fw-medium">Order: {{ $selectedOrderNumber }}</span>
                            <span class="text-muted">{{ $selectedOrderCustomer }}</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <select class="form-select" wire:model="paymentStatusDraft">
                            <option value="unpaid">Unpaid</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fulfillment Status</label>
                        <select class="form-select" wire:model="statusDraft">
                            <option value="placed">Placed</option>
                            <option value="paid">Paid</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="packed">Packed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="returned">Returned</option>
                        </select>
                        <div class="form-text text-muted">Change order progress manually. Does not trigger stock restoration if cancelled.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="closeStatusModal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="saveStatus">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
