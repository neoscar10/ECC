<div>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <div class="text-center mb-5">
                    <h2 class="display-6 fw-bold mb-3">Secure Payment Checkout</h2>
                    <p class="text-muted">Complete your payment for the archive enquiry.</p>
                </div>

                @if(session()->has('error'))
                    <div class="alert alert-danger shadow-sm border-0 mb-4">
                        <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
                    </div>
                @endif
                
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="row g-0">
                            <!-- Product Image/Details Area -->
                            <div class="col-12 bg-light p-4 border-bottom">
                                <div class="d-flex align-items-center">
                                    @if($enquiry->product->images->isNotEmpty())
                                        <img src="{{ Storage::url($enquiry->product->images->first()->image_path) }}" 
                                             class="rounded-3 shadow-sm" 
                                             alt="{{ $enquiry->product->title }}"
                                             style="width: 80px; height: 80px; object-fit: cover;">
                                    @else
                                        <div class="rounded-3 bg-white shadow-sm d-flex align-items-center justify-content-center" 
                                             style="width: 80px; height: 80px;">
                                            <i class="ri-image-line text-muted fs-3"></i>
                                        </div>
                                    @endif
                                    
                                    <div class="ms-4">
                                        <h5 class="fw-bold mb-1">{{ $enquiry->product->title }}</h5>
                                        <p class="text-muted mb-0 fs-14">Enquiry #{{ str_pad($enquiry->id, 6, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Invoice Summary -->
                            <div class="col-12 p-4 p-md-5">
                                <h6 class="text-uppercase text-muted fw-semibold mb-4 fs-12 tracking-wide">Payment Summary</h6>
                                
                                <div class="d-flex justify-content-between mb-3 fs-15">
                                    <span class="text-muted">Item Subtotal</span>
                                    <span class="fw-medium">₹{{ number_format($enquiry->payment_amount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 fs-15">
                                    <span class="text-muted">Taxes & Fees</span>
                                    <span class="fw-medium">₹0.00</span>
                                </div>
                                
                                <hr class="my-4 border-dashed">
                                
                                <div class="d-flex justify-content-between align-items-center mb-5">
                                    <span class="fw-bold fs-5 text-dark">Total Amount</span>
                                    <span class="fw-bold fs-3 text-primary">₹{{ number_format($enquiry->payment_amount, 2) }}</span>
                                </div>
                                
                                <div class="d-grid gap-3">
                                    <button wire:click="processPayment" wire:loading.attr="disabled" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                                        <span wire:loading.remove wire:target="processPayment">
                                            <i class="ri-secure-payment-line align-bottom me-2"></i> Pay Now with {{ ucfirst($enquiry->payment_gateway ?? 'Card/UPI') }}
                                        </span>
                                        <span wire:loading wire:target="processPayment">
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Processing...
                                        </span>
                                    </button>
                                </div>
                                
                                <div class="text-center mt-4 text-muted fs-12">
                                    <i class="ri-lock-fill align-middle me-1"></i> Payments are secure and encrypted.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
