<div class="row g-4">
    <div class="col-lg-6">
        <h5 class="card-title mb-3">Summary</h5>
        <table class="table table-borderless">
            <tbody>
                <tr>
                    <td class="text-muted">Internal Title</td>
                    <td class="fw-medium">{{ $title }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Placement</td>
                    <td class="fw-medium text-capitalize">{{ $placement }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Type</td>
                    <td class="fw-medium text-capitalize">{{ $type }}</td>
                </tr>
                 <tr>
                    <td class="text-muted">Status</td>
                    <td>
                        @if($isActive) 
                            <span class="badge bg-success-subtle text-success">Active</span> 
                        @else 
                            <span class="badge bg-danger-subtle text-danger">Inactive</span> 
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Visibility</td>
                    <td>
                         @if($restrictionMode === 'public')
                            <span class="badge bg-success-subtle text-success">Public</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Restricted ({{ count($selectedVisibilityTiers) }} Tiers)</span>
                        @endif
                    </td>
                </tr>
                @if($blurEnabled)
                <tr>
                    <td class="text-muted">Blur</td>
                    <td><span class="badge bg-secondary-subtle text-secondary">Enabled ({{ $restrictionType }})</span></td>
                </tr>
                @endif
            </tbody>
        </table>
        
        @if($type === 'slider')
             <div class="mt-3">
                 <h6 class="fw-semibold">Slider Configuration</h6>
                 <p class="text-muted small mb-1">Mode: <strong class="text-capitalize">{{ $sliderMode }}</strong></p>
                 @if($sliderMode === 'category')
                     <p class="text-muted small">Category ID: {{ $sliderCategoryId }}</p>
                 @elseif($sliderMode === 'manual')
                     <p class="text-muted small">Items: {{ count($selectedSliderItems) }} selected</p>
                 @elseif($sliderMode === 'images')
                     <p class="text-muted small">Slides: {{ count($sliderImages) }} slides</p>
                 @endif
             </div>
        @endif
    </div>
    
    <div class="col-lg-6">
         <h5 class="card-title mb-3">Final Preview</h5>
         <div class="border rounded bg-light p-3 d-flex justify-content-center">
             <div style="transform: scale(0.85); transform-origin: top center;">
                 @include('livewire.admin.cms.blocks.partials._mobile-preview')
             </div>
         </div>
    </div>
</div>
