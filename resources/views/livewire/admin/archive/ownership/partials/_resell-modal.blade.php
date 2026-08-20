<div>
    @if($showResellModal && $resellOrder)
    <div class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resell Archive Item</h5>
                    <button type="button" class="btn-close" wire:click="closeResellModal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        {{-- Left Col: Original Sale & Resale Pricing --}}
                        <div class="col-md-7 p-4 border-end">
                            <h6 class="text-uppercase fw-semibold mb-3">Resale Details</h6>
                            
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0 me-3">
                                             @if($product->image_url)
                                                <img src="{{ $product->image_url }}" class="rounded avatar-md object-fit-cover">
                                            @else
                                                <div class="avatar-md bg-white rounded d-flex align-items-center justify-content-center">
                                                    <i class="ri-image-line text-muted"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <h5 class="fs-15 mb-1">{{ $product->title }}</h5>
                                            <p class="text-muted mb-0">Original Owner: {{ $resellOrder->buyer_type === 'registered' ? $resellOrder->buyer->name : $resellOrder->external_name }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center py-2 border-top b">
                                        <span class="text-muted">Available to Resell:</span>
                                        <span class="fw-bold fs-16 text-primary">{{ $resellOrder->qty - $resellOrder->resold_qty }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-2 border-top ">
                                        <span class="text-muted">Original Purchase Price (Unit):</span>
                                        <span class="fw-medium text-danger">INR {{ number_format($resellOrder->unit_price_inr, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label">Resale Qty <span class="text-danger">*</span></label>
                                    <input type="number" wire:model.live="resaleQty" class="form-control" min="1" max="{{ $resellOrder->qty - $resellOrder->resold_qty }}">
                                    @error('resaleQty') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Owner's Price <span class="text-danger">*</span></label>
                                    <input type="number" wire:model.live="resaleOwnerAskingPriceInr" class="form-control" step="0.01">
                                    @error('resaleOwnerAskingPriceInr') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Platform Price <span class="text-danger">*</span></label>
                                    <input type="number" wire:model.live="resaleUnitPriceInr" class="form-control" step="0.01">
                                    @error('resaleUnitPriceInr') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="col-12 mt-3">
                                    @php
                                        $ownerProfitPerUnit = (float)$resaleOwnerAskingPriceInr - (float)$resellOrder->unit_price_inr;
                                        $totalOwnerProfit = $ownerProfitPerUnit * (float)$resaleQty;
                                        
                                        $platformProfitPerUnit = (float)$resaleUnitPriceInr - (float)$resaleOwnerAskingPriceInr;
                                        $totalPlatformProfit = $platformProfitPerUnit * (float)$resaleQty;
                                    @endphp
                                    <div class="alert {{ $totalOwnerProfit >= 0 ? 'alert-success' : 'alert-danger' }} border-0 mb-2 py-2">
                                        <strong>Owner's Profit:</strong> 
                                        INR {{ number_format($totalOwnerProfit, 2) }}
                                        <span class="fs-12 text-muted">({{ $ownerProfitPerUnit >= 0 ? '+' : '' }}{{ number_format($ownerProfitPerUnit, 2) }} per unit)</span>
                                    </div>
                                    <div class="alert {{ $totalPlatformProfit >= 0 ? 'alert-info' : 'alert-danger' }} border-0 mb-0 py-2">
                                        <strong>Platform's Profit:</strong> 
                                        INR {{ number_format($totalPlatformProfit, 2) }}
                                        <span class="fs-12 text-muted">({{ $platformProfitPerUnit >= 0 ? '+' : '' }}{{ number_format($platformProfitPerUnit, 2) }} per unit)</span>
                                    </div>
                                </div>

                                <div class="col-12 mt-3">
                                    <label class="form-label">Notes (Optional)</label>
                                    <textarea wire:model="resaleNotes" class="form-control" rows="2" placeholder="Internal notes about this resale..."></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Right Col: New Buyer Info --}}
                        <div class="col-md-5 p-4 bg-light">
                            <h6 class="text-uppercase fw-semibold mb-3">New Buyer Details</h6>
                            
                            <div class="mb-3">
                                <label class="form-label d-block">Buyer Type</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="resaleBuyerType" id="rbt_reg" value="registered" wire:model.live="resaleBuyerType">
                                    <label class="btn btn-outline-primary" for="rbt_reg">Registered User</label>

                                    <input type="radio" class="btn-check" name="resaleBuyerType" id="rbt_ext" value="external" wire:model.live="resaleBuyerType">
                                    <label class="btn btn-outline-primary" for="rbt_ext">External Guest</label>
                                </div>
                            </div>
                            
                            @if($resaleBuyerType === 'registered')
                                @if($resaleUserId)
                                    {{-- Selected User Display --}}
                                    <div class="card border mb-3">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="fs-14 mb-1">{{ $resaleUserSearch }}</h6>
                                                    <p class="text-muted mb-0 fs-11">Registered Member</p>
                                                </div>
                                            </div>
                                            <button type="button" wire:click="$set('resaleUserId', null)" class="btn btn-sm btn-danger mt-2">Change User</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="mb-3 position-relative" wire:key="resale-user-search" x-on:click.outside="$wire.closeResaleDropdowns()">
                                        <label class="form-label">Search User</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ri-user-search-line"></i></span>
                                            <input type="text" 
                                                wire:model.live.debounce.250ms="resaleUserSearch" 
                                                wire:focus="openResaleUserDropdown"
                                                wire:click.stop="openResaleUserDropdown"
                                                wire:keydown.escape="closeResaleDropdowns"
                                                class="form-control" 
                                                placeholder="Name or Email..."
                                                autocomplete="off">
                                        </div>
                                        
                                        @if($showResaleUserDropdown)
                                            <div class="dropdown-menu show w-100 shadow mt-1 p-0 border-0 overflow-hidden" style="position: absolute; inset: 100% auto auto 0; z-index: 1050;">
                                                <div class="list-group list-group-flush" style="max-height: 260px; overflow-y: auto;">
                                                    @forelse($resaleUserSearchResults as $u)
                                                        <button type="button" 
                                                            wire:mousedown.prevent="selectResaleUser({{ $u->id }}, '{{ $u->name }}')" 
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
                                        @error('resaleUserId') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                            @else
                                {{-- External Form --}}
                                <div class="vstack gap-3 mb-3">
                                    <div>
                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" wire:model="resaleExternalName" class="form-control">
                                        @error('resaleExternalName') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="form-label">Email</label>
                                        <input type="email" wire:model="resaleExternalEmail" class="form-control">
                                    </div>
                                    <div>
                                        <label class="form-label">Phone</label>
                                        <input type="text" wire:model="resaleExternalPhone" class="form-control">
                                    </div>
                                </div>
                            @endif
                            
                            {{-- Fulfillment Method --}}
                            <div class="mb-3">
                                <label class="form-label d-block">Fulfillment for New Buyer</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="resaleFulfillmentMethod" id="rfm_del" value="delivery" wire:model.live="resaleFulfillmentMethod">
                                    <label class="btn btn-outline-secondary" for="rfm_del">
                                        <i class="ri-truck-line align-middle me-1"></i> Delivery
                                    </label>

                                    <input type="radio" class="btn-check" name="resaleFulfillmentMethod" id="rfm_vlt" value="vault" wire:model.live="resaleFulfillmentMethod" 
                                        @if(!$canVault || $resaleBuyerType === 'external') disabled @endif>
                                    <label class="btn btn-outline-secondary" for="rfm_vlt">
                                        <i class="ri-safe-2-line align-middle me-1"></i> Lock in Vault
                                    </label>
                                </div>
                                @if($resaleBuyerType === 'registered' && !$canVault && $resaleUserId)
                                    <div class="text-warning fs-11 mt-1"><i class="ri-alert-line"></i> User's membership tier does not support Vault Access.</div>
                                @elseif($resaleBuyerType === 'external')
                                    <div class="text-muted fs-11 mt-1">Vault access available for registered members only.</div>
                                @endif
                                @error('resaleFulfillmentMethod') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                            </div>

                            {{-- Summary Total --}}
                             <div class="mt-4 pt-4 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-0">
                                    <span class="text-muted fs-14">Total Invoice to Buyer</span>
                                    <span class="fs-20 fw-bold text-primary">INR {{ number_format( (float)$resaleQty * (float)$resaleUnitPriceInr, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    @error('general') 
                        <span class="text-danger me-auto"><i class="ri-error-warning-line"></i> {{ $message }}</span> 
                    @enderror
                    
                    <button type="button" class="btn btn-light" wire:click="closeResellModal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="submitResale" wire:loading.attr="disabled" wire:target="submitResale">
                        <span wire:loading.remove wire:target="submitResale"><i class="ri-exchange-dollar-line align-bottom me-1"></i> Complete Resale</span>
                        <span wire:loading wire:target="submitResale"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
