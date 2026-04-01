<div wire:ignore.self class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title" id="bulkUploadModalLabel">Bulk Upload Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="resetBulkUpload"></button>
            </div>
            <div class="modal-body p-4">
                
                @if($bulkResults)
                    <!-- Results Summary -->
                    <div class="text-center mb-4">
                        <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop" colors="primary:#0ab39c,secondary:#405189" style="width:100px;height:100px"></lord-icon>
                        <h4 class="mt-3">Import Complete</h4>
                        <p class="text-muted">The bulk import process has finished. See the breakdown below.</p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="card shadow-none border text-center p-3 mb-0 bg-success-subtle border-success-subtle">
                                <h3 class="text-success mb-1">{{ $bulkResults['created'] }}</h3>
                                <span class="text-muted fs-12 uppercase fw-medium">Successfully Created</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card shadow-none border text-center p-3 mb-0 bg-warning-subtle border-warning-subtle">
                                <h3 class="text-warning mb-1">{{ $bulkResults['skipped'] }}</h3>
                                <span class="text-muted fs-12 uppercase fw-medium">Skipped/Duplicate</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card shadow-none border text-center p-3 mb-0 {{ $bulkResults['failed'] > 0 ? 'bg-danger-subtle border-danger-subtle' : 'bg-light border-light' }}">
                                <h3 class="text-{{ $bulkResults['failed'] > 0 ? 'danger' : 'muted' }} mb-1">{{ $bulkResults['failed'] }}</h3>
                                <span class="text-muted fs-12 uppercase fw-medium">Failed</span>
                            </div>
                        </div>
                    </div>

                    @if(!empty($bulkResults['failed_rows']))
                        <div class="alert alert-danger mt-4 d-flex align-items-center justify-content-between mb-0">
                            <div class="d-flex align-items-center">
                                <i class="ri-error-warning-line me-2 fs-20"></i>
                                <span>Some rows encountered unexpected server-side errors during processing.</span>
                            </div>
                            <button type="button" wire:click="downloadErrorReport" class="btn btn-sm btn-danger">
                                <i class="ri-download-2-line align-bottom me-1"></i> Download Errors
                            </button>
                        </div>
                    @endif
                @else
                    <!-- Upload & Preview Phase -->
                    @if(empty($bulkPreviewRows))
                        <div class="alert alert-info border-0 shadow-none mb-4 d-flex align-items-sm-center">
                            <div class="flex-shrink-0">
                                <i class="ri-information-line fs-24 text-info me-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading fw-medium mb-1">Upload a file to start the import process.</h6>
                                <p class="text-muted mb-2 fs-13">We recommend using our Excel template for easier data entry with pre-sized columns.</p>
                                <div class="d-flex align-items-center gap-3">
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="downloadTemplate('xlsx')">
                                        <i class="ri-file-excel-2-line align-bottom me-1"></i> Download Excel Template
                                    </button>
                                    <div class="text-muted fs-12" style="cursor: pointer" wire:click="downloadTemplate('csv')">
                                        <i class="ri-file-text-line align-bottom"></i> Or CSV Fallback
                                    </div>
                                    <div class="ms-auto text-muted fs-13" style="cursor: pointer" wire:click="openTierCodesModal">
                                        <i class="ri-list-check-2 align-bottom"></i> View Tier Codes
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fs-13 fw-semibold">Select File</label>
                            <div class="p-4 border border-2 border-dashed rounded text-center">
                                <i class="ri-upload-cloud-2-line fs-48 text-muted mb-2 d-block"></i>
                                <input type="file" class="form-control" id="bulk_file_input" wire:model.live="bulkUploadFile" accept=".csv,.txt,.xlsx,.xls" style="display: none;">
                                <label for="bulk_file_input" class="btn btn-primary mb-2">Choose File</label>
                                <p class="text-muted fs-12 mb-0">Max file size: 10MB. Format: .xlsx, .xls, .csv, .txt</p>
                            </div>
                            @error('bulkUploadFile') <span class="text-danger mt-1 fs-12 d-block">{{ $message }}</span> @enderror
                            <div wire:loading wire:target="bulkUploadFile" class="text-primary mt-3 text-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                <span class="fs-13">Parsing and validating data...</span>
                            </div>
                        </div>
                    @else
                        <!-- Smart Preview Workspace -->
                        <div class="d-flex align-items-center justify-content-between mb-3 mt-n2">
                            <h6 class="mb-0 fw-semibold">Import Review Workspace</h6>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 h-auto" wire:click="resetBulkUpload">
                                <i class="ri-refresh-line align-bottom"></i> Restart / Upload New
                            </button>
                        </div>
                        
                        @include('livewire.admin.users.users.partials._bulk_upload_preview')
                    @endif
                @endif
            </div>

            <div class="modal-footer bg-light border-top d-flex justify-content-between">
                @if(!$bulkResults)
                    <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal" wire:click="resetBulkUpload">Cancel</button>
                    <div>
                        @if(!empty($bulkPreviewRows))
                            @php
                                $readyToImport = collect($bulkPreviewRows)->where('is_valid', true)->count();
                            @endphp
                            <button type="button" class="btn btn-primary" wire:click="processImport" wire:loading.attr="disabled" {{ $readyToImport === 0 ? 'disabled' : '' }}>
                                <span wire:loading.remove wire:target="processImport">
                                    <i class="ri-check-double-line align-bottom me-1"></i> Import {{ $readyToImport }} Ready Rows
                                </span>
                                <span wire:loading wire:target="processImport">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span> Processing Import...
                                </span>
                            </button>
                        @endif
                    </div>
                @else
                    <button type="button" class="btn btn-primary ms-auto" data-bs-dismiss="modal" wire:click="resetBulkUpload">Close & Finish</button>
                @endif
            </div>
        </div>
    </div>
</div>
