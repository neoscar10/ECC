@php
    $total = count($bulkPreviewRows);
    $ready = collect($bulkPreviewRows)->where('is_valid', true)->count();
    $needsFix = collect($bulkPreviewRows)->where('is_valid', false)->count();
    $corrected = collect($bulkPreviewRows)->where('is_corrected', true)->count();
@endphp

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="card shadow-none border text-center p-2 mb-0">
            <h6 class="text-muted fs-11 uppercase mb-1">Total Rows</h6>
            <h5 class="mb-0">{{ $total }}</h5>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card shadow-none border text-center p-2 mb-0 bg-success-subtle border-success-subtle">
            <h6 class="text-success fs-11 uppercase mb-1">Ready to Import</h6>
            <h5 class="text-success mb-0">{{ $ready }}</h5>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card shadow-none border text-center p-2 mb-0 {{ $needsFix > 0 ? 'bg-danger-subtle border-danger-subtle' : '' }}">
            <h6 class="{{ $needsFix > 0 ? 'text-danger' : 'text-muted' }} fs-11 uppercase mb-1">Needs Correction</h6>
            <h5 class="{{ $needsFix > 0 ? 'text-danger' : '' }} mb-0">{{ $needsFix }}</h5>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card shadow-none border text-center p-2 mb-0 bg-info-subtle border-info-subtle">
            <h6 class="text-info fs-11 uppercase mb-1">Corrected</h6>
            <h5 class="text-info mb-0">{{ $corrected }}</h5>
        </div>
    </div>
</div>

<!-- Validation Legend -->
<div class="d-flex align-items-center mb-3 fs-12">
    <span class="me-3 text-muted">Legend:</span>
    <span class="badge bg-success-subtle text-success me-2 border border-success-subtle"><i class="ri-checkbox-circle-line me-1"></i> Valid</span>
    <span class="badge bg-danger-subtle text-danger me-2 border border-danger-subtle"><i class="ri-error-warning-line me-1"></i> Invalid</span>
    <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="ri-edit-line me-1"></i> Corrected</span>
</div>

@if($editingRowIndex !== null)
    <!-- Inline Edit Form -->
    <div class="card border border-primary shadow-none mb-4">
        <div class="card-header bg-primary-subtle py-2">
            <h6 class="card-title mb-0 fs-13 text-primary">Editing Row #{{ $editingRowIndex + 1 }}</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fs-12">Full Name</label>
                    <input type="text" class="form-control form-control-sm" wire:model="editingRowData.full_name">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-12">Email</label>
                    <input type="email" class="form-control form-control-sm" wire:model="editingRowData.email">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-12">Phone</label>
                    <input type="text" class="form-control form-control-sm" wire:model="editingRowData.phone">
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-12">Membership Tier Code</label>
                    <input type="text" class="form-control form-control-sm" wire:model="editingRowData.membership_tier_code" placeholder="e.g. PAVILION">
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-12">Expiry Date</label>
                    <input type="date" class="form-control form-control-sm" wire:model="editingRowData.membership_expiry_date">
                </div>
            </div>
            <div class="mt-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-sm btn-soft-secondary" wire:click="cancelEdit">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" wire:click="updateRow">Validate & Save</button>
            </div>
        </div>
    </div>
@endif

<!-- Preview Table -->
<div class="card border shadow-none mb-0 overflow-hidden">
    <div class="table-responsive" style="max-height: 400px;">
        <table class="table table-sm table-hover align-middle mb-0 fs-13">
            <thead class="table-light sticky-top">
                <tr>
                    <th class="ps-3" style="width: 50px;">#</th>
                    <th>Status</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Tier</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bulkPreviewRows as $idx => $row)
                    @php
                        $isValid = $row['is_valid'];
                        $isCorrected = $row['is_corrected'];
                        $statusClass = $isValid ? ($isCorrected ? 'info' : 'success') : 'danger';
                    @endphp
                    <tr class="{{ $editingRowIndex === $idx ? 'table-primary-subtle' : '' }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td>
                            <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} border border-{{ $statusClass }}-subtle">
                                {{ $row['status'] }}
                            </span>
                        </td>
                        <td class="{{ !$isValid && collect($row['errors'])->some(fn($e) => stripos($e, 'name') !== false) ? 'bg-danger-subtle' : '' }}">
                            {{ $row['data']['full_name'] ?: '-' }}
                        </td>
                        <td class="{{ !$isValid && collect($row['errors'])->some(fn($e) => stripos($e, 'email') !== false) ? 'bg-danger-subtle' : '' }}">
                            {{ $row['data']['email'] ?: '-' }}
                            @if(!$isValid && collect($row['errors'])->some(fn($e) => stripos($e, 'email') !== false))
                                <i class="ri-error-warning-line text-danger ms-1" data-bs-toggle="tooltip" title="{{ collect($row['errors'])->first(fn($e) => stripos($e, 'email') !== false) }}"></i>
                            @endif
                        </td>
                        <td class="{{ !$isValid && collect($row['errors'])->some(fn($e) => stripos($e, 'phone') !== false) ? 'bg-danger-subtle' : '' }}">
                            {{ $row['data']['phone'] ?: '-' }}
                        </td>
                        <td class="{{ !$isValid && collect($row['errors'])->some(fn($e) => stripos($e, 'tier') !== false) ? 'bg-danger-subtle' : '' }}">
                            <span class="badge bg-light text-body border">{{ $row['data']['membership_tier_code'] ?: '-' }}</span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-sm btn-icon btn-soft-primary" wire:click="editRow({{ $idx }})" title="Edit Row">
                                    <i class="ri-edit-2-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-icon btn-soft-danger" wire:click="removeRow({{ $idx }})" title="Delete Row">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @if(!$isValid && !empty($row['errors']))
                    <tr class="table-danger-subtle border-0">
                        <td colspan="7" class="py-1 ps-3 border-0">
                            <div class="text-danger fs-11">
                                <i class="ri-error-warning-line me-1"></i> {{ implode(' | ', $row['errors']) }}
                            </div>
                        </td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
