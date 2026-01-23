<div class="row g-4">
    <div class="col-12">
        <div class="alert alert-warning bg-warning-subtle text-warning border-0">
            Configure which tiers can see this auction lot and which can see unblurred images. 
            If no tiers selected for Visibility, it is hidden from all except Admin.
            If no tiers selected for Clear View, it is blurred for all.
        </div>
    </div>
    
    <div class="col-md-6">
        <h6 class="mb-3">Visibility (Who can see the card?)</h6>
        <div class="card p-3 border">
            <div class="d-flex flex-column gap-2">
                @foreach($membershipTiers as $tier)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="{{ $tier->id }}" id="vis_tier_{{ $tier->id }}" wire:model="selectedVisibilityTiers">
                    <label class="form-check-label" for="vis_tier_{{ $tier->id }}">
                        {{ $tier->name }}
                    </label>
                </div>
                @endforeach
            </div>
            <div class="mt-3">
                <small class="text-muted">Select all tiers that should see this lot in the list.</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <h6 class="mb-3">Clear View (Who sees unblurred images?)</h6>
         <div class="card p-3 border">
            <div class="d-flex flex-column gap-2">
                @foreach($membershipTiers as $tier)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="{{ $tier->id }}" id="clear_tier_{{ $tier->id }}" wire:model="selectedClearViewTiers">
                    <label class="form-check-label" for="clear_tier_{{ $tier->id }}">
                        {{ $tier->name }}
                    </label>
                </div>
                @endforeach
            </div>
             <div class="mt-3">
                <small class="text-muted">Select tiers that get the "Clear" view permission.</small>
            </div>
        </div>
    </div>
</div>
