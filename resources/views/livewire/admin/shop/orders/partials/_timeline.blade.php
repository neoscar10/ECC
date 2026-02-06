<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Order Timeline</h5>
    </div>
    <div class="card-body">
        <div class="profile-timeline">
            <div class="accordion accordion-flush" id="accordionFlushExample">
                {{-- Placed --}}
                <div class="accordion-item border-0">
                    <div class="accordion-header" id="headingOne">
                        <a class="accordion-button p-2 shadow-none" data-bs-toggle="collapse" href="#collapseOne" aria-expanded="true">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 avatar-xs">
                                    <div class="avatar-title bg-success rounded-circle">
                                        <i class="ri-shopping-bag-line"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fs-14 mb-0">Order Placed</h6>
                                    <small class="text-muted">{{ $this->order->placed_at->format('d M, Y h:i A') }}</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                
                {{-- Paid --}}
                @if($this->order->paid_at)
                    <div class="accordion-item border-0">
                        <div class="accordion-header">
                            <a class="accordion-button p-2 shadow-none" href="javascript:void(0);">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 avatar-xs">
                                        <div class="avatar-title bg-success rounded-circle">
                                            <i class="ri-money-dollar-circle-line"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fs-14 mb-0">Payment Confirmed</h6>
                                        <small class="text-muted">{{ $this->order->paid_at->format('d M, Y h:i A') }}</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Status Updates (Simulated/Current) --}}
                <div class="accordion-item border-0">
                    <div class="accordion-header">
                        <a class="accordion-button p-2 shadow-none" href="javascript:void(0);">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 avatar-xs">
                                    <div class="avatar-title bg-primary rounded-circle">
                                        <i class="ri-truck-line"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fs-14 mb-0">Current Status: {{ ucfirst(str_replace('_', ' ', $this->order->status)) }}</h6>
                                    <small class="text-muted">{{ $this->order->updated_at->format('d M, Y h:i A') }}</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                {{-- Cancelled --}}
                @if($this->order->cancelled_at)
                    <div class="accordion-item border-0">
                        <div class="accordion-header">
                            <a class="accordion-button p-2 shadow-none" href="javascript:void(0);">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 avatar-xs">
                                        <div class="avatar-title bg-danger rounded-circle">
                                            <i class="ri-close-circle-line"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fs-14 mb-0">Order Cancelled</h6>
                                        <small class="text-muted">{{ $this->order->cancelled_at->format('d M, Y h:i A') }}</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
