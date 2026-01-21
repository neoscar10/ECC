<div>
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Archive Orders</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">The Archive</a></li>
                        <li class="breadcrumb-item active">Orders</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="orderList">
                <div class="card-header border-0">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm-auto">
                            <div>
                                <h5 class="card-title mb-0">Sales & Orders</h5>
                            </div>
                        </div>
                        <div class="col-sm">
                            <div class="d-flex justify-content-sm-end gap-2">
                                <div class="search-box ms-2">
                                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search orders, buyers...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                                <select wire:model.live="status" class="form-control" style="width: 140px;">
                                    <option value="">All Statuses</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <button type="button" wire:click="openCreateModal" class="btn btn-primary add-btn">
                                    <i class="ri-add-line align-bottom me-1"></i> Log Sale
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    @if(session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-card mb-3">
                        <table class="table align-middle table-nowrap mb-0" id="orderTable">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" >ID</th>
                                    <th class="sort">Date</th>
                                    <th class="sort">Product</th>
                                    <th class="sort">Buyer</th>
                                    <th class="sort text-start">Price (INR)</th>
                                    <th class="sort text-start">Qty</th>
                                    <th class="sort ">Total</th>
                                    <th class="sort">Status</th>
                                    <th class="sort">Action</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse ($orders as $order)
                                    <tr>
                                        <td><a href="#" class="fw-medium link-primary">{{ $order->order_number }}</a></td>
                                        <td>
                                            {{ $order->sold_at->format('d M, Y') }}<br>
                                            <small class="text-muted">{{ $order->sold_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($order->product)
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 me-2">
                                                        @if($order->product->images->first())
                                                            <img src="{{ Storage::url($order->product->images->first()->image_path) }}" alt="" class="avatar-xs rounded-circle">
                                                        @else
                                                            <div class="avatar-xs bg-light rounded-circle"></div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="fs-14 mb-0"><a href="#" class="text-reset">{{ Str::limit($order->product->title, 20) }}</a></h5>
                                                        <p class="text-muted mb-0 fs-11">Enq: {{ $order->archive_product_enquiry_id ? '#'.$order->archive_product_enquiry_id : 'Manual' }}</p>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-danger">Deleted Product</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($order->buyer_type === 'registered' && $order->buyer)
                                                 <h5 class="fs-14 mb-1">{{ $order->buyer->name }}</h5>
                                                 <p class="text-muted mb-0">{{ $order->buyer->email }}</p>
                                            @else
                                                 <h5 class="fs-14 mb-1">{{ $order->external_name ?? 'N/A' }} <span class="badge bg-light text-muted border">Ext</span></h5>
                                                 <p class="text-muted mb-0">{{ $order->external_email ?? $order->external_phone }}</p>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($order->unit_price_inr, 2) }}
                                        </td>
                                        <td class="text-center">
                                            {{ $order->qty }}
                                        </td>
                                        <td class="text-end fw-bold">
                                            {{ number_format($order->subtotal_inr, 2) }}
                                        </td>
                                        <td class="status">
                                            @if($order->status === 'completed')
                                                <span class=" text-uppercase">Completed</span>
                                            @else
                                                <span class="text-uppercase">Cancelled</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($order->status !== 'cancelled')
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item remove-item-btn" href="#" 
                                                           wire:click.prevent="cancelOrder({{ $order->id }})"
                                                           onclick="confirm('Are you sure? This will RESTORE stock.') || event.stopImmediatePropagation()">
                                                            <i class="ri-close-circle-fill align-bottom me-2 text-danger"></i> Cancel Order
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="noresult">
                                                <div class="text-center">
                                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                                    <h5 class="mt-2">No Orders Found</h5>
                                                    <p class="text-muted mb-0">No sales have been logged yet.</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @livewire('admin.archive.orders.create')
    
</div>
