<div class="row g-3">
    <div class="col-6">
        <label class="form-label">Title <span class="text-danger">*</span></label>
        <input type="text" class="form-control" wire:model="title" placeholder="e.g., Cricket Bat 2026">
        @error('title') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Base Price <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">{{ $currency }}</span>
            <input type="number" step="0.01" class="form-control" wire:model="base_price" placeholder="0.00">
        </div>
        @error('base_price') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>
    
    <div class="col-12">
        <label class="form-label">Description</label>
        <x-ui.markdown-editor
    id="product_description_md"
    wire:model.defer="description"
    :value="$description"
    :editorKey="$descriptionEditorKey"
/>


    </div>
    @error('description') <span class="text-danger text-sm">{{ $message }}</span> @enderror

    <div class="col-md-6">
        <label class="form-label">Status</label>
        <div class="form-check form-switch form-switch-lg">
            <input class="form-check-input" type="checkbox" id="isActiveSwitch" wire:model.live="is_active">
            <label class="form-check-label" for="isActiveSwitch">Active</label>
        </div>
    </div>
    
    @if(!$is_active)
    <div class="col-12 mt-2">
        <label class="form-label">Reason for Deactivation <span class="text-danger">*</span></label>
        <textarea class="form-control" wire:model="deactivation_reason" rows="2" placeholder="Why is this product inactive? (e.g., Temporarily out of stock, discontinued)"></textarea>
        @error('deactivation_reason') <span class="text-danger text-sm">{{ $message }}</span> @enderror
    </div>
    @endif
</div>
