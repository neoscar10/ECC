<div wire:ignore.self class="modal fade" id="tierCodesModal" tabindex="-1" aria-labelledby="tierCodesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title" id="tierCodesModalLabel">Membership Tier Codes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tier Name</th>
                                <th>Code (For Import)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Sort active first
                                $sortedTiers = $membershipTiers->sortByDesc('is_active');
                            @endphp
                            @foreach($sortedTiers as $tier)
                            <tr class="{{ !$tier->is_active ? 'text-muted bg-light bg-opacity-50' : '' }}">
                                <td class="fw-medium">{{ $tier->name }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <code>{{ strtoupper($tier->code) }}</code>
                                        <button class="btn btn-sm btn-ghost-secondary btn-icon" type="button" 
                                            onclick="navigator.clipboard.writeText('{{ strtoupper($tier->code) }}'); 
                                                     this.innerHTML='<i class=\'ri-check-line text-success\'></i>'; 
                                                     setTimeout(() => this.innerHTML='<i class=\'ri-clipboard-line\'></i>', 1500);"
                                            title="Copy Code">
                                            <i class="ri-clipboard-line"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    @if($tier->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-soft-secondary w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
