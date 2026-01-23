<div class="row g-4 justify-content-center">
    <div class="col-lg-8">
        <h6 class="fw-semibold mb-3">Anti-Sniping Configuration</h6>
        
        <div class="card bg-light border p-3 h-100">
            <div class="form-check form-switch form-switch-lg mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="snipingEnabled" wire:model.live="anti_sniping_enabled">
                <label class="form-check-label fw-bold" for="snipingEnabled">Enable Anti-Sniping Protection</label>
            </div>
            
            <p class="text-muted small mb-4">
                Anti-sniping extends the auction duration if a bid is placed in the final moments, preventing last-second sniping bots or users.
            </p>

            @if($anti_sniping_enabled)
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Trigger Window (Secs)</label>
                        <input type="number" class="form-control" wire:model="trigger_window_seconds" placeholder="120">
                        <div class="form-text fs-11">Time remaining to trigger extension</div>
                        @error('trigger_window_seconds') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Extend By (Secs)</label>
                        <input type="number" class="form-control" wire:model="extend_by_seconds" placeholder="60">
                         <div class="form-text fs-11">Seconds to add to ends_at</div>
                        @error('extend_by_seconds') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Max Extensions</label>
                        <input type="number" class="form-control" wire:model="max_extensions" placeholder="10">
                         <div class="form-text fs-11">Max times it can extend</div>
                        @error('max_extensions') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="alert alert-warning mt-3 mb-0 border-0 fs-12">
                    <i class="ri-alarm-warning-fill me-1"></i> Example: If a bid is placed with less than <strong>{{ $trigger_window_seconds }}s</strong> remaining, the auction will extend by <strong>{{ $extend_by_seconds }}s</strong>.
                </div>
            @endif
        </div>
    </div>
</div>
