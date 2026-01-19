<div class="row justify-content-center">
    <!-- Summary Panel -->
    <div class="col-lg-8">
        <h6 class="fw-semibold mb-3">Review & Create</h6>
        <div class="card border mb-3">
            <div class="card-body">
                {{-- Top Section: Product Details --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="text-muted text-uppercase fw-medium fs-11">Product Details</label>
                        <h5 class="fs-15 fw-bold mb-1">{{ $title ?: 'Untitled Product' }}</h5>
                        @php $catMatch = $categories->where('id', $categoryId)->first(); @endphp
                        <p class="text-muted mb-0">{{ $catMatch ? $catMatch->title : 'No Category Selected' }}</p>
                        <p class="mb-0 mt-1"><span class="fw-medium">{{ count($existingImages) + count($newImages) }}</span> Images, <span class="fw-medium">{{ count($existing360Images) + count($new360Images) }}</span> 360° Images</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <label class="text-muted text-uppercase fw-medium fs-11">Availability</label>
                        <div class="mt-1">
                             @if($goLiveNow)
                                <div class="badge bg-success-subtle text-success fs-12">go live now</div>
                                <div class="text-success small mt-1 fw-medium"><i class="ri-check-line me-1"></i>Access: Live</div>
                            @else
                                <div class="badge bg-warning-subtle text-warning fs-12">Scheduled: {{ $goLiveAt ? \Carbon\Carbon::parse($goLiveAt)->format('d M Y, h:i A') : 'TBD' }}</div>
                                @if($allowsEarlyAccess)
                                    <div class="d-block mt-1"><span class="badge bg-info-subtle text-info">Early Access Enabled</span></div>
                                    @if(count($earlyAccessRows) > 0)
                                        <div class="text-muted small mt-1">Configured in Early Access modal</div>
                                    @else
                                        <div class="text-danger small mt-1"><i class="ri-error-warning-line me-1"></i>Early Access enabled but not configured yet.</div>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                
                

                {{-- Access Summary Card --}}
                <div class="row">
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
                             
                             <div class="mt-2">
                                <h6 class="fs-12 text-muted text-uppercase mb-2">Clear View: {{ $this->previewVisibleTiers->count() }} Tiers</h6>
                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle me-1 mb-1">All Visible Tiers</span>
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

                             {{-- Blur List --}}
                             <div class="mt-2">
                                <h6 class="fs-12 text-muted text-uppercase mb-2">Blur View: {{ $this->previewBlurTiers->count() }} Tiers</h6>
                                @forelse($this->previewBlurTiers as $tier)
                                    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle me-1 mb-1">{{ $tier->name }}</span>
                                    @if($loop->iteration >= 6 && $loop->remaining > 0)
                                        <span class="badge rounded-pill bg-light text-muted border me-1 mb-1">+{{ $loop->remaining }} more</span>
                                        @break
                                    @endif
                                @empty
                                    <span class="text-muted small">None (All clear)</span>
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
                     {{-- Check for subset integrity (should be handled by logic, but good to flag) --}}
                     @php 
                        $visibleIds = $this->previewVisibleTiers->pluck('id');
                        $clearIds = $this->previewClearTiers->pluck('id');
                        $diff = $clearIds->diff($visibleIds); 
                     @endphp
                     @if($diff->isNotEmpty())
                        <div class="text-danger fs-12 mb-1"><i class="ri-alarm-warning-fill me-1"></i>Misconfiguration: Clear tiers must be subset of Visible tiers.</div>
                     @endif
                </div>

            </div>
        </div>
               
        <div class="alert alert-warning mt-3 mb-0 border-0 fs-13">
            <div class="d-flex align-items-center">
                <i class="ri-information-line me-2 fs-18"></i>
                <div>
                     Review your product settings and confirm they are correct.
                </div>
            </div>
        </div>
    </div>
</div>

