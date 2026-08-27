<div>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-7">
                
                <!-- Brand Header -->
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="d-inline-block mb-3">
                        <img src="{{ asset('ecc_logo_dark.png') }}" alt="ECC Logo" style="height: 60px; object-fit: contain;">
                    </a>
                    <h3 class="fw-bold mb-1 text-uppercase tracking-wide">Shipping Destination Details</h3>
                    <p class="text-muted fs-14">Please provide your preferred delivery address for this archive item.</p>
                </div>

                <!-- Product Summary Card -->
                <div class="card shadow-sm border-0 rounded-4 mb-4 overflow-hidden">
                    <div class="card-body p-4 bg-light">
                        <div class="d-flex align-items-center gap-3">
                            <div class="flex-shrink-0">
                                @if($enquiry->product && $enquiry->product->images->first())
                                    <img src="{{ Storage::url($enquiry->product->images->first()->image_path) }}" 
                                         class="rounded-3 shadow-sm" 
                                         alt="{{ $enquiry->product->title }}" 
                                         style="width: 75px; height: 75px; object-fit: cover;">
                                @else
                                    <div class="rounded-3 bg-white shadow-sm d-flex align-items-center justify-content-center" style="width: 75px; height: 75px;">
                                        <i class="ri-image-line text-muted fs-2"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="badge bg-warning-subtle text-warning text-uppercase fs-11 fw-semibold mb-1">Archive Enquiry #{{ str_pad($enquiry->id, 6, '0', STR_PAD_LEFT) }}</span>
                                <h5 class="fw-bold mb-1 text-truncate">{{ $enquiry->product->title ?? 'Archive Item' }}</h5>
                                <p class="text-muted mb-0 fs-13">Customer: <strong class="text-dark">{{ $enquiry->contact_name }}</strong> ({{ $enquiry->contact_email }})</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if(session()->has('success'))
                    <div class="alert alert-success shadow-sm border-0 mb-4 d-flex align-items-center gap-2">
                        <i class="ri-checkbox-circle-fill fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if($submittedSuccessfully)
                    <!-- Confirmation Screen -->
                    <div class="card shadow-lg border-0 rounded-4 overflow-hidden text-center p-4 p-md-5">
                        <div class="mb-4">
                            <div class="avatar-lg bg-success-subtle text-success rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                <i class="ri-truck-line fs-1"></i>
                            </div>
                        </div>

                        <h4 class="fw-bold mb-2">Delivery Address Submitted</h4>
                        <p class="text-muted mb-4 fs-14">Your shipping details have been securely saved for this enquiry item.</p>

                        <div class="bg-light rounded-3 p-4 text-start mb-4 fs-14">
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <span class="text-muted">Recipient Name</span>
                                <strong class="text-dark">{{ $enquiry->delivery_name }}</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <span class="text-muted">Phone Number</span>
                                <strong class="text-dark">{{ $enquiry->delivery_phone }}</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <span class="text-muted">Address</span>
                                <strong class="text-dark text-end">{{ $enquiry->delivery_line1 }} {{ $enquiry->delivery_line2 }}</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <span class="text-muted">City / State</span>
                                <strong class="text-dark">{{ $enquiry->delivery_city }}, {{ $enquiry->delivery_state }}</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <span class="text-muted">Postal / PIN Code</span>
                                <strong class="text-dark">{{ $enquiry->delivery_postal_code }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Country</span>
                                <strong class="text-dark">{{ $enquiry->delivery_country }}</strong>
                            </div>
                        </div>

                        <div>
                            <button type="button" wire:click="editAddress" class="btn btn-outline-primary rounded-pill px-4">
                                <i class="ri-edit-line me-1"></i> Update Address Details
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Address Input Form Card -->
                    <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                        <div class="card-header bg-white p-4 border-bottom">
                            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="ri-map-pin-2-line text-primary fs-4"></i>
                                <span>Shipping Destination Form</span>
                            </h5>
                        </div>
                        <div class="card-body p-4 p-md-5">
                            <form wire:submit.prevent="saveAddress">
                                <div class="row g-3">
                                    
                                    <!-- Full Name -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold fs-13">Recipient Full Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="ri-user-3-line"></i></span>
                                            <input type="text" wire:model="delivery_name" class="form-control @error('delivery_name') is-invalid @enderror" placeholder="e.g. John Doe">
                                        </div>
                                        @error('delivery_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <!-- Phone Number -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold fs-13">Phone Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="ri-phone-line"></i></span>
                                            <input type="text" wire:model="delivery_phone" class="form-control @error('delivery_phone') is-invalid @enderror" placeholder="e.g. +91 9876543210">
                                        </div>
                                        @error('delivery_phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <!-- Address Line 1 -->
                                    <div class="col-12">
                                        <label class="form-label fw-semibold fs-13">Address Line 1 <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="ri-map-pin-line"></i></span>
                                            <input type="text" wire:model="delivery_line1" class="form-control @error('delivery_line1') is-invalid @enderror" placeholder="Street address, house number, building name">
                                        </div>
                                        @error('delivery_line1') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <!-- Address Line 2 -->
                                    <div class="col-12">
                                        <label class="form-label fw-semibold fs-13">Address Line 2 <span class="text-muted fw-normal">(Optional)</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="ri-building-line"></i></span>
                                            <input type="text" wire:model="delivery_line2" class="form-control @error('delivery_line2') is-invalid @enderror" placeholder="Apartment, suite, unit, landmark">
                                        </div>
                                        @error('delivery_line2') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <!-- City -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold fs-13">City <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="ri-government-line"></i></span>
                                            <input type="text" wire:model="delivery_city" class="form-control @error('delivery_city') is-invalid @enderror" placeholder="City name">
                                        </div>
                                        @error('delivery_city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <!-- State -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold fs-13">State / Province <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="ri-map-2-line"></i></span>
                                            <input type="text" wire:model="delivery_state" class="form-control @error('delivery_state') is-invalid @enderror" placeholder="State or Province">
                                        </div>
                                        @error('delivery_state') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <!-- Postal Code -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold fs-13">Postal Code / PIN Code <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="ri-mail-line"></i></span>
                                            <input type="text" wire:model="delivery_postal_code" class="form-control @error('delivery_postal_code') is-invalid @enderror" placeholder="e.g. 400001">
                                        </div>
                                        @error('delivery_postal_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <!-- Country -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold fs-13">Country <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="ri-earth-line"></i></span>
                                            <select wire:model="delivery_country" class="form-select @error('delivery_country') is-invalid @enderror">
                                                @foreach($countries as $c)
                                                    <option value="{{ $c }}">{{ $c }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('delivery_country') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <!-- Submit Action -->
                                    <div class="col-12 mt-4">
                                        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm">
                                            <span wire:loading.remove wire:target="saveAddress">
                                                <i class="ri-check-double-line align-bottom me-1"></i> Submit Delivery Details
                                            </span>
                                            <span wire:loading wire:target="saveAddress">
                                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                Saving Address...
                                            </span>
                                        </button>
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
