<!-- User View Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true" wire:ignore.self data-bs-backdrop="static"
     data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title" id="userModalLabel">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Personal Details -->
                    <div class="col-md-6 mb-4">
                        <h6 class="fs-14 text-uppercase fw-semibold mb-3">Personal Information</h6>
                        <div class="table-responsive">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <th class="ps-0" scope="row">Name :</th>
                                        <td class="text-muted">{{ $name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0" scope="row">Email :</th>
                                        <td class="text-muted">{{ $email }}</td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0" scope="row">Phone :</th>
                                        <td class="text-muted">{{ $phone ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0" scope="row">Joined :</th>
                                        <td class="text-muted">{{ \App\Models\User::find($userId)?->created_at->format('d M, Y') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Membership Details -->
                    <div class="col-md-6 mb-4">
                        <h6 class="fs-14 text-uppercase fw-semibold mb-3">Membership Status</h6>
                        @if($tierInfo)
                            <div class="d-flex align-items-center mb-2">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-info fs-12">{{ $tierInfo->tier_name }}</span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fs-14 mb-0 text-capitalize">{{ $tierInfo->status }}</h6>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-borderless table-sm mb-0">
                                    <tbody>
                                        <tr>
                                            <th class="ps-0" scope="row">Started :</th>
                                            <td class="text-muted">{{ $tierInfo->started_at ? \Carbon\Carbon::parse($tierInfo->started_at)->format('d M, Y') : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-0" scope="row">Expires :</th>
                                            <td class="text-muted">{{ $tierInfo->expires_at ? \Carbon\Carbon::parse($tierInfo->expires_at)->format('d M, Y') : 'Never' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="ri-error-warning-line fs-16 align-middle me-2"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading fs-14">Registration Incomplete</h6>
                                        <p class="mb-2 fs-13">This user has not completed their registration wizard or tier selection.</p>
                                        
                                        <hr class="border-warning-subtle opacity-50 my-2">
                                        
                                        <h6 class="fs-12 text-uppercase fw-bold mb-2">Complete Manually</h6>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <select class="form-select form-select-sm @error('complete_tier_id') is-invalid @enderror" wire:model="complete_tier_id">
                                                    <option value="">Select Membership Tier...</option>
                                                    @foreach(\App\Models\MembershipTier::all() as $tier)
                                                        <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('complete_tier_id') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-12">
                                                <input type="date" class="form-control form-control-sm @error('complete_expires_at') is-invalid @enderror" 
                                                       wire:model="complete_expires_at" placeholder="Expiry Date (Optional)">
                                                <div class="form-text fs-10 text-muted">Expiry Date (Optional)</div>
                                                @error('complete_expires_at') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-12 mt-2">
                                                <button type="button" class="btn btn-primary btn-sm w-100" wire:click="completeRegistration" wire:loading.attr="disabled">
                                                    <span wire:loading.remove wire:target="completeRegistration">Complete Registration</span>
                                                    <span wire:loading wire:target="completeRegistration">Processing...</span>
                                                </button>
                                            </div>
                                            @error('complete_registration_error')
                                                <div class="col-12 mt-2">
                                                    <div class="text-danger fs-11 text-center">{{ $message }}</div>
                                                </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                         <h6 class="fs-14 text-uppercase fw-semibold mb-3">Recent Applications</h6>
                         <div class="table-responsive table-card">
                             <table class="table table-nowrap align-middle mb-0">
                                 <thead class="table-light text-muted">
                                     <tr>
                                         <th scope="col">Application ID</th>
                                         <th scope="col">Tier</th>
                                         <th scope="col">Status</th>
                                         <th scope="col">Submitted</th>
                                         <th scope="col">Reviewed</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($applications ?? [] as $app)
                                     <tr>
                                         <td>#{{ $app->id }}</td>
                                         <td>{{ $app->tier_name }}</td>
                                         <td>
                                             @if($app->status === 'approved')
                                                 <span class="badge bg-success-subtle text-success text-uppercase">{{ $app->status }}</span>
                                             @elseif($app->status === 'rejected')
                                                 <span class="badge bg-danger-subtle text-danger text-uppercase">{{ $app->status }}</span>
                                             @elseif($app->status === 'under_review')
                                                  <span class="badge bg-warning-subtle text-warning text-uppercase">{{ $app->status }}</span>
                                             @else
                                                  <span class="badge bg-light text-body text-uppercase">{{ $app->status }}</span>
                                             @endif
                                         </td>
                                         <td>{{ $app->submitted_at ? \Carbon\Carbon::parse($app->submitted_at)->format('d M, Y') : 'Draft' }}</td>
                                         <td>{{ $app->reviewed_at ? \Carbon\Carbon::parse($app->reviewed_at)->format('d M, Y') : '-' }}</td>
                                     </tr>
                                     @empty
                                     <tr>
                                         <td colspan="5" class="text-center text-muted">No applications found.</td>
                                     </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" wire:click="$dispatch('close-modal')">Close</button>
            </div>
        </div>
    </div>
</div>

