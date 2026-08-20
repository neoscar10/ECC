<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('admin.archive.ownership.index') }}" class="btn btn-sm btn-soft-secondary">
                        <i class="ri-arrow-left-line align-middle me-1"></i> Back to Tracking
                    </a>
                    <h4 class="mb-sm-0">Ownership Breakdown</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Summary Card --}}
    <div class="row">
        <div class="col-12">
            <div class="card overflow-hidden">
                <div class="row g-0">
                    <div class="col-xl-3 col-md-4">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="" class="img-fluid rounded-start h-100 object-fit-cover">
                        @else
                            <div class="bg-light h-100 d-flex align-items-center justify-content-center">
                                <i class="ri-image-line text-muted display-4"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col-xl-9 col-md-8">
                        <div class="card-body">
                            <h4 class="card-title mb-3">{{ $product->title }}
                                @if($product->trashed())
                                    <span class="badge bg-danger-subtle text-danger ms-2">Deleted</span>
                                @endif
                            </h4>
                            <div class="row text-center mt-4">
                                <div class="col-4 border-end">
                                    <div class="p-2">
                                        <h4 class="mb-1 fw-bold text-success">{{ $product->total_sold }}</h4>
                                        <p class="text-muted mb-0">Total Sold Units</p>
                                    </div>
                                </div>
                                <div class="col-4 border-end">
                                    <div class="p-2">
                                        <h4 class="mb-1 fw-bold text-primary">{{ $product->total_owners }}</h4>
                                        <p class="text-muted mb-0">Total Owners</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2">
                                        <h4 class="mb-1 fw-bold">{{ $product->trashed() ? '-' : $product->quantity }}</h4>
                                        <p class="text-muted mb-0">Remaining Stock</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ownership Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header align-items-center d-flex border-bottom-dashed">
                    <h4 class="card-title mb-0 flex-grow-1">Ownership Details</h4>
                    <div class="flex-shrink-0">
                        <ul class="nav justify-content-end nav-tabs-custom rounded card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" data-bs-toggle="tab" href="#current-owners" role="tab" aria-selected="true">
                                    Current Owners
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#resale-history" role="tab" aria-selected="false" tabindex="-1">
                                    Resale History
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        {{-- Current Owners Tab --}}
                        <div class="tab-pane active" id="current-owners" role="tabpanel">
                            <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Owner Info</th>
                                    <th scope="col">Purchase Record</th>
                                    <th scope="col" class="text-center">Qty Owned</th>
                                    <th scope="col">Current Location</th>
                                    <th scope="col">Address Details</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td>
                                            @if($order->buyer_type === 'registered' && $order->buyer)
                                                <h5 class="fs-14 mb-1"><a href="{{ route('admin.users.index') }}?search={{ urlencode($order->buyer->email) }}" class="text-dark">{{ $order->buyer->name }}</a></h5>
                                                <p class="text-muted mb-0"><i class="ri-mail-line align-middle me-1"></i>{{ $order->buyer->email }}</p>
                                                @if($order->buyer->phone)
                                                    <p class="text-muted mb-0"><i class="ri-phone-line align-middle me-1"></i>{{ $order->buyer->phone }}</p>
                                                @endif
                                                <span class="badge bg-primary-subtle text-primary mt-1">Registered</span>
                                            @else
                                                <h5 class="fs-14 mb-1">{{ $order->external_name ?? 'Guest User' }}</h5>
                                                <p class="text-muted mb-0"><i class="ri-mail-line align-middle me-1"></i>{{ $order->external_email ?? 'N/A' }}</p>
                                                <p class="text-muted mb-0"><i class="ri-phone-line align-middle me-1"></i>{{ $order->external_phone ?? 'N/A' }}</p>
                                                <span class="badge bg-light text-muted mt-1">Guest</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.archive.orders.show', $order->id) }}" class="fw-medium link-primary">{{ $order->order_number }}</a><br>
                                            <span class="text-muted fs-12">{{ $order->sold_at ? $order->sold_at->format('d M, Y') : 'N/A' }}</span><br>
                                            <span class="fw-bold mt-1 d-inline-block">INR {{ number_format($order->subtotal_inr, 2) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold fs-15">{{ $order->qty - $order->resold_qty }}</span>
                                            @if($order->resold_qty > 0)
                                                <div class="fs-12 text-muted mt-1">({{ $order->resold_qty }} Resold)</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($order->vaultItem)
                                                @if($order->vaultItem->status === 'locked')
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 fs-12">
                                                        <i class="ri-safe-2-line align-middle me-1"></i> In Vault
                                                    </span>
                                                @elseif($order->vaultItem->status === 'removed')
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-12">
                                                        <i class="ri-truck-line align-middle me-1"></i> With User (Removed from Vault)
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary px-3 py-2 fs-12">
                                                        {{ ucfirst($order->vaultItem->status) }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-12">
                                                    <i class="ri-truck-line align-middle me-1"></i> With User (Delivered)
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($order->external_address)
                                                <p class="mb-0 text-wrap" style="max-width: 250px;">{{ $order->external_address }}</p>
                                            @elseif($order->buyer_type === 'registered' && $order->buyer && $order->buyer->addresses()->where('is_default', true)->exists())
                                                @php $addr = $order->buyer->addresses()->where('is_default', true)->first(); @endphp
                                                <p class="mb-0 text-wrap" style="max-width: 250px;">
                                                    {{ $addr->line1 }}{{ $addr->line2 ? ', ' . $addr->line2 : '' }}, {{ $addr->city }}, {{ $addr->state }}<br>
                                                    {{ $addr->country ?? '' }} - {{ $addr->postal_code }}
                                                </p>
                                            @else
                                                <span class="text-muted fst-italic">No address provided</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if(($order->qty - $order->resold_qty) > 0)
                                                <button class="btn btn-sm btn-soft-primary" wire:click="openResellModal({{ $order->id }})">
                                                    Resell
                                                </button>
                                            @else
                                                <span class="badge bg-light text-muted">Fully Resold</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="noresult">
                                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                                <h5 class="mt-2">No Owners Found</h5>
                                                <p class="text-muted mb-0">This item has not been sold yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Resale History Tab --}}
                <div class="tab-pane" id="resale-history" role="tabpanel">
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Resale Invoice</th>
                                    <th scope="col">New Owner</th>
                                    <th scope="col" class="text-center">Resale Qty</th>
                                    <th scope="col">Pricing Breakdown (Per Unit)</th>
                                    <th scope="col">Total Platform Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders->filter(fn($o) => (bool)$o->is_resale) as $resale)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.archive.orders.show', $resale->id) }}" class="fw-medium link-primary">{{ $resale->order_number }}</a><br>
                                            <span class="text-muted fs-12">{{ $resale->sold_at ? $resale->sold_at->format('d M, Y') : 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if($resale->buyer_type === 'registered' && $resale->buyer)
                                                <h5 class="fs-14 mb-1">{{ $resale->buyer->name }}</h5>
                                                <span class="badge bg-primary-subtle text-primary">Registered</span>
                                            @else
                                                <h5 class="fs-14 mb-1">{{ $resale->external_name ?? 'Guest User' }}</h5>
                                                <span class="badge bg-light text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold fs-15">{{ $resale->qty }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1" style="max-width: 220px;">
                                                @if($resale->owner_asking_price_inr)
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-muted fs-12">Owner Asking Price:</span>
                                                        <span class="fw-medium text-end">INR {{ number_format($resale->owner_asking_price_inr, 2) }}</span>
                                                    </div>
                                                @endif
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted fs-12">Platform Final Price:</span>
                                                    <span class="fw-medium text-end">INR {{ number_format($resale->unit_price_inr, 2) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($resale->owner_asking_price_inr)
                                                @php
                                                    $platformProfitPerUnit = (float)$resale->unit_price_inr - (float)$resale->owner_asking_price_inr;
                                                    $totalPlatformProfit = $platformProfitPerUnit * (float)$resale->qty;
                                                @endphp
                                                <span class="fw-bold fs-15 {{ $totalPlatformProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $totalPlatformProfit >= 0 ? '+' : '' }}INR {{ number_format($totalPlatformProfit, 2) }}
                                                </span>
                                            @else
                                                <span class="text-muted fs-12 fst-italic">Data unavailable</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="noresult">
                                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                                <h5 class="mt-2">No Resales Logged</h5>
                                                <p class="text-muted mb-0">There are no resales logged for this item yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    
    @include('livewire.admin.archive.ownership.partials._resell-modal')
</div>
