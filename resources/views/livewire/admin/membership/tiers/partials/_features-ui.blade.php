<div class="col-12">
    <div class="card border border-dashed shadow-none mb-0">
        <div class="card-header bg-light-subtle">
            <h6 class="mb-0 fs-13 fw-semibold">Tier Features / Benefits</h6>
            <p class="text-muted fs-11 mb-0 mt-1">Add benefits members receive in this tier.</p>
        </div>
        <div class="card-body">
            @foreach($features as $index => $feature)
                <div class="row g-2 align-items-center mb-2" wire:key="tier-feature-{{ $index }}-{{ $feature['id'] ?? 'new' }}">
                    <div class="col-auto">
                        <i class="ri-drag-move-2-line text-muted handle" style="cursor: move;"></i>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control form-control-sm" placeholder="Feature title" 
                            wire:model="features.{{ $index }}.title">
                        @error("features.{$index}.title") 
                            <span class="text-danger small">{{ $message }}</span> 
                        @enderror
                    </div>
                    <!-- <div class="col-2">
                        <input type="number" class="form-control form-control-sm" placeholder="Order" 
                            wire:model="features.{{ $index }}.sort_order">
                    </div> -->
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-soft-danger" 
                            wire:click="removeFeatureRow({{ $index }})">
                            <i class="ri-delete-bin-5-fill"></i>
                        </button>
                    </div>
                </div>
            @endforeach

            <button type="button" class="btn btn-sm btn-soft-primary mt-2" wire:click="addFeatureRow">
                <i class="ri-add-line align-bottom me-1"></i> Add Feature
            </button>
        </div>
    </div>
</div>
