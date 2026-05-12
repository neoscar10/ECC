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
                <div class="col-md-6 text-end text-muted small pt-2 d-flex justify-content-end align-items-center">
                    <i class="ri-information-line me-1"></i> Abandoned threshold: <span class="fw-medium ms-1">{{ $this->formattedThreshold }}</span>
                    <button type="button" class="btn btn-sm btn-soft-primary ms-3" wire:click="openSettingsModal">
                        <i class="ri-settings-3-line align-middle"></i> Configure
                    </button>
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
                            <h6>{{ $selectedCart->user->name ?? 'Deleted User' }} <span class="text-muted">({{ $selectedCart->user->email ?? 'N/A' }} | {{ $selectedCart->user->phone ?? 'No Phone' }})</span></h6>
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
    {{-- Settings Modal --}}
    <div wire:ignore.self class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title"><i class="ri-settings-3-line align-middle me-1 text-primary"></i> Configure Abandoned Threshold</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btn-close-settings"></button>
                </div>
                <form wire:submit.prevent="saveSettings">
                    <div class="modal-body">
                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <strong>What is this?</strong><br>
                            This setting determines how long an active shopping cart must sit completely untouched before the system officially flags it as "Abandoned". You can use this status to trigger automated recovery emails to the user.
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label for="thresholdValue" class="form-label">Duration</label>
                                <input type="number" class="form-control" id="thresholdValue" wire:model="thresholdValue" min="1" required>
                                @error('thresholdValue') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-6">
                                <label for="thresholdUnit" class="form-label">Unit</label>
                                <select class="form-select" id="thresholdUnit" wire:model="thresholdUnit" required>
                                    <option value="minutes">Minutes</option>
                                    <option value="hours">Hours</option>
                                    <option value="days">Days</option>
                                </select>
                                @error('thresholdUnit') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="hstack gap-2 justify-content-end w-100">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveSettings">Save Configuration</span>
                                <span wire:loading wire:target="saveSettings">Saving...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('show-settings-modal', () => {
            var myModal = new bootstrap.Modal(document.getElementById('settingsModal'));
            myModal.show();
        });

        Livewire.on('hide-settings-modal', () => {
            const modalEl = document.getElementById('settingsModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });
    });

    window.addEventListener('show-cart-modal', event => {
        var myModal = new bootstrap.Modal(document.getElementById('cartDetailModal'));
        myModal.show();
    });
</script>
@endpush
