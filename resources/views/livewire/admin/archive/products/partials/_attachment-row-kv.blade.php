<div class="card mb-2 border shadow-none" wire:key="att-kv-{{ $index }}">
    <div class="card-body p-2 bg-light-subtle">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label fs-11 text-muted text-uppercase mb-0">Label / Key</label>
                <input type="text" class="form-control form-control-sm mt-1" placeholder="e.g. 'Dimensions'" wire:model="attachmentRows.{{ $index }}.kv_key">
                @error("attachmentRows.{$index}.kv_key") <span class="text-danger fs-11">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label fs-11 text-muted text-uppercase mb-0">Value</label>
                <input type="text" class="form-control form-control-sm mt-1" placeholder="e.g. '1920 x 1080'" wire:model="attachmentRows.{{ $index }}.kv_value">
                @error("attachmentRows.{$index}.kv_value") <span class="text-danger fs-11">{{ $message }}</span> @enderror
            </div>
            
            <div class="col-md-4 border-start ps-3">
                <label class="form-label fs-11 text-muted text-uppercase mb-0">Restriction</label>
                <select class="form-select form-select-sm mt-1" wire:model.live="attachmentRows.{{ $index }}.restriction_mode">
                    <option value="inherit">Inherit (Default)</option>
                    <option value="public">Force Public</option>
                    <option value="restricted">Restricted Subset</option>
                </select>
                @error("attachmentRows.{$index}.restriction_mode") <span class="text-danger fs-11">{{ $message }}</span> @enderror

                <div wire:key="att-restrict-{{ $index }}-{{ data_get($attachmentRows, $index.'.restriction_mode', 'inherit') }}">
                    @include('livewire.admin.archive.products.partials._attachment-restriction-config', ['index' => $index, 'row' => $row])
                </div>
            </div>
            
            <div class="col-md-1 text-end">
                <button class="btn btn-sm btn-ghost-danger mt-3" wire:click="removeAttachmentRow({{ $index }})" title="Remove">
                    <i class="ri-delete-bin-line fs-16"></i>
                </button>
            </div>
        </div>
    </div>
</div>
