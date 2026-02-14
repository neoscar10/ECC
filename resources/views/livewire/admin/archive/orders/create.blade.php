<div>
    {{-- Create Order Modal --}}
    @if($showModal)
    <div class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" data-bs-backdrop="static"
     data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log New Sale</h5>
                    <button type="button" class="btn-close" wire:click="close" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        {{-- Left Col: Product & Stock --}}
                        <div class="col-md-7 p-4 border-end">
                            <h6 class="text-uppercase fw-semibold mb-3">Product Selection</h6>
                            
                            @if($selectedProduct)
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0 me-3">
                                                 @if($selectedProduct->images->first())
                                                    <img src="{{ Storage::url($selectedProduct->images->first()->image_path) }}" class="rounded avatar-md">
                                                @else
                                                    <div class="avatar-md bg-white rounded d-flex align-items-center justify-content-center">
                                                        <i class="ri-image-line text-muted"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <h5 class="fs-15 mb-1">{{ $selectedProduct->title }}</h5>
                                                <p class="text-muted mb-0">SKU: #{{ $selectedProduct->id }}</p>
                                                <button type="button" wire:click="$set('selectedProduct', null)" class="btn btn-sm btn-link text-danger p-0 mt-1">Change Product</button>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center py-2 border-top b">
                                            <span class="text-muted">Available Stock:</span>
                                            <span class="fw-bold fs-16 {{ $selectedProduct->quantity < 1 ? 'text-danger' : 'text-success' }}">{{ $selectedProduct->quantity }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-top ">
                                            <span class="text-muted">Min Price:</span>
                                            <span class="fw-medium">INR {{ number_format($selectedProduct->price_min_amount) }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Sales Details --}}
                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" wire:model.live="qty" class="form-control" min="1" max="{{ $selectedProduct->quantity }}">
                                        @error('qty') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Unit Price (INR) <span class="text-danger">*</span></label>
                                        <input type="number" wire:model.live="unit_price_inr" class="form-control" step="0.01">
                                        @error('unit_price_inr') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Notes (Optional)</label>
                                        <textarea wire:model="notes" class="form-control" rows="2" placeholder="Internal notes about this sale..."></textarea>
                                    </div>
                                </div>

                            @else
                                <div class="mb-3 position-relative" wire:key="product-search-wrapper" x-on:click.outside="$wire.closeDropdowns()">
                                    <label class="form-label">Search Product</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                                        <input type="text" 
                                            wire:model.live.debounce.250ms="productSearch" 
                                            wire:focus="openProductDropdown"
                                            wire:click.stop="openProductDropdown"
                                            wire:keydown.escape="closeDropdowns"
                                            class="form-control" 
                                            placeholder="Type product name to search...">
                                    </div>
                                    
                                    @if($showProductDropdown)
                                        <div class="dropdown-menu show w-100 shadow mt-1 p-0 border-0 overflow-hidden" style="position: absolute; inset: 100% auto auto 0; z-index: 1050;">
                                            <div class="list-group list-group-flush" style="max-height: 260px; overflow-y: auto;">
                                                @forelse($searchResults as $result)
                                                    <button type="button" 
                                                        wire:mousedown.prevent="selectProduct({{ $result->id }})" 
                                                        class="list-group-item list-group-item-action d-flex align-items-center p-2">
                                                        <div class="flex-shrink-0 me-2">
                                                            @if($result->images->first())
                                                                <img src="{{ Storage::url($result->images->first()->image_path) }}" class="avatar-xs rounded-circle">
                                                            @else
                                                                <div class="avatar-xs bg-light rounded-circle d-flex align-items-center justify-content-center">
                                                                    <i class="ri-image-line text-muted"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="flex-grow-1 overflow-hidden">
                                                            <div class="fw-bold text-truncate">{{ $result->title }}</div>
                                                            <small class="text-muted d-flex justify-content-between">
                                                                <span>SKU: {{ $result->id }}</span>
                                                                <span class="{{ $result->quantity > 0 ? 'text-success' : 'text-danger' }}">Qty: {{ $result->quantity }}</span>
                                                            </small>
                                                        </div>
                                                    </button>
                                                @empty
                                                    <div class="p-3 text-center text-muted">
                                                        <small>No products found.</small>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Right Col: Buyer Info --}}
                        <div class="col-md-5 p-4 bg-light">
                            <h6 class="text-uppercase fw-semibold mb-3">Buyer Details</h6>
                            
                            @if($enquiry_id)
                                <div class="alert alert-info border-0 mb-3">
                                    <span class="fw-medium"><i class="ri-links-line"></i> Linked to Enquiry #{{ $enquiry_id }}</span>
                                </div>
                            @endif
                            
                            <div class="mb-3">
                                <label class="form-label d-block">Buyer Type</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="buyer_type" id="bt_reg" value="registered" wire:model.live="buyer_type" {{ $user_id ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="bt_reg">Registered User</label>

                                    <input type="radio" class="btn-check" name="buyer_type" id="bt_ext" value="external" wire:model.live="buyer_type">
                                    <label class="btn btn-outline-primary" for="bt_ext">External Guest</label>
                                </div>
                            </div>
                            
                            @if($buyer_type === 'registered')
                                @if($user_id)
                                    {{-- Selected User Display --}}
                                    <div class="card border">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="fs-14 mb-1">{{ $userSearch }}</h6>
                                                    <p class="text-muted mb-0 fs-11">Registered Member</p>
                                                </div>
                                                
                                                    
                                                
                                                
                                            </div>
                                            <button type="button" wire:click="$set('user_id', null)" class="btn btn-sm btn-danger mt-2">Change</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="mb-3 position-relative" wire:key="user-search-wrapper" x-on:click.outside="$wire.closeDropdowns()">
                                        <label class="form-label">Search User</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ri-user-search-line"></i></span>
                                            <input type="text" 
                                                wire:model.live.debounce.250ms="userSearch" 
                                                wire:focus="openUserDropdown"
                                                wire:click.stop="openUserDropdown"
                                                wire:keydown.escape="closeDropdowns"
                                                class="form-control" 
                                                placeholder="Name or Email..."
                                                autocomplete="off">
                                        </div>
                                        
                                        @if($showUserDropdown)
                                            <div class="dropdown-menu show w-100 shadow mt-1 p-0 border-0 overflow-hidden" style="position: absolute; inset: 100% auto auto 0; z-index: 1050;">
                                                <div class="list-group list-group-flush" style="max-height: 260px; overflow-y: auto;">
                                                    @forelse($userSearchResults as $u)
                                                        <button type="button" 
                                                            wire:mousedown.prevent="selectUser({{ $u->id }})" 
                                                            class="list-group-item list-group-item-action p-2">
                                                            <div class="fw-bold">{{ $u->name }}</div>
                                                            <small class="text-muted">{{ $u->email }}</small>
                                                        </button>
                                                    @empty
                                                        <div class="p-3 text-center text-muted">
                                                            <small>No users found.</small>
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                {{-- External Form --}}
                                <div class="vstack gap-3">
                                    <div>
                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" wire:model="external_name" class="form-control">
                                        @error('external_name') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="form-label">Email</label>
                                        <input type="email" wire:model="external_email" class="form-control">
                                    </div>
                                    <div>
                                        <label class="form-label">Phone</label>
                                        <input type="text" wire:model="external_phone" class="form-control">
                                    </div>
                                </div>
                            @endif
                            
                            @endif
                            
                            {{-- Fulfillment Method --}}
                            <div class="mb-3">
                                <label class="form-label d-block">Fulfillment Method</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="fulfillment_method" id="fm_del" value="delivery" wire:model.live="fulfillment_method">
                                    <label class="btn btn-outline-secondary" for="fm_del">
                                        <i class="ri-truck-line align-middle me-1"></i> Delivery / Pickup
                                    </label>

                                    <input type="radio" class="btn-check" name="fulfillment_method" id="fm_vlt" value="vault" wire:model.live="fulfillment_method" 
                                        @if(!$can_vault || $buyer_type === 'external') disabled @endif>
                                    <label class="btn btn-outline-secondary" for="fm_vlt">
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

                            {{-- Summary Total --}}
                             <div class="mt-4 pt-4 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-0">
                                    <span class="text-muted fs-14">Total Amount</span>
                                    <span class="fs-20 fw-bold text-primary">INR {{ number_format( (float)$qty * (float)$unit_price_inr, 2) }}</span>
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
                    <button type="button" class="btn btn-success" wire:click="store" wire:loading.attr="disabled" wire:target="store" @if(!$selectedProduct) disabled @endif>
                        <span wire:loading.remove wire:target="store"><i class="ri-save-line align-bottom me-1"></i> Confirm Sale</span>
                        <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
