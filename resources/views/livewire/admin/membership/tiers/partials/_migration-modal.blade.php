<!-- Migration Modal -->
<div wire:ignore.self class="modal fade" id="migrationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <div class="modal-header bg-primary p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-white bg-opacity-20 rounded-3 p-2 me-3">
                        <i class="ri-user-shared-2-line fs-24 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title text-white fw-bold mb-0">Member Migration Workflow</h5>
                        <p class="text-white-50 small mb-0">Reassign members before tier deletion</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0">
                <!-- Alerts Container -->
                <div class="p-4 pb-0">
                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="ri-checkbox-circle-line me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-uppercase fs-11 fw-bold text-muted mb-1">Target Migration Tier</h6>
                                    <select class="form-select border-0 shadow-sm @error('migrationTargetTierId') is-invalid @enderror" wire:model="migrationTargetTierId">
                                        <option value="">Select destination tier...</option>
                                        @foreach($tiers as $tierOption)
                                            @if($tierOption->id != $tierToDeleteId)
                                                <option value="{{ $tierOption->id }}">{{ $tierOption->name }} ({{ $tierOption->code }})</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('migrationTargetTierId') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-primary mt-3 shadow-sm px-4" 
                                            wire:click="executeMigration" 
                                            wire:loading.attr="disabled"
                                            {{ empty($selectedMembershipIds) ? 'disabled' : '' }}>
                                        <span wire:loading.remove wire:target="executeMigration">
                                            <i class="ri-arrow-right-up-line me-1"></i> Migrate Selected ({{ count($selectedMembershipIds) }})
                                        </span>
                                        <span wire:loading wire:target="executeMigration">
                                            <span class="spinner-border spinner-border-sm me-1" role="status"></span> Processing...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members Table -->
                <div class="table-responsive" style="max-height: 400px; min-height: 200px;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top" style="z-index: 10;">
                            <tr>
                                <th style="width: 40px;" class="ps-4">
                                    <div class="form-check fs-15">
                                        <input class="form-check-input" type="checkbox" wire:model.live="selectAll" id="checkAll">
                                    </div>
                                </th>
                                <th class="fs-13 text-muted text-uppercase fw-bold">Member Name</th>
                                <th class="fs-13 text-muted text-uppercase fw-bold">Email Identity</th>
                                <th class="fs-13 text-muted text-uppercase fw-bold text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($migrationMembers as $index => $member)
                                <tr>
                                    <td class="ps-4">
                                        <div class="form-check fs-15">
                                            <input class="form-check-input" type="checkbox" 
                                                   value="{{ $member['id'] }}" 
                                                   wire:model.live="selectedMembershipIds" 
                                                   id="member_{{ $member['id'] }}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 avatar-xs me-2">
                                                <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-12">
                                                    {{ strtoupper(substr($member['name'], 0, 1)) }}
                                                </div>
                                            </div>
                                            <span class="fw-medium text-dark">{{ $member['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="text-muted fs-13">{{ $member['email'] }}</td>
                                    <td class="text-end pe-4">
                                        <span class="badge bg-soft-warning text-warning text-uppercase" style="font-size: 10px;">Awaiting Migration</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-user-follow-line fs-48 mb-3 d-block opacity-25"></i>
                                            <p class="mb-0">All members have been migrated.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-light p-3 border-0">
                <div class="me-auto text-muted fs-12">
                    <i class="ri-information-line me-1"></i> {{ count($selectedMembershipIds) }} of {{ $membersOnTierCount }} members selected for migration
                </div>
                <button type="button" class="btn btn-link text-dark fw-medium text-decoration-none" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
