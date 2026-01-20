<div class="card mb-3 border shadow-none" wire:key="att-rich-{{ $index }}">
    <div class="card-header bg-light-subtle p-2 d-flex justify-content-between align-items-center">
        <span class="fs-12 fw-bold text-uppercase text-muted">
            <i class="ri-file-text-line me-1"></i> Content Block #{{ $loop->iteration }}
        </span>
        <button class="btn btn-sm btn-ghost-danger" wire:click="removeAttachmentRow({{ $index }})" title="Remove">
            <i class="ri-delete-bin-line"></i>
        </button>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="mb-2">
                    <label class="form-label fs-11 text-muted text-uppercase mb-1">Heading (Optional)</label>
                    <input type="text" class="form-control form-control-sm" placeholder="Enter heading of the writeup" wire:model="attachmentRows.{{ $index }}.heading">
                </div>
                
                <div class="mb-0" wire:ignore>
                    <label class="form-label fs-11 text-muted text-uppercase mb-1">Content (Markdown)</label>
                    <div x-data x-init="
                        new EasyMDE({
                            element: $refs.editor,
                            spellChecker: false,
                            status: false,
                            minHeight: '150px',
                            toolbar: ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link', 'preview', 'guide'],
                            forceSync: true
                        }).codemirror.on('change', (cm) => {
                            @this.set('attachmentRows.{{ $index }}.body', cm.getValue());
                        });
                    ">
                        <textarea x-ref="editor">{{ $attachmentRows[$index]['body'] ?? '' }}</textarea>
                    </div>
                </div>
                @error("attachmentRows.{$index}.body") <span class="text-danger fs-11">{{ $message }}</span> @enderror
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
        </div>
    </div>
</div>
