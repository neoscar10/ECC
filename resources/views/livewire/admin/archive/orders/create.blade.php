<div>
    {{-- Create Order Modal --}}
    @if($showModal)
    <div class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
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
                                        
                                        <div class="d-flex justify-content-between align-items-center py-2 border-top border-dashed">
                                            <span class="text-muted">Available Stock:</span>
                                            <span class="fw-bold fs-16 {{ $selectedProduct->quantity < 1 ? 'text-danger' : 'text-success' }}">{{ $selectedProduct->quantity }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-top border-dashed">
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
                                <div class="mb-3 position-relative">
                                    <label class="form-label">Search Product</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                                        <input type="text" wire:model.live.debounce.300ms="productSearch" class="form-control" placeholder="Type product name...">
                                    </div>
                                    
                                    @if(count($searchResults) > 0)
                                        <div class="list-group position-absolute w-100 shadow mt-1" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                            @foreach($searchResults as $result)
                                                <button type="button" wire:click="selectProduct({{ $result->id }})" class="list-group-item list-group-item-action d-flex align-items-center">
                                                    @if($result->images->first())
                                                        <img src="{{ Storage::url($result->images->first()->image_path) }}" class="avatar-xs rounded-circle me-2">
                                                    @endif
                                                    <div>
                                                        <div class="fw-medium">{{ $result->title }}</div>
                                                        <small class="text-muted">Stock: {{ $result->quantity }}</small>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @elseif(strlen($productSearch) > 2)
                                        <div class="alert alert-warning mt-2 mb-0">No matching products found with stock > 0.</div>
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
                                                <button type="button" wire:click="$set('user_id', null)" class="btn btn-sm btn-ghost-danger">Change</button>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="mb-3 position-relative">
                                        <label class="form-label">Search User</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ri-user-search-line"></i></span>
                                            <input type="text" wire:model.live.debounce.300ms="userSearch" class="form-control" placeholder="Name or Email...">
                                        </div>
                                         @if(count($userSearchResults) > 0)
                                            <div class="list-group position-absolute w-100 shadow mt-1" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                                @foreach($userSearchResults as $u)
                                                    <button type="button" wire:click="selectUser({{ $u->id }})" class="list-group-item list-group-item-action">
                                                        <div class="fw-medium">{{ $u->name }}</div>
                                                        <small class="text-muted">{{ $u->email }}</small>
                                                    </button>
                                                @endforeach
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
                    <button type="button" class="btn btn-success" wire:click="store" @if(!$selectedProduct) disabled @endif>
                        <i class="ri-save-line align-bottom me-1"></i> Confirm Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
