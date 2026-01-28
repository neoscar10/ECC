<div class="row justify-content-center">
    <!-- Summary Panel -->
    <div class="col-lg-12">
        <h6 class="fw-semibold mb-3">Review & Create</h6>
        <div class="card border mb-3">
            <div class="card-body">
                {{-- Top Section: Details --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="text-muted text-uppercase fw-medium fs-11">Auction Lot Details</label>
                        <h5 class="fs-15 fw-bold mb-1">{{ $title ?: 'Untitled Lot' }}</h5>
                        <p class="text-muted mb-0">{{ $lot_no ?: 'Lot # (Auto)' }}</p>
                        <p class="mb-0 mt-1"><span class="fw-medium">{{ count($existingImages) + count($newImages) }}</span> Images, <span class="fw-medium">{{ count($attachmentRows ?? []) }}</span> Attachments</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <label class="text-muted text-uppercase fw-medium fs-11">Availability</label>
                        <div class="mt-1">
                             @if($goLiveNow)
                                <div class="badge bg-success-subtle text-success fs-12">LIVE NOW</div>
                                <div class="text-success small mt-1 fw-medium"><i class="ri-check-line me-1"></i>Status: Live</div>
                            @else
                                <div class="badge bg-warning-subtle text-warning fs-12">Scheduled: {{ $starts_at ? \Carbon\Carbon::parse($starts_at)->format('d M Y, h:i A') : 'TBD' }}</div>
                            @endif
                            <div class="mt-2 text-muted small">
                                Ends: {{ $ends_at ? \Carbon\Carbon::parse($ends_at)->format('d M Y, h:i A') : 'TBD' }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4 border-top pt-3">
                    <div class="col-12">
                         <label class="text-muted text-uppercase fw-medium fs-11">Pricing</label>
                         <div class="d-flex gap-4 mt-1">
                             <div>
                                 <span class="text-muted fs-12">Starting Price:</span>
                                 <div class="fw-bold">{{ $starting_price }}</div>
                             </div>
                             <div>
                                 <span class="text-muted fs-12">Reserve:</span>
                                 <div class="fw-bold">{{ $min_selling_price ?: 'None' }}</div>
                             </div>
                             <div>
                                 <span class="text-muted fs-12">Increment:</span>
                                 <div class="fw-bold">{{ $min_increment }}</div>
                             </div>
                         </div>
                    </div>
                </div>

                {{-- Access Summary Card --}}
                <div class="row border-top pt-3">
                     {{-- LEFT: VISIBILITY --}}
                     <div class="col-md-6 border-end">
                        <label class="text-muted text-uppercase fw-medium fs-11 mb-2">Visibility</label>
                        @if($restrictionMode === 'public')
                             <div class="d-flex align-items-center mb-3">
                                 <i class="ri-global-line text-success fs-20 me-2"></i>
                                 <div>
                                     <h6 class="mb-0">Public</h6>
                                     <small class="text-muted">Visible to all active tiers</small>
                                 </div>
                             </div>
                        @else
                             <div class="d-flex align-items-center mb-3">
                                 <i class="ri-lock-2-line text-warning fs-20 me-2"></i>
                                 <div>
                                     <h6 class="mb-0">Restricted</h6>
                                     <small class="text-muted">Visible to selected tiers only</small>
                                 </div>
                             </div>
                        @endif

                        <div class="mt-2">
                            <h6 class="fs-12 text-muted text-uppercase mb-2">Visible To: {{ $this->previewVisibleTiers->count() }} Tiers</h6>
                            @forelse($this->previewVisibleTiers as $tier)
                                <span class="badge rounded-pill bg-light text-dark border me-1 mb-1">{{ $tier->name }}</span>
                                @if($loop->iteration >= 6 && $loop->remaining > 0)
                                    <span class="badge rounded-pill bg-light text-muted border me-1 mb-1">+{{ $loop->remaining }} more</span>
                                    @break
                                @endif
                            @empty
                                <span class="text-danger small">No tiers selected!</span>
                            @endforelse
                        </div>
                     </div>

                     {{-- RIGHT: VIEW QUALITY --}}
                     <div class="col-md-6 ps-md-4">
                        <label class="text-muted text-uppercase fw-medium fs-11 mb-2">View Quality</label>
                         @if(!$blurEnabled)
                             <div class="d-flex align-items-center mb-3">
                                 <i class="ri-eye-line text-primary fs-20 me-2"></i>
                                 <div>
                                     <h6 class="mb-0">Full Clarity</h6>
                                     <small class="text-muted">All visible users see clear content</small>
                                 </div>
                             </div>
                         @else
                             <div class="d-flex align-items-center mb-3">
                                 <i class="ri-blur-off-line text-secondary fs-20 me-2"></i>
                                 <div>
                                     <h6 class="mb-0">Blur Enabled</h6>
                                     <small class="text-muted">Content blurred for some users</small>
                                 </div>
                             </div>

                             {{-- Clear List --}}
                             <div class="mt-2 mb-3">
                                <h6 class="fs-12 text-muted text-uppercase mb-2">Clear View: {{ $this->previewClearTiers->count() }} Tiers</h6>
                                @forelse($this->previewClearTiers as $tier)
                                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle me-1 mb-1">{{ $tier->name }}</span>
                                    @if($loop->iteration >= 6 && $loop->remaining > 0)
                                        <span class="badge rounded-pill bg-light text-muted border me-1 mb-1">+{{ $loop->remaining }} more</span>
                                        @break
                                    @endif
                                @empty
                                    <span class="text-danger small">No clear tiers!</span>
                                @endforelse
                             </div>
                         @endif
                     </div>
                </div>

                {{-- Validation Hints --}}
                <div class="mt-4 pt-3 border-top">
                     @if($restrictionMode === 'restricted' && $this->previewVisibleTiers->isEmpty())
                        <div class="text-danger fs-12 mb-1"><i class="ri-error-warning-fill me-1"></i>Visibility is Restricted but no tiers selected.</div>
                     @endif
                     @if($blurEnabled && $this->previewClearTiers->isEmpty())
                        <div class="text-danger fs-12 mb-1"><i class="ri-error-warning-fill me-1"></i>Blur is enabled but no clear tiers selected (everyone will see blur).</div>
                     @endif
                </div>

            </div>
        </div>
               
        <div class="alert alert-warning mt-3 mb-0 border-0 fs-13">
            <div class="d-flex align-items-center">
                <i class="ri-information-line me-2 fs-18"></i>
                <div>
                     Review your auction lot settings and confirm they are correct.
                </div>
            </div>
        </div>
    </div>
</div>
