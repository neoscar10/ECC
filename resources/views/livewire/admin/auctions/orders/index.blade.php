<div>
    @section('title', 'Auction Orders')
    
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0 text-uppercase fw-bold text-primary">Auction Orders</h4>
                        <div class="fs-13 text-muted">Manage auction sales and records</div>
                    </div>
                   <button wire:click="openRecordSaleModal" class="btn btn-success waves-effect waves-light">
                        <i class="ri-add-line align-bottom me-1"></i> Record New Sale
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="search-box">
                            <input type="text" class="form-control" placeholder="Search orders, lots, or buyers..." wire:model.live.debounce.300ms="search">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" wire:model.live="status">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                         <div class="input-group">
                            <input type="date" class="form-control" wire:model.live="dateFrom" placeholder="From Date">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" wire:model.live="dateTo" placeholder="To Date">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Order Ref</th>
                                <th scope="col">Lot</th>
                                <th scope="col">Buyer</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Payment</th>
                                <th scope="col">Recorded At</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td class="fw-medium text-primary">{{ $order->order_number }}</td>
                                    <td>
                                        @if($order->auctionLot)
                                            <a href="{{ route('admin.auctions.lots.show', $order->auctionLot->id) }}" class="text-reset fw-medium">
                                                Lot #{{ $order->auctionLot->lot_no }} - {{ Str::limit($order->auctionLot->title, 30) }}
                                            </a>
                                        @else
                                            <span class="text-muted">No Lot</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->buyer)
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="fs-13 mb-0">{{ $order->buyer->name }}</h6>
                                                    <span class="text-muted fs-12">{{ $order->buyer->email }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                     <h6 class="fs-13 mb-0">{{ $order->external_name }} <span class="badge bg-light text-dark text-xs">Guest</span></h6>
                                                    <span class="text-muted fs-12">{{ $order->external_email ?? $order->external_phone }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="fw-bold">
                                         @if($order->auctionLot)
                                            {{ $order->auctionLot->currency }} {{ number_format($order->subtotal_inr) }}
                                         @else
                                            INR {{ number_format($order->subtotal_inr) }}
                                         @endif
                                    </td>
                                    <td>
                                        <div class="vstack gap-1">
                                            <span class="badge bg-soft-success text-success">{{ $order->payment_method }}</span>
                                            @if($order->payment_reference)
                                                <span class="fs-11 text-muted">{{ $order->payment_reference }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="vstack gap-1">
                                            <span>{{ $order->sold_at->format('M d, Y') }}</span>
                                            <span class="text-muted fs-11">{{ $order->sold_at->format('h:i A') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($order->status === 'completed')
                                            <span class="badge bg-success-subtle text-success text-uppercase">Completed</span>
                                        @elseif($order->status === 'cancelled')
                                            <span class="badge bg-danger-subtle text-danger text-uppercase">Cancelled</span>
                                        @else
                                            <span class="badge bg-light text-dark text-uppercase">{{ $order->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-fill align-middle"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.auctions.lots.show', $order->auction_lot_id) }}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View Lot</a></li>
                                                {{-- <li><a class="dropdown-item remove-item-btn" wire:click="confirmCancel({{ $order->id }})"><i class="ri-close-circle-fill align-bottom me-2 text-muted"></i> Cancel Order</a></li> --}}
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">No orders found matching your criteria.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
             <div class="card-footer border-top-0">
                {{ $orders->links() }}
            </div>
        </div>
    </div>

    {{-- Record Sale Modal --}}
    @livewire('admin.auctions.orders.record-sale-modal')

</div>
