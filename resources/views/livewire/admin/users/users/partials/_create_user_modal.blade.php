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
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('create_name') is-invalid @enderror" wire:model.defer="create_name" placeholder="Enter full name">
                                    @error('create_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-mail-line"></i></span>
                                        <input type="email" class="form-control @error('create_email') is-invalid @enderror" wire:model.defer="create_email" placeholder="Enter email">
                                        @error('create_email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-phone-line"></i></span>
                                        <input type="text" class="form-control @error('create_phone') is-invalid @enderror" wire:model.defer="create_phone" placeholder="Enter phone">
                                        @error('create_phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Membership Tier <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-vip-crown-line text-warning"></i></span>
                                        <select class="form-select @error('create_tier_id') is-invalid @enderror" wire:model.defer="create_tier_id">
                                            <option value="">Select Tier</option>
                                            @foreach($membershipTiers as $tier)
                                                <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('create_tier_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <div class="card border border-light shadow-none mb-0">
                                        <div class="card-header bg-light border-light d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title mb-1">Login Credentials</h6>
                                                <p class="text-muted mb-0 small">Choose how the user will receive their initial password (sent via email).</p>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="btn-group w-100 mb-4" role="group">
                                                <input type="radio" class="btn-check" wire:model.live="password_option" name="pwdOption" id="pwdAuto" value="auto" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="pwdAuto">Auto-generate</label>

                                                <input type="radio" class="btn-check" wire:model.live="password_option" name="pwdOption" id="pwdManual" value="manual" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="pwdManual">Set manually</label>
                                            </div>

                                            @if($password_option === 'auto')
                                            <!-- Auto-generate UI -->
                                            <div x-data="{ 
                                                    pwType: 'password',
                                                    tempPass: '••••••••••••••••', 
                                                    generate() { 
                                                        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*';
                                                        let result = '';
                                                        for(let i=0; i<16; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
                                                        this.tempPass = result; 
                                                        this.pwType = 'text';
                                                    },
                                                    copy() {
                                                        const valToCopy = this.tempPass === '••••••••••••••••' ? 'System will auto-generate securely' : this.tempPass;
                                                        navigator.clipboard.writeText(valToCopy);
                                                        alert('Password copied to clipboard');
                                                    }
                                                }">
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text bg-light"><i class="ri-lock-password-line"></i></span>
                                                    <input x-bind:type="pwType" class="form-control bg-light text-muted" x-model="tempPass" readonly>
                                                    <button class="btn btn-outline-secondary bg-light" type="button" x-on:click="pwType = pwType === 'password' ? 'text' : 'password'" title="Toggle visibility"><i class="ri-eye-line" x-bind:class="{ 'ri-eye-off-line': pwType === 'text', 'ri-eye-line': pwType === 'password' }"></i></button>
                                                    <button class="btn btn-outline-secondary bg-light" type="button" x-on:click="copy()" title="Copy password"><i class="ri-clipboard-line"></i></button>
                                                    <button class="btn btn-outline-secondary bg-light" type="button" x-on:click="generate()" title="Regenerate secure password"><i class="ri-refresh-line"></i></button>
                                                </div>
                                                <small class="text-muted"><i class="ri-mail-send-line align-middle me-1"></i> The password will be automatically generated and emailed to the user.</small>
                                            </div>
                                            @else
                                            <!-- Manual UI -->
                                            <div class="row g-3" x-data="{ 
                                                    pwType: 'password',
                                                    strength: 0,
                                                    checkStrength(val) {
                                                        let s = 0;
                                                        if (val.length >= 6) s += 25;
                                                        if (val.length >= 10) s += 25;
                                                        if (/[A-Z]/.test(val)) s += 25;
                                                        if (/[0-9!@#\$%\^\&*\)\(+=._-]/.test(val)) s += 25;
                                                        this.strength = Math.min(100, s);
                                                    },
                                                    getStrengthText() {
                                                        if (this.strength < 50) return 'Weak';
                                                        if (this.strength < 75) return 'Okay';
                                                        return 'Strong';
                                                    },
                                                    getStrengthColor() {
                                                        if (this.strength < 50) return 'bg-danger';
                                                        if (this.strength < 75) return 'bg-warning';
                                                        return 'bg-success';
                                                    }
                                                }">
                                                <div class="col-md-6">
                                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="ri-lock-password-line"></i></span>
                                                        <input x-bind:type="pwType" class="form-control @error('create_password') is-invalid @enderror" wire:model="create_password" x-on:input.debounce.250ms="checkStrength($event.target.value)" placeholder="Enter password">
                                                        <button class="btn btn-outline-secondary" type="button" x-on:click="pwType = pwType === 'password' ? 'text' : 'password'">
                                                            <i class="ri-eye-line" x-bind:class="pwType === 'password' ? 'ri-eye-line' : 'ri-eye-off-line'"></i>
                                                        </button>
                                                    </div>
                                                    @error('create_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                    <div class="progress mt-2" style="height: 5px;" x-show="strength > 0" style="display: none;">
                                                        <div class="progress-bar" role="progressbar" x-bind:style="'width: ' + strength + '%'" x-bind:class="getStrengthColor()" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <small class="text-muted mt-1 d-block" x-show="strength > 0" style="display: none;" x-text="'Strength: ' + getStrengthText()"></small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="ri-lock-password-fill"></i></span>
                                                        <input x-bind:type="pwType" class="form-control @error('create_password_confirmation') is-invalid @enderror" wire:model.defer="create_password_confirmation" placeholder="Confirm password">
                                                        <button class="btn btn-outline-secondary" type="button" x-on:click="pwType = pwType === 'password' ? 'text' : 'password'">
                                                            <i class="ri-eye-line" x-bind:class="pwType === 'password' ? 'ri-eye-line' : 'ri-eye-off-line'"></i>
                                                        </button>
                                                    </div>
                                                    @error('create_password_confirmation') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($createStep === 2)
                        <div class="tab-pane active">
                            <!-- Optional Banner -->
                            <div class="alert alert-secondary border-0 d-flex align-items-center mb-4">
                                <div class="avatar-xs flex-shrink-0 me-3">
                                    <div class="avatar-title bg-light text-primary rounded-circle fs-16 border border-primary-subtle">
                                        <i class="ri-information-line"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Membership Application <span class="badge bg-light text-muted border ms-1">Optional</span></h6>
                                    <p class="mb-0 text-muted">This step is entirely optional. Fill out what you can, or simply click Create User to finish.</p>
                                </div>
                            </div>

                            <div class="card shadow-none border mb-0">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center p-2">
                                    <h6 class="card-title mb-0 ms-2">Application Details</h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link text-decoration-none" data-bs-toggle="collapse" data-bs-target=".application-collapse.show" aria-expanded="true">Collapse All</button>
                                        <button type="button" class="btn btn-sm btn-link text-decoration-none" data-bs-toggle="collapse" data-bs-target=".application-collapse:not(.show)" aria-expanded="false">Expand All</button>
                                    </div>
                                </div>
                                <div class="accordion accordion-flush" id="applicationAccordion">
                                    <!-- Personal Detail -->
                                    <div class="accordion-item border-0 border-bottom">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#personalCollapse">
                                                <i class="ri-user-smile-line text-muted me-2 fs-18"></i> Personal Detail
                                            </button>
                                        </h2>
                                        <div id="personalCollapse" class="accordion-collapse application-collapse collapse show" data-bs-parent="#applicationAccordion">
                                            <div class="accordion-body row g-3 bg-light bg-opacity-50">
                                                <div class="col-md-6">
                                                    <label class="form-label">Official Full Name</label>
                                                    <input type="text" class="form-control bg-white" wire:model.defer="app_full_name" placeholder="Enter full name">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Date of Birth</label>
                                                    <input type="date" class="form-control bg-white" wire:model.defer="app_dob">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Country</label>
                                                    <input type="text" class="form-control bg-white" wire:model.defer="app_country" placeholder="Enter country">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">City</label>
                                                    <input type="text" class="form-control bg-white" wire:model.defer="app_city" placeholder="Enter city">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cricket Profile -->
                                    <div class="accordion-item border-0 border-bottom">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cricketCollapse">
                                                <i class="ri-medal-2-line text-muted me-2 fs-18"></i> Cricket Profile
                                            </button>
                                        </h2>
                                        <div id="cricketCollapse" class="accordion-collapse application-collapse collapse" data-bs-parent="#applicationAccordion">
                                            <div class="accordion-body bg-light bg-opacity-50">
                                                <div class="mb-4">
                                                    <label class="form-label fw-medium mb-3">Preferred Formats</label>
                                                    <div class="row g-2">
                                                        <div class="col-sm-6 col-md-3">
                                                            <div class="form-check form-check-outline form-check-primary">
                                                                <input class="form-check-input" type="checkbox" wire:model.defer="app_preferred_formats" value="test" id="fmt1">
                                                                <label class="form-check-label" for="fmt1">Test Matches</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6 col-md-3">
                                                            <div class="form-check form-check-outline form-check-primary">
                                                                <input class="form-check-input" type="checkbox" wire:model.defer="app_preferred_formats" value="odi" id="fmt2">
                                                                <label class="form-check-label" for="fmt2">ODI</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6 col-md-3">
                                                            <div class="form-check form-check-outline form-check-primary">
                                                                <input class="form-check-input" type="checkbox" wire:model.defer="app_preferred_formats" value="t20" id="fmt3">
                                                                <label class="form-check-label" for="fmt3">T20</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6 col-md-3">
                                                            <div class="form-check form-check-outline form-check-primary">
                                                                <input class="form-check-input" type="checkbox" wire:model.defer="app_preferred_formats" value="leagues" id="fmt4">
                                                                <label class="form-check-label" for="fmt4">T20 Leagues</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="form-label fw-medium mb-3">Eras of Interest</label>
                                                    <div class="card border border-light shadow-none mb-0 bg-white">
                                                        <div class="card-body p-3">
                                                            <div class="row g-3">
                                                                <div class="col-sm-6 col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" wire:model.defer="app_eras" value="golden_age" id="era1">
                                                                        <label class="form-check-label" for="era1">Golden Age</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-6 col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" wire:model.defer="app_eras" value="post_war_50s" id="era2">
                                                                        <label class="form-check-label" for="era2">Post-War 50s</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-6 col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" wire:model.defer="app_eras" value="west_indies" id="era3">
                                                                        <label class="form-check-label" for="era3">West Indies Dominance</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-6 col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" wire:model.defer="app_eras" value="odi_90s" id="era4">
                                                                        <label class="form-check-label" for="era4">ODI 90s</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-6 col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" wire:model.defer="app_eras" value="modern" id="era5">
                                                                        <label class="form-check-label" for="era5">Modern Era</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-6 col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" wire:model.defer="app_eras" value="womens" id="era6">
                                                                        <label class="form-check-label" for="era6">Women's Cricket</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Collector Intent -->
                                    <div class="accordion-item border-0">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collectorCollapse">
                                                <i class="ri-focus-2-line text-muted me-2 fs-18"></i> Collector Intent
                                            </button>
                                        </h2>
                                        <div id="collectorCollapse" class="accordion-collapse application-collapse collapse" data-bs-parent="#applicationAccordion">
                                            <div class="accordion-body row g-4 bg-light bg-opacity-50">
                                                <div class="col-md-6">
                                                    <label class="form-label">Has Acquired Memorabilia Before?</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white"><i class="ri-shopping-bag-3-line"></i></span>
                                                        <select class="form-select bg-white" wire:model.defer="app_has_acquired_before">
                                                            <option value="no">No</option>
                                                            <option value="yes">Yes</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Primary Focus</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white"><i class="ri-crosshair-2-line"></i></span>
                                                        <select class="form-select bg-white" wire:model.defer="app_focus">
                                                            <option value="legacy">Legacy & History</option>
                                                            <option value="rarity">Rarity & Exclusivity</option>
                                                            <option value="value">Investment Value</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-12 mt-4">
                                                    <label class="form-label d-flex justify-content-between align-items-center mb-0">
                                                        Investment Horizon
                                                        <span class="badge bg-primary fs-12 px-3 py-1 rounded-pill">{{ $app_investment_horizon ?? 5 }} Years</span>
                                                    </label>
                                                    <div class="card shadow-none border border-light bg-white mt-2">
                                                        <div class="card-body py-3 px-4">
                                                            <input type="range" class="form-range" min="1" max="25" wire:model.live="app_investment_horizon">
                                                            <div class="d-flex justify-content-between text-muted small mt-1">
                                                                <span>1 Year</span>
                                                                <span>25 Years</span>
                                                            </div>
                                                        </div>
                                                    </div>
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
            <div class="modal-footer bg-light p-3 border-top d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
                <div class="d-flex gap-2">
                    @if($createStep > 1)
                        <button type="button" class="btn btn-outline-secondary" wire:click="prevStep"><i class="ri-arrow-left-line align-middle me-1"></i> Back</button>
                    @endif

                    @if($createStep < 2)
                        <button type="button" class="btn btn-primary" wire:click="nextStep">Next Step <i class="ri-arrow-right-line align-middle ms-1"></i></button>
                    @else
                        <button type="button" class="btn btn-success" wire:click="storeUser" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-check-line align-middle me-1"></i> Create User</span>
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
