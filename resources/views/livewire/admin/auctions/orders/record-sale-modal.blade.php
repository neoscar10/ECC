<div>
    {{-- Record Sale Modal --}}
    @if($showModal)
    <div class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" data-bs-backdrop="static"
     data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Auction Sale</h5>
                    <button type="button" class="btn-close" wire:click="close" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        {{-- Left Col: Lot Selection --}}
                        <div class="col-md-6 p-4 border-end">
                            <h6 class="text-uppercase fw-semibold mb-3">Auction Lot</h6>
                            
                            @if($selectedLot)
                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="fs-15 mb-1">{{ $selectedLot->title }}</h5>
                                                <p class="text-muted mb-0">Lot #{{ $selectedLot->lot_no }}</p>
                                            </div>
                                             @if(!$auction_lot_id) 
                                                <button type="button" wire:click="$set('selectedLot', null)" class="btn btn-sm btn-link text-danger p-0">Change</button>
                                            @endif
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-top mt-2">
                                            <span class="text-muted">Highest Bid:</span>
                                            <span class="fw-bold fs-15">{{ $selectedLot->currency }} {{ number_format($selectedLot->current_highest_bid) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mb-3 position-relative" x-data="{ open: false }">
                                    <label class="form-label">Search Lot</label>
                                    <div class="input-group">
                                        <input type="text" 
                                            wire:model.live.debounce.300ms="lotSearch" 
                                            class="form-control" 
                                            placeholder="Lot No or Title..."
                                            @focus="open = true; $wire.searchLots()"
                                            @input="open = true"
                                            @click.outside="open = false"
                                            @keydown.escape="open = false"
                                        >
                                    </div>
                                    
                                    @if(count($lotSearchResults) > 0)
                                        <div class="list-group position-absolute w-100 shadow mt-1" 
                                            style="z-index: 1000; max-height: 200px; overflow-y: auto;"
                                            x-show="open"
                                            x-transition
                                            wire:key="lot-search-results"
                                        >
                                            @foreach($lotSearchResults as $lot)
                                                <button type="button" wire:click="selectLot({{ $lot->id }}); open = false" class="list-group-item list-group-item-action">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="fw-medium text-truncate" style="max-width: 200px;">{{ $lot->title }}</div>
                                                        <span class="badge bg-light text-dark">{{ ucfirst($lot->status) }}</span>
                                                    </div>
                                                    <small class="text-muted">Lot #{{ $lot->lot_no }}</small>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                             {{-- Payment Details --}}
                             <div class="mt-4">
                                <h6 class="text-uppercase fw-semibold mb-3">Payment Details</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Method <span class="text-danger">*</span></label>
                                    <select wire:model="payment_method" class="form-select">
                                        <option value="Offline">Offline / Other</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Cheque">Cheque</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reference ID (Optional)</label>
                                    <input type="text" wire:model="payment_reference" class="form-control" placeholder="Transaction Ref / Chq No">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Paid At</label>
                                    <input type="datetime-local" wire:model="paid_at" class="form-control">
                                </div>
                                
                                <div class="mb-0">
                                    <label class="form-label">Notes</label>
                                    <textarea wire:model="notes" class="form-control" rows="2"></textarea>
                                </div>
                             </div>
                        </div>

                        {{-- Right Col: Buyer & Price --}}
                        <div class="col-md-6 p-4 bg-light">
                            <h6 class="text-uppercase fw-semibold mb-3">Buyer & Amount</h6>
                            
                            {{-- Buyer Selector --}}
                            <div class="mb-3">
                                <div class="btn-group w-100 mb-2" role="group">
                                    <input type="radio" class="btn-check" name="buyer_type" id="ab_reg" value="registered" wire:model.live="buyer_type">
                                    <label class="btn btn-outline-primary" for="ab_reg">Registered User</label>

                                    <input type="radio" class="btn-check" name="buyer_type" id="ab_ext" value="external" wire:model.live="buyer_type">
                                    <label class="btn btn-outline-primary" for="ab_ext">External Guest</label>
                                </div>
                                
                                @if($buyer_type === 'registered')
                                    @if($selectedUser)
                                        <div class="card border">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h6 class="fs-14 mb-1">{{ $selectedUser->name }}</h6>
                                                        <p class="text-muted mb-0 fs-11">{{ $selectedUser->email }}</p>
                                                    </div>
                                                </div>
                                                <button type="button" wire:click="$set('selectedUser', null)" class="btn btn-sm btn-danger mt-2">Change</button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="position-relative" x-data="{ open: false }">
                                            <input type="text" 
                                                wire:model.live.debounce.300ms="userSearch" 
                                                class="form-control" 
                                                placeholder="Search Winner..."
                                                @focus="open = true; $wire.searchUsers()"
                                                @input="open = true"
                                                @click.outside="open = false"
                                                @keydown.escape="open = false"
                                            >
                                            @if(count($userSearchResults) > 0)
                                                <div class="list-group position-absolute w-100 shadow mt-1" 
                                                    style="z-index: 1000; max-height: 200px; overflow-y: auto;"
                                                    x-show="open"
                                                    x-transition
                                                    wire:key="user-search-results"
                                                >
                                                    @foreach($userSearchResults as $u)
                                                        <button type="button" wire:click="selectUser({{ $u->id }}); open = false" class="list-group-item list-group-item-action">
                                                            <div class="fw-medium">{{ $u->name }}</div>
                                                            <small class="text-muted">{{ $u->email }}</small>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <div class="vstack gap-2">
                                        <input type="text" wire:model="external_name" class="form-control" placeholder="Full Name *">
                                        <input type="email" wire:model="external_email" class="form-control" placeholder="Email">
                                        <input type="text" wire:model="external_phone" class="form-control" placeholder="Phone">
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Fulfillment Method --}}
                            <div class="mb-3">
                                <label class="form-label d-block">Fulfillment Method</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="fulfillment_method" id="afm_del" value="delivery" wire:model.live="fulfillment_method">
                                    <label class="btn btn-outline-secondary" for="afm_del">
                                        <i class="ri-truck-line align-middle me-1"></i> Delivery / Pickup
                                    </label>

                                    <input type="radio" class="btn-check" name="fulfillment_method" id="afm_vlt" value="vault" wire:model.live="fulfillment_method" 
                                        @if(!$can_vault || $buyer_type === 'external') disabled @endif>
                                    <label class="btn btn-outline-secondary" for="afm_vlt">
                                        <i class="ri-safe-2-line align-middle me-1"></i> Lock in Vault
                                    </label>
                                </div>
                                @if($buyer_type === 'registered' && !$can_vault && $user_id)
                                    <div class="text-warning fs-11 mt-1"><i class="ri-alert-line"></i> User's membership tier does not support Vault Access.</div>
                                @elseif($buyer_type === 'external')
                                    <div class="text-muted fs-11 mt-1">Vault access available for registered members only.</div>
                                @endif
                                @error('fulfillment_method') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                            </div>
                            
                            {{-- Amount --}}
                            <div class="mb-4">
                                <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $selectedLot->currency ?? 'INR' }}</span>
                                    <input type="number" wire:model="unit_price_inr" class="form-control fs-16 fw-bold" step="0.01">
                                </div>
                                <div class="form-text">Defaults to winning bid. Editable if needed.</div>
                            </div>
                            
                            {{-- Summary --}}
                             <div class="pt-4 border-top mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-0">
                                    <span class="text-muted fs-14">Total Payable</span>
                                    <span class="fs-22 fw-bold text-success">{{ $selectedLot->currency ?? 'INR' }} {{ number_format((float)$unit_price_inr, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                     @error('general') 
                        <span class="text-danger me-auto"><i class="ri-error-warning-line"></i> {{ $message }}</span> 
                    @enderror
                    
                    <button type="button" class="btn btn-light" wire:click="close">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="store" @if(!$selectedLot) disabled @endif>
                        <i class="ri-save-line align-bottom me-1"></i> Confirm Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
