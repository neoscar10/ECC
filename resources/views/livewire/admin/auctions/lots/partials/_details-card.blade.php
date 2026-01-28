<div class="card">
     <div class="card-header">
        <h5 class="card-title mb-0">Auction Details</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-sm align-middle fs-13 mb-0">
                        <tbody>
                            <tr class="border-bottom-dashed">
                                <td class="text-muted w-25">Status</td>
                                <td class="fw-medium">
                                    @if($lot->status === 'live')
                                        <span class="badge bg-success-subtle text-success text-uppercase">Live</span>
                                    @elseif($lot->status === 'upcoming')
                                        <span class="badge bg-warning-subtle text-warning text-uppercase">Upcoming</span>
                                    @elseif($lot->status === 'ended')
                                        <span class="badge bg-dark-subtle text-dark text-uppercase">Ended</span>
                                    @elseif($lot->status === 'unsold')
                                        <span class="badge bg-warning-subtle text-warning text-uppercase">Unsold</span>
                                    @elseif($lot->status === 'cancelled')
                                        <span class="badge bg-danger-subtle text-danger text-uppercase">Cancelled</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ ucfirst($lot->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="border-bottom-dashed">
                                <td class="text-muted">Starting Price</td>
                                <td class="fw-medium">{{ $lot->currency }} {{ number_format($lot->starting_price) }}</td>
                            </tr>
                            <tr class="border-bottom-dashed">
                                <td class="text-muted">Reserve Price</td>
                                <td class="fw-medium">{{ $lot->min_selling_price ? $lot->currency . ' ' . number_format($lot->min_selling_price) : 'No Reserve' }}</td>
                            </tr>
                            <tr class="border-bottom-dashed">
                                <td class="text-muted">Min Increment</td>
                                <td class="fw-medium">{{ $lot->currency }} {{ number_format($lot->min_increment) }}</td>
                            </tr>
                            <tr class="border-bottom-dashed">
                                <td class="text-muted">Start Time</td>
                                <td class="fw-medium">
                                    @if($lot->starts_at)
                                        <div class="d-flex align-items-center">
                                            <i class="ri-calendar-event-line text-muted me-2"></i>
                                            {{ $lot->starts_at->format('d M Y, h:i A') }}
                                        </div>
                                    @else -- @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">End Time</td>
                                <td class="fw-medium">
                                     @if($lot->ends_at)
                                        <div class="d-flex align-items-center">
                                            <i class="ri-flag-line text-muted me-2"></i>
                                            {{ $lot->ends_at->format('d M Y, h:i A') }}
                                        </div>
                                    @else -- @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6 border-start-md ps-md-4">
                 <h6 class="fs-13 text-uppercase text-muted mb-3">Access Settings</h6>
                 <div class="mb-4">
                     <div class="d-flex align-items-center mb-2">
                         <div class="avatar-xs me-2">
                             <div class="avatar-title rounded bg-light text-primary fs-16">
                                 <i class="ri-shield-user-line"></i>
                             </div>
                         </div>
                         <div>
                             <h6 class="fs-14 mb-0 fw-semibold">{{ $accessSummary }}</h6>
                         </div>
                     </div>
                     
                     @if($lot->restriction_mode == 'restricted')
                         <div class="d-flex flex-wrap gap-1 mt-2 ps-1">
                             @foreach($lot->visibilityTiers as $tier)
                                 <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $tier->name }}</span>
                             @endforeach
                         </div>
                     @endif
                 </div>
                 
                 <div class="d-flex align-items-center mb-3">
                     <h6 class="fs-13 text-uppercase text-muted mb-0 flex-grow-1">Anti-Sniping</h6>
                     <hr class="flex-grow-1 ms-3 my-0 border-dashed text-muted opacity-25">
                 </div>

                 <div class="row g-3">
                     {{-- Status --}}
                     <div class="col-6">
                         <div class="p-2 border rounded bg-light h-100">
                             <div class="text-muted fs-11 text-uppercase mb-1">Status</div>
                             <div>
                                 @if($lot->anti_sniping_enabled)
                                     <span class="badge bg-success-subtle text-success">Enabled</span>
                                 @else
                                     <span class="badge bg-secondary-subtle text-muted">Disabled</span>
                                 @endif
                             </div>
                         </div>
                     </div>

                     {{-- Max Extensions --}}
                     <div class="col-6">
                         <div class="p-2 border rounded bg-light h-100">
                             <div class="text-muted fs-11 text-uppercase mb-1">Max Extensions</div>
                             <div class="fw-medium text-dark">
                                 {{ $lot->anti_sniping_enabled ? ($lot->max_extensions ?? 'Unlimited') : '—' }}
                             </div>
                         </div>
                     </div>

                     {{-- Extension Window --}}
                     <div class="col-6">
                         <div class="p-2 border rounded bg-light h-100">
                             <div class="text-muted fs-11 text-uppercase mb-1">Extension Window</div>
                             <div class="fw-medium text-dark">
                                 @if($lot->anti_sniping_enabled && $lot->extend_by_seconds)
                                     {{ $lot->extend_by_seconds < 60 ? $lot->extend_by_seconds . ' sec' : round($lot->extend_by_seconds/60, 1) . ' min' }}
                                 @else
                                     <span class="text-muted">—</span>
                                 @endif
                             </div>
                         </div>
                     </div>

                     {{-- Trigger Threshold --}}
                     <div class="col-6">
                         <div class="p-2 border rounded bg-light h-100">
                             <div class="text-muted fs-11 text-uppercase mb-1">Trigger Threshold</div>
                             <div class="fw-medium text-dark">
                                 @if($lot->anti_sniping_enabled && $lot->trigger_window_seconds)
                                    Last {{ $lot->trigger_window_seconds < 60 ? $lot->trigger_window_seconds . ' sec' : round($lot->trigger_window_seconds/60, 1) . ' min' }}
                                 @else
                                     <span class="text-muted">—</span>
                                 @endif
                             </div>
                         </div>
                     </div>
                     
                    
                 </div>
            </div>

             {{-- Behavior Summary --}}
                     <div class="col-12">
                         <div class="p-2 border rounded bg-light-subtle">
                             <div class="d-flex">
                                 <i class="ri-information-line text-muted me-2 mt-1"></i>
                                 <p class="fs-12 text-muted mb-0">
                                     @if($lot->anti_sniping_enabled)
                                         If a bid is placed within the last <strong>{{ $lot->trigger_window_seconds }}s</strong>, 
                                         the auction extends by <strong>{{ $lot->extend_by_seconds }}s</strong> 
                                         (up to {{ $lot->max_extensions }} times).
                                     @else
                                         Anti-sniping is currently disabled for this auction.
                                     @endif
                                 </p>
                             </div>
                         </div>
                     </div>
        </div>
        
        @if($lot->earlyAccessWindows->isNotEmpty())
            <div class="mt-4 pt-3 border-top">
                <h6 class="fs-13 text-uppercase text-muted mb-3">Early Access Windows</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered fs-13 mb-0">
                        <thead class="bg-light text-muted"><tr><th>Membership Tier</th><th>Access Start Time</th></tr></thead>
                        <tbody>
                            @foreach($lot->earlyAccessWindows as $ea)
                                <tr>
                                    <td class="fw-medium">{{ $ea->tier->name }}</td>
                                    <td>{{ $ea->access_at->format('d M Y, h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
