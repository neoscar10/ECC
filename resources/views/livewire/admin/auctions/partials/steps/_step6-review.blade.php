<div class="row g-4">
    <div class="col-12">
        <h5>Review Auction Lot Details</h5>
        <p class="text-muted">Please review all properties before creating.</p>
    </div>
    
    <div class="col-md-6">
        <div class="card p-3 bg-light">
            <h6>Basic Info</h6>
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">Lot No:</td><td class="fw-bold">{{ $lot_no }}</td></tr>
                <tr><td class="text-muted">Title:</td><td class="fw-bold">{{ $title }}</td></tr>
                <tr><td class="text-muted">Subtitle:</td><td>{{ $subtitle ?? '-' }}</td></tr>
                <tr><td class="text-muted">Flags:</td><td>
                    @if($is_featured_star_lot) <span class="badge bg-warning">Star</span> @endif
                    @if($is_hot) <span class="badge bg-danger">Hot</span> @endif
                    @if($is_rare) <span class="badge bg-info">Rare</span> @endif
                </td></tr>
            </table>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3 bg-light">
            <h6>Pricing & Schedule</h6>
             <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">Start Price:</td><td class="fw-bold">{{ $currency }} {{ $starting_price }}</td></tr>
                <tr><td class="text-muted">Reserve:</td><td>{{ $currency }} {{ $min_selling_price ?? '-' }}</td></tr>
                <tr><td class="text-muted">Increment:</td><td>{{ $currency }} {{ $min_increment }}</td></tr>
                <tr><td class="text-muted">Starts:</td><td>{{ $starts_at }}</td></tr>
                <tr><td class="text-muted">Ends:</td><td>{{ $ends_at }}</td></tr>
                <tr><td class="text-muted">Anti-Sniping:</td><td>{{ $anti_sniping_enabled ? 'Yes (' . $trigger_window_seconds . 's / +' . $extend_by_seconds . 's)' : 'No' }}</td></tr>
            </table>
        </div>
    </div>
    
    <div class="col-12">
        <div class="card p-3 bg-light">
            <h6>Access Summary</h6>
            <div class="d-flex gap-5">
                <div>
                    <strong>Visible To:</strong>
                    @if(count($selectedVisibilityTiers) > 0)
                        {{ count($selectedVisibilityTiers) }} Tiers Selected
                    @else
                        <span class="text-danger">Hidden (No Tiers)</span>
                    @endif
                </div>
                <div>
                     <strong>Clear View To:</strong>
                     @if(count($selectedClearViewTiers) > 0)
                        {{ count($selectedClearViewTiers) }} Tiers Selected
                    @else
                        <span class="text-muted">Blurred for all</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12">
        <h6>Media Preview</h6>
        <div class="d-flex gap-2">
             @foreach($newImages as $img)
                <div class="border rounded p-1" style="width: 50px; height: 50px;">
                     <img src="{{ $img->temporaryUrl() }}" class="w-100 h-100 object-fit-cover">
                </div>
            @endforeach
             @foreach($existingImages as $img)
                <div class="border rounded p-1" style="width: 50px; height: 50px;">
                     <img src="{{ Storage::url($img['path']) }}" class="w-100 h-100 object-fit-cover">
                </div>
            @endforeach
        </div>
    </div>
</div>
