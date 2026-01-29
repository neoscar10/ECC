<div class="modal fade" id="attachmentsModal" tabindex="-1" aria-hidden="true" wire:ignore.self data-bs-backdrop="static"
     data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Attachments</h5>
                <button type="button" class="btn-close" wire:click="$set('showAttachmentsModal', false)" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left Sidebar: Tabs & Info -->
                    <div class="col-lg-3 border-end">
                        <div class="nav flex-column nav-pills text-center" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <a class="nav-link active mb-2" id="v-pills-quick-tab" data-bs-toggle="pill" href="#v-pills-quick" role="tab" aria-controls="v-pills-quick" aria-selected="true" wire:ignore.self>
                                <i class="ri-list-check-2 align-middle me-1"></i> Quick Lines
                            </a>
                            <a class="nav-link mb-2" id="v-pills-kv-tab" data-bs-toggle="pill" href="#v-pills-kv" role="tab" aria-controls="v-pills-kv" aria-selected="false" wire:ignore.self>
                                <i class="ri-key-2-line align-middle me-1"></i> Key / Features
                            </a>
                            <a class="nav-link mb-2" id="v-pills-rich-tab" data-bs-toggle="pill" href="#v-pills-rich" role="tab" aria-controls="v-pills-rich" aria-selected="false" wire:ignore.self>
                                <i class="ri-file-text-line align-middle me-1"></i> Rich Sections
                            </a>
                        </div>
                        
                        <div class="mt-4 p-3 bg-light rounded fs-12 text-muted">
                            <h6 class="fs-13 fw-bold text-dark">Access Control</h6>
                            <p class="mb-2">Attachments default to <strong>Inherit</strong> (same access as product).</p>
                            <p class="mb-0">You can further restrict an attachment to a subset of the product's allowed tiers.</p>
                        </div>
                    </div>

                    <!-- Right Content -->
                    <div class="col-lg-9">
                        <div class="tab-content text-muted mt-3 mt-lg-0" id="v-pills-tabContent">
                            
                            <!-- Quick Lines -->
                            <div class="tab-pane fade show active" id="v-pills-quick" role="tabpanel" aria-labelledby="v-pills-quick-tab" wire:ignore.self>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fs-14 fw-bold mb-0">Quick Text Lines</h6>
                                    <button class="btn btn-sm btn-soft-primary" wire:click="addAttachmentRow('line')">
                                        <i class="ri-add-line align-bottom"></i> Add Line
                                    </button>
                                </div>
                                
                                @foreach($attachmentRows as $index => $row)
                                    @if($row['type'] === 'line')
                                        @include('livewire.admin.archive.products.partials._attachment-row-line', ['index' => $index, 'row' => $row])
                                    @endif
                                @endforeach
                                
                                <div class="text-center mt-3" wire:loading wire:target="addAttachmentRow">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                </div>
                            </div>

                            <!-- Key / Features -->
                            <div class="tab-pane fade" id="v-pills-kv" role="tabpanel" aria-labelledby="v-pills-kv-tab" wire:ignore.self>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fs-14 fw-bold mb-0">Key/Value Features</h6>
                                    <button class="btn btn-sm btn-soft-primary" wire:click="addAttachmentRow('kv')">
                                        <i class="ri-add-line align-bottom"></i> Add Feature
                                    </button>
                                </div>

                                @foreach($attachmentRows as $index => $row)
                                    @if($row['type'] === 'kv')
                                        @include('livewire.admin.archive.products.partials._attachment-row-kv', ['index' => $index, 'row' => $row])
                                    @endif
                                @endforeach
                            </div>

                            <!-- Rich Sections -->
                            <div class="tab-pane fade" id="v-pills-rich" role="tabpanel" aria-labelledby="v-pills-rich-tab" wire:ignore.self>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fs-14 fw-bold mb-0">Rich Content Blocks</h6>
                                    <button class="btn btn-sm btn-soft-primary" wire:click="addAttachmentRow('rich')">
                                        <i class="ri-add-line align-bottom"></i> Add Block
                                    </button>
                                </div>

                                @foreach($attachmentRows as $index => $row)
                                    @if($row['type'] === 'rich')
                                        @include('livewire.admin.archive.products.partials._attachment-row-rich', ['index' => $index, 'row' => $row])
                                    @endif
                                @endforeach
                            </div>
                            
                            <!-- Errors -->
                            @if($errors->any())
                                <div class="alert alert-danger mt-3">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="saveAttachments">Save Attachments</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
@push('scripts')
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
@endpush
