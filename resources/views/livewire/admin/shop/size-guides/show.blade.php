<div>
    {{-- Breadcrumbs --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Size Guide Configuration</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.shop.size-guides.index') }}">Size Guides</a></li>
                        <li class="breadcrumb-item active">Configure</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Back navigation --}}
    <div class="mb-3">
        <a href="{{ route('admin.shop.size-guides.index') }}" class="btn btn-sm btn-soft-secondary">
            <i class="ri-arrow-left-line align-bottom me-1"></i> Back to Size Guides List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card text-dark">
                <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-dark">Configure Guide Details & Sizing Grid</h5>
                </div>
                <div class="card-body bg-white p-4">
                    <form wire:submit.prevent="saveGuide" id="sizeGuideConfigForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted text-uppercase fs-11">Guide Name</label>
                                <input type="text" class="form-control" wire:model="name" placeholder="e.g. Men's shirts & tops sizing" required>
                                @error('name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted text-uppercase fs-11">Short Description (Optional)</label>
                                <input type="text" class="form-control" wire:model="description" placeholder="Brief note on fitting, e.g. Slim fit, Regular fit">
                                @error('description') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Summernote Editor --}}
                        <div class="mb-4" wire:ignore>
                            <label class="form-label fw-semibold text-muted text-uppercase fs-11 mb-2">"How to measure" Instruction & Diagrams</label>
                            <textarea id="how_to_measure_editor" class="form-control">{{ $how_to_measure_text }}</textarea>
                        </div>

                        {{-- Tabs Grid Builders --}}
                        <div class="border-top pt-4 mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 fw-bold text-dark"><i class="ri-table-line me-1 align-bottom text-primary"></i> Sizing Tables</h5>
                            </div>

                            @foreach($tables as $tIndex => $table)
                                @php
                                    $tableUnit = $table['unit'] ?? 'cm';
                                @endphp
                                <div class="border rounded p-3 mb-4 bg-light shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 fw-bold text-dark">Table Section {{ $tIndex + 1 }} ({{ strtoupper($tableUnit) }})</h6>
                                        @if(count($tables) > 1)
                                            <button type="button" class="btn btn-outline-danger btn-sm mb-0" wire:click="removeTable({{ $tIndex }})">
                                                <i class="mdi mdi-delete me-1"></i> Remove Table
                                            </button>
                                        @endif
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fs-11 text-muted text-uppercase">Section Title (Optional)</label>
                                        <input type="text" class="form-control bg-white" wire:model="tables.{{ $tIndex }}.title" placeholder="e.g. Shirt, Short, Pants...">
                                        <small class="text-muted">Leave blank if this is the only table.</small>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded border bg-white">
                                        <span class="fw-semibold text-muted fs-12">Grid Columns</span>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn {{ $tableUnit === 'cm' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setUnitForTable({{ $tIndex }}, 'cm')">Centimeters (cm)</button>
                                                <button type="button" class="btn {{ $tableUnit === 'inch' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setUnitForTable({{ $tIndex }}, 'inch')">Inches (in)</button>
                                            </div>
                                            <button type="button" class="btn btn-soft-primary btn-sm mb-0" wire:click="addColumn({{ $tIndex }})">
                                                <i class="mdi mdi-table-column-plus-after me-1"></i> Add Column
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive rounded border p-2 mb-2 bg-white">
                                        <table class="table table-bordered mb-0 align-middle text-center bg-white">
                                            <thead>
                                                <tr>
                                                    @foreach($table['columns'] as $colIndex => $col)
                                                        <th class="p-2 align-middle bg-light" style="border: 1px solid #e9ebec;">
                                                            <div class="d-flex align-items-center gap-1">
                                                                <input type="text" class="form-control form-control-sm text-center fw-bold bg-white" style="min-width: 100px; border-color: #ced4da;" wire:model="tables.{{ $tIndex }}.columns.{{ $colIndex }}" placeholder="Header" {{ $colIndex === 0 ? 'readonly' : '' }}>
                                                                @if(count($table['columns']) > 1 && $colIndex > 0)
                                                                    <button type="button" class="btn btn-link text-danger p-0 m-0" wire:click="removeColumn({{ $tIndex }}, {{ $colIndex }})" title="Remove Column">
                                                                        <i class="mdi mdi-close"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($table['rows'] as $rowIndex => $row)
                                                    <tr>
                                                        @foreach($table['columns'] as $colIndex => $col)
                                                            <td class="p-1" style="border: 1px solid #e9ebec;">
                                                                @if($colIndex === 0)
                                                                    <input type="text" class="form-control form-control-sm text-center bg-white fw-bold" style="min-width: 100px; border-color: #ced4da;" wire:model="tables.{{ $tIndex }}.rows.{{ $rowIndex }}.label" placeholder="Row Label">
                                                                @else
                                                                    <input type="text" class="form-control form-control-sm text-center bg-white" style="min-width: 100px; border-color: #ced4da;" wire:model="tables.{{ $tIndex }}.rows.{{ $rowIndex }}.values.{{ $tableUnit }}.{{ $colIndex }}" placeholder="...">
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                        <td style="width: 40px;" class="p-1 bg-light">
                                                            @if(count($table['rows']) > 1)
                                                                <button type="button" class="btn btn-link text-danger p-0 m-0 w-100" wire:click="removeRow({{ $tIndex }}, {{ $rowIndex }})" title="Remove Row">
                                                                    <i class="mdi mdi-delete"></i>
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-start">
                                        <button type="button" class="btn btn-soft-primary btn-sm" wire:click="addRow({{ $tIndex }})">
                                            <i class="mdi mdi-table-row-plus-after me-1"></i> Add Row
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                            
                            <div class="text-center mt-3 mb-5">
                                <button type="button" class="btn btn-outline-secondary" wire:click="addTable">
                                    <i class="mdi mdi-plus-box-multiple-outline me-1"></i> Add Another Table Section
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer border-top bg-light p-3">
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="ri-check-line me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.shop.size-guides.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
                        <button type="submit" form="sizeGuideConfigForm" class="btn btn-primary btn-sm px-4">
                            <span wire:loading wire:target="saveGuide" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    /* Premium overrides for Summernote editor layout */
    .note-editor.note-frame {
        border: 1px solid #ced4da !important;
        border-radius: 0.25rem !important;
        background-color: #ffffff !important;
    }
    .note-editor .note-editing-area {
        background-color: #ffffff !important;
    }
    .note-editable {
        background-color: #ffffff !important;
        color: #212529 !important;
        font-family: var(--vz-font-sans-serif, sans-serif) !important;
        font-size: 14px !important;
        line-height: 1.6 !important;
        min-height: 400px !important;
    }
    .note-editor.note-frame .note-statusbar {
        background-color: #f3f6f9 !important;
        border-top: 1px solid #ced4da !important;
    }
    .note-toolbar {
        background-color: #f3f6f9 !important;
        border-bottom: 1px solid #ced4da !important;
        padding: 6px 10px !important;
    }
    .note-btn {
        background-color: #ffffff !important;
        border: 1px solid #ced4da !important;
        color: #495057 !important;
        padding: 5px 10px !important;
        font-size: 12px !important;
        border-radius: 0.25rem !important;
        margin-right: 4px !important;
        box-shadow: none !important;
        transition: all 0.15s ease-in-out !important;
    }
    .note-btn:hover, .note-btn.active {
        background-color: #e9ecef !important;
        border-color: #adb5bd !important;
        color: #212529 !important;
    }
    .note-btn-group .note-btn {
        border-radius: 0.25rem !important;
    }
    .note-dropdown-menu {
        min-width: 180px !important;
        background-color: #ffffff !important;
        border: 1px solid rgba(0, 0, 0, 0.15) !important;
        border-radius: 0.25rem !important;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1) !important;
    }
    .note-dropdown-item {
        color: #212529 !important;
        padding: 6px 16px !important;
    }
    .note-dropdown-item:hover {
        background-color: #f3f6f9 !important;
    }
    .note-modal-content {
        background-color: #ffffff !important;
        border: 1px solid rgba(0, 0, 0, 0.2) !important;
        border-radius: 0.3rem !important;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5) !important;
    }
    .note-form-label {
        color: #212529 !important;
    }
    .note-input {
        background-color: #ffffff !important;
        border: 1px solid #ced4da !important;
        color: #212529 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    document.addEventListener('livewire:initialized', () => {
        // Initialize Summernote
        $('#how_to_measure_editor').summernote({
            placeholder: 'Write instructions and drag/drop or upload diagrams directly here...',
            tabsize: 2,
            height: 400,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onChange: function(contents) {
                    @this.set('how_to_measure_text', contents);
                }
            }
        });
    });
</script>
@endpush
