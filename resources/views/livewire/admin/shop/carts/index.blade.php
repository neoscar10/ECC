<div>
    {{-- Breadcrumbs --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Shopping Carts</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Shop</a></li>
                        <li class="breadcrumb-item active">Carts</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    @include('livewire.admin.partials._alerts') 
    <div class="card">
        <div class="card-header border-0">
            <div class="row g-4">
                <div class="col-sm">
                    <div class="d-flex justify-content-sm-end">
                       {{-- No Add Button for Carts --}}
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- Filters --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="search-box ms-2">
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search User...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="abandoned">Abandoned</option>
                        <option value="empty">Empty</option>
                    </select>
                </div>
                <div class="col-md-6 text-end text-muted small pt-2">
                    <i class="ri-information-line"></i> Abandoned threshold: {{ config('cart.abandoned_minutes') }} minutes
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive table-card mb-1">
                <table class="table align-middle">
                    <thead class="table-light text-muted">
                        <tr>
                            <th>User</th>
                            <th>Items</th>
                            <th>Total Qty</th>
                            <th>Subtotal</th>
                            <th>Status</th>
                            <th>Last Activity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($carts as $cart)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                         <div class="flex-grow-1">
                                             <h5 class="fs-14 m-0">
                                                 <a href="#" class="text-dark">{{ $cart->user->name ?? 'Deleted User' }}</a>
                                             </h5>
                                             <small class="text-muted">{{ $cart->user->email ?? 'N/A' }}</small>
                                         </div>
                                    </div>
                                </td>
                                <td>{{ $cart->items_count }}</td>
                                <td>{{ $cart->items->sum('quantity') }}</td>
                                <td>
                                    @php
                                        // Using service-like logic or just summing line_total from accessor
                                        // But CartItem line_total is accessor.
                                        // We didn't eager load items.line_total (it's computed).
                                        // We need to iterate items to sum because unit_price is on item.
                                        // Since we used paginate, eager loading all items is okay.
                                        $subtotal = $cart->items->reduce(function($carry, $item) {
                                            return $carry + ($item->unit_price * $item->quantity);
                                        }, 0);
                                        $currency = $cart->items->first()->currency ?? 'INR';
                                    @endphp
                                    {{ $currency }} {{ number_format($subtotal, 2) }}
                                </td>
                                <td>
                                    @if($cart->is_abandoned)
                                        <span class="text-danger">Abandoned</span>
                                    @elseif($cart->items_count == 0)
                                        <span class="text-secondary">Empty</span>
                                    @else
                                        <span class="text-success">Active</span>
                                    @endif
                                </td>
                                <td>
                                    <span data-bs-toggle="tooltip" title="{{ $cart->last_activity_at }}">
                                        {{ $cart->last_activity_at?->diffForHumans() }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-soft-primary" wire:click="viewCart({{ $cart->id }})">
                                        <i class="ri-eye-line align-bottom"></i> View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="noresult">
                                        <div class="text-center">
                                            <h5 class="mt-2">No Carts Found</h5>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-end mt-2">
                {{ $carts->links() }}
            </div>
        </div>
    </div>

    {{-- Cart Detail Modal --}}
    <div wire:ignore.self class="modal fade" id="cartDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cart Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($selectedCart)
                        <div class="mb-3">
                            <h6>{{ $selectedCart->user->name ?? 'Deleted User' }} <span class="text-muted">({{ $selectedCart->user->email ?? 'N/A' }})</span></h6>
                            <div>
                                Status: 
                                @if($selectedCart->is_abandoned)
                                    <span class="badge bg-danger">Abandoned</span>
                                @elseif($selectedCart->items->isEmpty())
                                    <span class="badge bg-secondary">Empty</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                                <span class="ms-2 text-muted small">Last active: {{ $selectedCart->last_activity_at?->format('d M Y H:i a') }}</span>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Variations</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedCart->items as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($item->product->images->first())
                                                        <img src="{{ Storage::url($item->product->images->first()->image_path) }}" class="avatar-sm rounded me-2 object-fit-cover">
                                                    @endif
                                                    <div>
                                                        <h6 class="fs-14 mb-1">{{ $item->product->title }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @foreach($item->selectedVariations as $var)
                                                        <div class="d-block">{{ $var->group->name }}: <span class="text-body fw-medium">{{ $var->caption }}</span></div>
                                                    @endforeach
                                                </small>
                                            </td>
                                            <td>{{ $item->currency }} {{ number_format($item->unit_price, 2) }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td class="fw-bold">{{ $item->currency }} {{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Subtotal</td>
                                        <td class="fw-bold">
                                            @php
                                                $total = $selectedCart->items->reduce(fn($c, $i) => $c + ($i->unit_price * $i->quantity), 0);
                                                $curr = $selectedCart->items->first()->currency ?? 'INR';
                                            @endphp
                                            {{ $curr }} {{ number_format($total, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center p-4">Loading...</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary fw-medium" data-bs-dismiss="modal">Close</button>
                    {{-- Future: Add Note button --}}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.addEventListener('show-cart-modal', event => {
        var myModal = new bootstrap.Modal(document.getElementById('cartDetailModal'));
        myModal.show();
    });
</script>
@endpush
