<div class="row justify-content-center">
    <!-- Summary Panel -->
    <div class="col-lg-8">
        <h6 class="fw-semibold mb-3">Review & Create</h6>
        <div class="card border mb-0">
            <div class="card-body">
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
                                <div class="badge bg-success-subtle text-success fs-12">Live Now</div>
                            @else
                                <div class="badge bg-warning-subtle text-warning fs-12">Scheduled: {{ $goLiveAt ? \Carbon\Carbon::parse($goLiveAt)->format('d M Y, h:i A') : 'TBD' }}</div>
                                @if($allowsEarlyAccess)
                                    <div class="d-block mt-1"><span class="badge bg-info-subtle text-info">Early Access Enabled</span></div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                
                <hr class="border-dashed my-4">

                <div class="row">
                     <div class="col-md-6">
                        <label class="text-muted text-uppercase fw-medium fs-11 mb-2">Visibility Settings</label>
                        @if($restrictionMode === 'public')
                             <div class="d-flex align-items-center">
                                 <i class="ri-global-line text-success fs-20 me-2"></i>
                                 <div>
                                     <h6 class="mb-0">Public Access</h6>
                                     <small class="text-muted">Visible to everyone</small>
                                 </div>
                             </div>
                        @else
                             <div class="d-flex align-items-center mb-2">
                                 <i class="ri-lock-2-line text-warning fs-20 me-2"></i>
                                 <div>
                                     <h6 class="mb-0">Restricted Access ({{ ucfirst($restrictionType) }})</h6>
                                 </div>
                             </div>
                             <div class="ps-4 ms-1 border-start">
                                 @if($restrictionType === 'hierarchical')
                                     @php $minTier = $membershipTiers->where('id', $restrictedMinTierId)->first(); @endphp
                                     <p class="mb-0 small">Min Tier: <strong>{{ $minTier ? $minTier->name : 'N/A' }}</strong></p>
                                 @elseif($restrictionType === 'random')
                                     <p class="mb-0 small">Specific Tiers: <strong>{{ count($selectedRandomTiers) }} selected</strong></p>
                                 @elseif($restrictionType === 'private')
                                     @php $pTier = $membershipTiers->where('id', $restrictedPrivateTierId)->first(); @endphp
                                     <p class="mb-0 small">Private Tier: <strong>{{ $pTier ? $pTier->name : 'N/A' }}</strong></p>
                                 @endif
                             </div>
                        @endif
                     </div>
                     <div class="col-md-6">
                        <label class="text-muted text-uppercase fw-medium fs-11 mb-2">Blur / Clear View</label>
                         @if(!$blurEnabled)
                             <div class="d-flex align-items-center">
                                 <i class="ri-eye-line text-primary fs-20 me-2"></i>
                                 <div>
                                     <h6 class="mb-0">Full Clarity</h6>
                                     <small class="text-muted">All allowed users see clear content.</small>
                                 </div>
                             </div>
                         @else
                             <div class="d-flex align-items-center mb-2">
                                 <i class="ri-blur-off-line text-secondary fs-20 me-2"></i>
                                 <div>
                                     <h6 class="mb-0">Blur Enabled</h6>
                                     <small class="text-muted">Content blurred for some users.</small>
                                 </div>
                             </div>
                             <div class="ps-4 ms-1 border-start">
                                 <p class="mb-0 small">Clear View Tiers: <strong>{{ count($clearViewTierIds) }} selected</strong></p>
                                 <small class="text-muted">Others see blurred preview.</small>
                             </div>
                         @endif
                     </div>
                </div>
            </div>
        </div>
               
        <div class="alert alert-warning mt-3 mb-0 border-0 fs-13">
            <div class="d-flex align-items-center">
                <i class="ri-information-line me-2 fs-18"></i>
                <div>
                     Please review all settings carefully to be sure the right users see the right content. 
                </div>
            </div>
        </div>
    </div>
</div>
