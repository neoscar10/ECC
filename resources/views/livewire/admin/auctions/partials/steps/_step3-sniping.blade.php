<div class="row justify-content-center">
    <div class="col-lg-12">
        <!-- Main Card -->
        <div class="card border shadow-none mb-0">
            <div class="card-header bg-light-subtle border-bottom-0 pb-0">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                <i class="mdi mdi-timer-sand"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">Anti-Sniping Protection</h5>
                        <p class="text-muted mb-0">Prevent last-second bids from ending the auction prematurely.</p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="badge bg-light text-secondary border">Optional</span>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Master Toggle -->
                <div class="form-check form-switch form-switch-custom form-switch-primary mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" id="snipingEnabled" wire:model.live="anti_sniping_enabled">
                    <label class="form-check-label fw-medium ms-2" for="snipingEnabled">
                        Enable Anti-Sniping Extension logic
                    </label>
                </div>

                <!-- Animated Collapse for Options -->
                <div class="{{ $anti_sniping_enabled ? 'd-block' : 'd-none' }}">
                    <hr class="border-top-dashed my-4">
                    
                    <div class="row g-4">
                        <!-- Trigger Window -->
                        <div class="col-md-4">
                            <label class="form-label d-flex align-items-center">
                                Trigger Window
                                <i class="ri-information-line text-muted ms-1" data-bs-toggle="tooltip" title="If a bid is placed within this many seconds of the end time."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" wire:model.live="trigger_window_seconds" min="10" placeholder="120">
                                <span class="input-group-text text-muted">sec</span>
                            </div>
                            <div class="text-muted fs-11 mt-1">Recommended: 120s (2 mins)</div>
                            @error('trigger_window_seconds') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>

                        <!-- Extend By -->
                        <div class="col-md-4">
                            <label class="form-label d-flex align-items-center">
                                Extension Length
                                <i class="ri-information-line text-muted ms-1" data-bs-toggle="tooltip" title="How much time starts added to the clock."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" wire:model.live="extend_by_seconds" min="10" placeholder="60">
                                <span class="input-group-text text-muted">sec</span>
                            </div>
                            <div class="text-muted fs-11 mt-1">Recommended: 60s (1 min)</div>
                            @error('extend_by_seconds') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>

                        <!-- Max Extensions -->
                        <div class="col-md-4">
                            <label class="form-label d-flex align-items-center">
                                Max Extensions
                                <i class="ri-information-line text-muted ms-1" data-bs-toggle="tooltip" title="Maximum number of times the auction can be extended."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" wire:model.live="max_extensions" min="1" placeholder="10">
                                <span class="input-group-text text-muted">times</span>
                            </div>
                             <div class="text-muted fs-11 mt-1">Recommended: 5-10 times</div>
                            @error('max_extensions') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Live Outcome Preview -->
                    <div class="mt-4">
                        <div class="alert alert-info border-0 d-flex align-items-center mb-0" role="alert">
                            <i class="mdi mdi-information-outline fs-20 me-2"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-1 fs-13">Outcome Preview</h6>
                                <p class="mb-0 fs-13">
                                    If a bid is placed within the final <span class="fw-bold text-dark">{{ $trigger_window_seconds ?? 0 }} seconds</span>, 
                                    the auction will extend by <span class="fw-bold text-dark">{{ $extend_by_seconds ?? 0 }} seconds</span> 
                                    (up to {{ $max_extensions ?? 0 }} times).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Disabled State Placeholder -->
                <div class="{{ !$anti_sniping_enabled ? 'd-block' : 'd-none' }}">
                   <div class="alert alert-light border text-center text-muted mt-3 mb-0">
                        <i class="mdi mdi-sleep fs-24 d-block mb-2"></i>
                        Anti-sniping protection is disabled. The auction will end exactly at the scheduled time regardless of last-second bids.
                   </div>
                </div>

            </div>
        </div>
    </div>
</div>
