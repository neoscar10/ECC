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
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="card shadow-none border text-center p-3 mb-0">
                                <h5 class="text-success mb-1">{{ $bulkResults['created'] }}</h5>
                                <span class="text-muted fs-12 uppercase">Created</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card shadow-none border text-center p-3 mb-0">
                                <h5 class="text-warning mb-1">{{ $bulkResults['skipped'] }}</h5>
                                <span class="text-muted fs-12 uppercase">Skipped (Dupe)</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card shadow-none border border-{{ $bulkResults['failed'] > 0 ? 'danger' : 'light' }} text-center p-3 mb-0">
                                <h5 class="text-{{ $bulkResults['failed'] > 0 ? 'danger' : 'body' }} mb-1">{{ $bulkResults['failed'] }}</h5>
                                <span class="text-muted fs-12 uppercase">Failed</span>
                            </div>
                        </div>
                    </div>

                    @if(!empty($bulkResults['error_report_url']))
                        <div class="alert alert-danger mt-4 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="ri-error-warning-line me-2 align-middle fs-16"></i>
                                Some rows failed to import due to validation errors.
                            </div>
                            <button type="button" wire:click="downloadErrorReport" class="btn btn-sm btn-danger">
                                <i class="ri-download-2-line align-bottom me-1"></i> Download Error Report
                            </button>
                        </div>
                    @endif
                @else
                    <!-- Upload & Preview Phase -->
                    <div class="alert alert-info border-0 shadow-none mb-4 d-flex align-items-sm-center">
                        <div class="flex-shrink-0">
                            <i class="ri-information-line fs-24 text-info me-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading fw-medium mb-1">Use the provided template to avoid import errors.</h6>
                            <ul class="mb-0 ps-3">
                                <li>Do not rename column headers.</li>
                                <li>Keep required columns filled. (full_name, email, phone, membership_tier_code)</li>
                                <li><code>membership_tier_code</code> must correspond to an active tier code.</li>
                                <li><strong>Passwords are automatically generated for all imported users and emailed to them.</strong></li>
                            </ul>
                            <div class="mt-2 text-primary text-decoration-underline" style="cursor: pointer" wire:click="downloadTemplate">
                                <i class="ri-download-2-line align-bottom"></i> Download Template
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Template File (CSV)</label>
                        <input type="file" class="form-control" wire:model.live="bulkUploadFile" accept=".csv,.txt">
                        @error('bulkUploadFile') <span class="text-danger mt-1 fs-12">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="bulkUploadFile" class="text-primary mt-2">
                            <i class="mdi mdi-loading mdi-spin me-1"></i> Parsing file...
                        </div>
                    </div>

                    @if(!empty($bulkPreview))
                        <div class="card border shadow-none mb-0 mt-4">
                            <div class="card-header bg-light border-bottom p-2 px-3 d-flex align-items-center">
                                <h6 class="card-title mb-0 flex-grow-1">File Preview</h6>
                                <span class="badge bg-primary text-white">Detected {{ $bulkPreview['total_rows'] }} Rows</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless table-nowrap mb-0 fs-13">
                                        <thead class="text-muted table-light">
                                            <tr>
                                                <th>Row</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Tier Code</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($bulkPreview['rows'] as $index => $row)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 150px;">{{ $row['full_name'] ?? '-' }}</td>
                                                    <td class="text-truncate" style="max-width: 150px;">{{ $row['email'] ?? '-' }}</td>
                                                    <td>{{ $row['phone'] ?? '-' }}</td>
                                                    <td><span class="badge bg-light text-body">{{ $row['membership_tier_code'] ?? '-' }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @if($bulkPreview['total_rows'] > 5)
                            <div class="card-footer bg-light p-2 text-center text-muted fs-12 border-top">
                                Showing 5 of {{ $bulkPreview['total_rows'] }} rows.
                            </div>
                            @endif
                        </div>
                    @endif
                @endif
            </div>

            <div class="modal-footer bg-light border-top d-flex justify-content-between">
                @if(!$bulkResults)
                    <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal" wire:click="resetBulkUpload">Cancel</button>
                    <div>
                        <button type="button" class="btn btn-primary" wire:click="processImport" wire:loading.attr="disabled" {{ empty($bulkPreview) ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="processImport">
                                <i class="ri-play-circle-line align-bottom me-1"></i> Process Import
                            </span>
                            <span wire:loading wire:target="processImport">
                                <i class="mdi mdi-loading mdi-spin me-1"></i> Processing...
                            </span>
                        </button>
                    </div>
                @else
                    <button type="button" class="btn btn-primary ms-auto" data-bs-dismiss="modal" wire:click="resetBulkUpload">Done</button>
                @endif
            </div>
        </div>
    </div>
</div>
