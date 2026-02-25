<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true" wire:ignore.self data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                <button type="button" class="btn-close" wire:click="closeModal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Wizard Navigation -->
                <div class="user-creation-stepper mb-4">
                    <div class="uc-stepper d-flex justify-content-between position-relative">
                        <!-- Step 1: Essentials -->
                        <div class="uc-step">
                            <button class="uc-pill btn {{ $createStep === 1 ? 'active' : '' }} {{ $createStep > 1 ? 'done' : '' }}" type="button">
                                <span class="step-icon">
                                    @if($createStep > 1) <i class="ri-check-line"></i> @else 1 @endif
                                </span>
                                <span class="step-label d-none d-sm-block">Registration Essentials</span>
                            </button>
                        </div>

                        <!-- Step 2: Application Data -->
                        <div class="uc-step">
                            <button class="uc-pill btn {{ $createStep === 2 ? 'active' : '' }} {{ $createStep > 2 ? 'done' : '' }}" type="button">
                                <span class="step-icon">
                                    @if($createStep > 2) <i class="ri-check-line"></i> @else 2 @endif
                                </span>
                                <span class="step-label d-none d-sm-block">Membership Application</span>
                            </button>
                        </div>
                    </div>
                </div>

                @if($errors->has('create_user_error'))
                    <div class="alert alert-danger">
                        {{ $errors->first('create_user_error') }}
                    </div>
                @endif

                <!-- Wizard Content -->
                <div class="tab-content text-muted">
                    @if($createStep === 1)
                        <div class="tab-pane active">
                            <div class="row g-3">
                                <div class="col-lg-12">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('create_name') is-invalid @enderror" wire:model.defer="create_name" placeholder="Enter full name">
                                    @error('create_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('create_email') is-invalid @enderror" wire:model.defer="create_email" placeholder="Enter email">
                                    @error('create_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('create_phone') is-invalid @enderror" wire:model.defer="create_phone" placeholder="Enter phone">
                                    @error('create_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-lg-12">
                                    <label class="form-label">Membership Tier <span class="text-danger">*</span></label>
                                    <select class="form-select @error('create_tier_id') is-invalid @enderror" wire:model.defer="create_tier_id">
                                        <option value="">Select Tier</option>
                                        @foreach($membershipTiers as $tier)
                                            <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('create_tier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                
                                <div class="col-lg-12 mt-4">
                                    <h6 class="fw-semibold">Password Configuration</h6>
                                    <div class="d-flex gap-4 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" wire:model.live="password_option" value="auto" id="pwdAuto">
                                            <label class="form-check-label" for="pwdAuto">Auto-generate password</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" wire:model.live="password_option" value="manual" id="pwdManual">
                                            <label class="form-check-label" for="pwdManual">Set password manually</label>
                                        </div>
                                    </div>
                                </div>

                                @if($password_option === 'manual')
                                <div class="col-lg-6">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('create_password') is-invalid @enderror" wire:model.defer="create_password" placeholder="Enter password">
                                    @error('create_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('create_password_confirmation') is-invalid @enderror" wire:model.defer="create_password_confirmation" placeholder="Confirm password">
                                    @error('create_password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                @endif
                            </div>
                        </div>
                    @elseif($createStep === 2)
                        <div class="tab-pane active">
                            <div class="alert alert-info">
                                <i class="ri-information-line align-middle me-2"></i> This step is optional. You can fill as much as needed or click "Create User" to finish.
                            </div>

                            <div class="accordion accordion-flush" id="applicationAccordion">
                                <!-- Personal Detail -->
                                <div class="accordion-item shadow-none border">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#personalCollapse">
                                            Personal Detail
                                        </button>
                                    </h2>
                                    <div id="personalCollapse" class="accordion-collapse collapse show" data-bs-parent="#applicationAccordion">
                                        <div class="accordion-body row g-3">
                                            <div class="col-lg-6">
                                                <label class="form-label">Official Full Name</label>
                                                <input type="text" class="form-control" wire:model.defer="app_full_name">
                                            </div>
                                            <div class="col-lg-6">
                                                <label class="form-label">Date of Birth</label>
                                                <input type="date" class="form-control" wire:model.defer="app_dob">
                                            </div>
                                            <div class="col-lg-6">
                                                <label class="form-label">Country</label>
                                                <input type="text" class="form-control" wire:model.defer="app_country">
                                            </div>
                                            <div class="col-lg-6">
                                                <label class="form-label">City</label>
                                                <input type="text" class="form-control" wire:model.defer="app_city">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cricket Profile -->
                                <div class="accordion-item shadow-none border">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cricketCollapse">
                                            Cricket Profile
                                        </button>
                                    </h2>
                                    <div id="cricketCollapse" class="accordion-collapse collapse" data-bs-parent="#applicationAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <label class="form-label d-block">Preferred Formats</label>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" wire:model.defer="app_preferred_formats" value="test" id="fmt1">
                                                    <label class="form-check-label" for="fmt1">Test</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" wire:model.defer="app_preferred_formats" value="odi" id="fmt2">
                                                    <label class="form-check-label" for="fmt2">ODI</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" wire:model.defer="app_preferred_formats" value="t20" id="fmt3">
                                                    <label class="form-check-label" for="fmt3">T20</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" wire:model.defer="app_preferred_formats" value="leagues" id="fmt4">
                                                    <label class="form-check-label" for="fmt4">Leagues</label>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label d-block">Eras of Interest</label>
                                                <select class="form-select" wire:model.defer="app_eras" multiple style="height: 100px;">
                                                    <option value="golden_age">Golden Age</option>
                                                    <option value="post_war_50s">Post-War 50s</option>
                                                    <option value="west_indies">West Indies Dominance</option>
                                                    <option value="odi_90s">ODI 90s</option>
                                                    <option value="modern">Modern Era</option>
                                                    <option value="womens">Women's Cricket</option>
                                                </select>
                                                <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Collector Intent -->
                                <div class="accordion-item shadow-none border">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collectorCollapse">
                                            Collector Intent
                                        </button>
                                    </h2>
                                    <div id="collectorCollapse" class="accordion-collapse collapse" data-bs-parent="#applicationAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <label class="form-label">Acquired Before?</label>
                                                    <select class="form-select" wire:model.defer="app_has_acquired_before">
                                                        <option value="no">No</option>
                                                        <option value="yes">Yes</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Focus</label>
                                                    <select class="form-select" wire:model.defer="app_focus">
                                                        <option value="legacy">Legacy</option>
                                                        <option value="rarity">Rarity</option>
                                                        <option value="value">Value</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Investment Horizon (Years)</label>
                                                    <input type="range" class="form-range" min="1" max="25" wire:model.live="app_investment_horizon">
                                                    <div class="text-center fw-bold">{{ $app_investment_horizon }} Years</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                </div>
                <div class="d-flex gap-2">
                    @if($createStep > 1)
                        <button type="button" class="btn btn-light" wire:click="prevStep">Back</button>
                    @endif

                    @if($createStep < 2)
                        <button type="button" class="btn btn-primary" wire:click="nextStep">Next Step <i class="ri-arrow-right-line align-middle ms-1"></i></button>
                    @else
                        <button type="button" class="btn btn-success" wire:click="storeUser" wire:loading.attr="disabled">
                            <span wire:loading.remove>Create User</span>
                            <span wire:loading><span class="spinner-border spinner-border-sm me-2"></span>Creating...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.user-creation-stepper .uc-stepper { gap: 24px; }
.user-creation-stepper .uc-step { flex: 1 1 0; position: relative; display: flex; justify-content: center; }
.user-creation-stepper .uc-step:not(:last-child)::after {
    content: ""; position: absolute; top: 50%; left: calc(50% + 100px); right: -100px; height: 3px;
    background: var(--vz-light); transform: translateY(-50%); z-index: 1;
}
@media (max-width: 576px) { .user-creation-stepper .uc-step:not(:last-child)::after { left: calc(50% + 40px); right: -40px; } }
.user-creation-stepper .uc-pill { 
    position: relative; z-index: 2; background: var(--vz-card-bg-custom, #fff); border: 2px solid var(--vz-light); 
    border-radius: 999px; padding: 10px 22px; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;
    color: var(--vz-body-color);
}
.user-creation-stepper .uc-pill.active { border-color: var(--vz-primary); color: var(--vz-primary); }
.user-creation-stepper .uc-pill.done { border-color: var(--vz-success); color: var(--vz-success); background-color: rgba(10, 187, 135, 0.1); }
.user-creation-stepper .step-icon {
    display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%;
    background-color: var(--vz-light); color: var(--vz-muted); font-size: 12px; font-weight: 700;
}
.user-creation-stepper .uc-pill.active .step-icon, .user-creation-stepper .uc-pill.done .step-icon { background-color: var(--vz-success); color: #fff; }
</style>
