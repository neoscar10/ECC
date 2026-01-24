<div class="card">
     <div class="card-header">
        <h5 class="card-title mb-0">Auction Details</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-borderless table-sm">
                        <tbody>
                            <tr><td class="text-muted">Status</td><td class="fw-medium">{{ ucfirst($lot->status) }}</td></tr>
                            <tr><td class="text-muted">Starting Price</td><td class="fw-medium">{{ $lot->currency }} {{ number_format($lot->starting_price) }}</td></tr>
                            <tr><td class="text-muted">Reserve Price</td><td class="fw-medium">{{ $lot->min_selling_price ? $lot->currency . ' ' . number_format($lot->min_selling_price) : '-' }}</td></tr>
                            <tr><td class="text-muted">Min Increment</td><td class="fw-medium">{{ $lot->currency }} {{ number_format($lot->min_increment) }}</td></tr>
                            <tr><td class="text-muted">Start Time</td><td class="fw-medium">{{ $lot->starts_at?->format('d M H:i') }}</td></tr>
                            <tr><td class="text-muted">End Time</td><td class="fw-medium">{{ $lot->ends_at?->format('d M H:i') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                 <h6 class="fs-13 text-uppercase text-muted mb-2">Access Settings</h6>
                 <div class="mb-3">
                     <div class="d-flex align-items-center mb-1">
                         <i class="ri-shield-user-line me-2"></i> Mode: 
                         <span class="fw-bold ms-1">{{ $accessSummary }}</span>
                     </div>
                     @if($lot->restriction_mode == 'restricted')
                         <div class="d-flex flex-wrap gap-1 mt-1">
                             @foreach($lot->visibilityTiers as $tier)
                                 <span class="badge bg-primary-subtle text-primary">{{ $tier->name }}</span>
                             @endforeach
                         </div>
                     @endif
                 </div>
                 
                 <h6 class="fs-13 text-uppercase text-muted mb-2">Anti-Sniping</h6>
                 <p class="mb-1 fs-13"><span class="text-muted">Enabled:</span> {{ $lot->anti_sniping_enabled ? 'Yes' : 'No' }}</p>
                 @if($lot->anti_sniping_enabled)
                     <p class="mb-1 fs-13"><span class="text-muted">Window:</span> {{ $lot->trigger_window_seconds }}s</p>
                     <p class="mb-1 fs-13"><span class="text-muted">Extend:</span> {{ $lot->extend_by_seconds }}s</p>
                     <p class="mb-0 fs-13"><span class="text-muted">Max:</span> {{ $lot->max_extensions }} (Used: {{ $lot->extensions_used ?? 0 }})</p>
                 @endif
            </div>
        </div>
        
        @if($lot->earlyAccessWindows->isNotEmpty())
            <hr>
            <h6 class="fs-13 text-uppercase text-muted mb-2">Early Access Windows</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Tier</th><th>Access Starts</th></tr></thead>
                    <tbody>
                        @foreach($lot->earlyAccessWindows as $ea)
                            <tr>
                                <td>{{ $ea->tier->name }}</td>
                                <td>{{ $ea->access_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
