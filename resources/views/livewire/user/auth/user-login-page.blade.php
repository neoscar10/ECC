<div class="ecc-user-login position-relative overflow-hidden">
    {{-- Background glows --}}
    <div class="ecc-glow-1 position-absolute top-0 start-50 translate-middle-x"></div>
    <div class="ecc-glow-2 position-absolute bottom-0 end-0"></div>

    <div class="position-relative z-2 d-flex min-vh-100 align-items-center justify-content-center px-3 py-4 py-md-4 py-lg-3">
        <div class="ecc-login-shell w-100">
            {{-- Crest --}}
            <div class="text-center mb-3 mb-md-4">
                <div class="ecc-crest mx-auto">
                    <div class="ecc-crest-sheen"></div>
                    <div class="ecc-crest-img" aria-label="Executive Cricket Club crest"></div>
                </div>
            </div>

            {{-- Title --}}
            <div class="text-center mb-4">
                <div class="ecc-title text-uppercase">
                    @if($mode === 'password') Member Login
                    @elseif($mode === 'forgot') Reset Passcode
                    @elseif($mode === 'otp') OTP Access
                    @endif
                </div>
                <div class="ecc-subtext mx-auto">
                    @if($mode === 'password')
                        Access reserved for approved members
                        <span class="d-none d-md-inline"> of Executive Cricket Club.</span>
                        <span class="d-md-none"><br>of Executive Cricket Club.</span>
                    @elseif($mode === 'forgot')
                        @if($step === 1) Enter your identity to receive a reset code.
                        @else Verification code sent to {{ $otpIdentifier }}.
                        @endif
                    @elseif($mode === 'otp')
                        @if($step === 1) Enter your identity to request secure access.
                        @else Verification code sent to {{ $otpIdentifier }}.
                        @endif
                    @endif
                </div>
            </div>

            {{-- Feedback --}}
            @if ($errorMessage)
                <div class="alert alert-danger py-2 small mb-3">{{ $errorMessage }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success py-2 small mb-3">{{ session('success') }}</div>
            @endif

            {{-- --- PASSWORD LOGIN MODE --- --}}
            @if($mode === 'password')
                <form wire:submit.prevent="submit" class="ecc-form">
                    {{-- Identity --}}
                    <div class="ecc-field mb-3 mb-md-4">
                        <label for="ecc_identity" class="ecc-label text-uppercase">Identity</label>
                        <div class="position-relative">
                            <span class="material-symbols-outlined ecc-input-icon">mail</span>
                            <input
                                id="ecc_identity"
                                type="text"
                                wire:model="identity"
                                class="form-control ecc-input ps-5"
                                placeholder="Email / Registered Mobile Number"
                                autocomplete="username"
                            >
                        </div>
                        @error('identity') <div class="ecc-error mt-2">{{ $message }}</div> @enderror
                    </div>

                    {{-- Password --}}
                    <div class="ecc-field mb-3 mb-md-4">
                        <label for="ecc_password" class="ecc-label text-uppercase">Passcode</label>
                        <div class="position-relative">
                            <span class="material-symbols-outlined ecc-input-icon">lock</span>
                            <input
                                id="ecc_password"
                                type="{{ $showPassword ? 'text' : 'password' }}"
                                wire:model="password"
                                class="form-control ecc-input ps-5 pe-5"
                                placeholder="Password"
                                autocomplete="current-password"
                            >
                            <button
                                type="button"
                                wire:click="togglePassword"
                                class="ecc-eye-btn"
                                aria-label="Toggle password visibility"
                            >
                                <span class="material-symbols-outlined">{{ $showPassword ? 'visibility_off' : 'visibility' }}</span>
                            </button>
                        </div>
                        @error('password') <div class="ecc-error mt-2">{{ $message }}</div> @enderror
                    </div>

                    {{-- Submit --}}
                    <div class="pt-2">
                        <button type="submit" class="ecc-submit-btn w-100" wire:loading.attr="disabled" wire:target="submit">
                            <span class="ecc-btn-default d-inline-flex align-items-center justify-content-center gap-2" wire:loading.class="d-none" wire:target="submit">
                                Enter The Club
                                <span class="material-symbols-outlined ecc-arrow">arrow_forward</span>
                            </span>
                            <span class="ecc-btn-loading d-none align-items-center justify-content-center gap-2" wire:loading.delay.class.remove="d-none" wire:target="submit">
                                Signing in...
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            </span>
                        </button>
                    </div>
                </form>

                {{-- Links outside form to prevent accidental submission --}}
                <div class="d-flex justify-content-between align-items-center mt-4 px-1 flex-wrap gap-3">
                    <a href="javascript:void(0)" wire:click="setMode('forgot')" class="ecc-link text-uppercase">Forgot Password?</a>
                    <a href="javascript:void(0)" wire:click="setMode('otp')" class="ecc-link text-uppercase">Login with OTP</a>
                </div>

            {{-- --- FORGOT PASSWORD MODE --- --}}
            @elseif($mode === 'forgot')
                @if($step === 1)
                    {{-- Step 1: Request OTP --}}
                    <form wire:submit.prevent="requestResetOtp" class="ecc-form">
                        <div class="ecc-field mb-3 mb-md-4">
                            <label for="forgot_identity" class="ecc-label text-uppercase">Account Identity</label>
                            <div class="position-relative">
                                <span class="material-symbols-outlined ecc-input-icon">person</span>
                                <input
                                    id="forgot_identity"
                                    type="text"
                                    wire:model="identity"
                                    class="form-control ecc-input ps-5"
                                    placeholder="Enter Email or Mobile"
                                >
                            </div>
                            @error('identity') <div class="ecc-error mt-2">{{ $message }}</div> @enderror
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="ecc-submit-btn w-100" wire:loading.attr="disabled" wire:target="requestResetOtp">
                                <span class="ecc-btn-default d-inline-flex align-items-center justify-content-center gap-2" wire:loading.class="d-none" wire:target="requestResetOtp">
                                    Send Verification Code
                                    <span class="material-symbols-outlined ecc-arrow">send</span>
                                </span>
                                <span class="ecc-btn-loading d-none align-items-center justify-content-center gap-2" wire:loading.delay.class.remove="d-none" wire:target="requestResetOtp">
                                    Processing...
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                </span>
                            </button>
                        </div>

                        <div class="text-center mt-4">
                            <a href="#" wire:click.prevent="setMode('password')" class="ecc-link text-uppercase">Back to Login</a>
                        </div>
                    </form>
                @else
                    {{-- Step 2: Verify & Reset --}}
                    <form wire:submit.prevent="verifyResetOtp" class="ecc-form">
                        <div class="ecc-field mb-3 mb-md-4 text-center">
                            <label class="ecc-label text-uppercase d-block mb-3">Verification Code</label>
                            <input
                                type="text"
                                wire:model="otp"
                                class="form-control ecc-input text-center fs-2 fw-bold"
                                placeholder="- - - - - -"
                                maxlength="6"
                                style="letter-spacing: 0.6em; padding-left: 0.6em; font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 28px !important; height: 64px; border-color: rgba(242,185,13,.4);"
                            >
                            @error('otp') <div class="ecc-error mt-2 text-center">{{ $message }}</div> @enderror
                        </div>

                        <div class="ecc-field mb-3">
                            <label for="new_password" class="ecc-label text-uppercase">New Passcode</label>
                            <div class="position-relative">
                                <span class="material-symbols-outlined ecc-input-icon">lock_reset</span>
                                <input
                                    id="new_password"
                                    type="{{ $showResetPassword ? 'text' : 'password' }}"
                                    wire:model="newPassword"
                                    class="form-control ecc-input ps-5 pe-5"
                                    placeholder="Min 8 characters"
                                >
                                <button
                                    type="button"
                                    wire:click="toggleResetPassword"
                                    class="ecc-eye-btn"
                                    aria-label="Toggle password visibility"
                                >
                                    <span class="material-symbols-outlined">{{ $showResetPassword ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </div>
                            @error('newPassword') <div class="ecc-error mt-2">{{ $message }}</div> @enderror
                        </div>

                        <div class="ecc-field mb-3 mb-md-4">
                            <label for="new_password_confirmation" class="ecc-label text-uppercase">Confirm Passcode</label>
                            <div class="position-relative">
                                <span class="material-symbols-outlined ecc-input-icon">done_all</span>
                                <input
                                    id="new_password_confirmation"
                                    type="{{ $showResetPassword ? 'text' : 'password' }}"
                                    wire:model="newPassword_confirmation"
                                    class="form-control ecc-input ps-5 pe-5"
                                    placeholder="Repeat new password"
                                >
                                <button
                                    type="button"
                                    wire:click="toggleResetPassword"
                                    class="ecc-eye-btn"
                                    aria-label="Toggle password visibility"
                                >
                                    <span class="material-symbols-outlined">{{ $showResetPassword ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="ecc-submit-btn w-100" wire:loading.attr="disabled" wire:target="verifyResetOtp">
                                <span class="ecc-btn-default d-inline-flex align-items-center justify-content-center gap-2" wire:loading.class="d-none" wire:target="verifyResetOtp">
                                    Update Password & Login
                                    <span class="material-symbols-outlined ecc-arrow">verified_user</span>
                                </span>
                                <span class="ecc-btn-loading d-none align-items-center justify-content-center gap-2" wire:loading.delay.class.remove="d-none" wire:target="verifyResetOtp">
                                    Finalizing...
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                </span>
                            </button>
                        </div>

                        <div class="text-center mt-4">
                            <a href="#" wire:click.prevent="requestResetOtp" class="ecc-link text-uppercase">Resend Code</a>
                            <span class="mx-2 opacity-25">|</span>
                            <a href="#" wire:click.prevent="setMode('password')" class="ecc-link text-uppercase">Back to Login</a>
                        </div>
                    </form>
                @endif

            {{-- --- OTP LOGIN MODE --- --}}
            @elseif($mode === 'otp')
                @if($step === 1)
                    {{-- Step 1: Request OTP --}}
                    <form wire:submit.prevent="requestLoginOtp" class="ecc-form">
                        <div class="ecc-field mb-3 mb-md-4">
                            <label for="otp_identity" class="ecc-label text-uppercase">Account Identity</label>
                            <div class="position-relative">
                                <span class="material-symbols-outlined ecc-input-icon">person</span>
                                <input
                                    id="otp_identity"
                                    type="text"
                                    wire:model="identity"
                                    class="form-control ecc-input ps-5"
                                    placeholder="Email or Registered Mobile"
                                >
                            </div>
                            @error('identity') <div class="ecc-error mt-2">{{ $message }}</div> @enderror
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="ecc-submit-btn w-100" wire:loading.attr="disabled" wire:target="requestLoginOtp">
                                <span class="ecc-btn-default d-inline-flex align-items-center justify-content-center gap-2" wire:loading.class="d-none" wire:target="requestLoginOtp">
                                    Request Access Code
                                    <span class="material-symbols-outlined ecc-arrow">key</span>
                                </span>
                                <span class="ecc-btn-loading d-none align-items-center justify-content-center gap-2" wire:loading.delay.class.remove="d-none" wire:target="requestLoginOtp">
                                    Requesting...
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                </span>
                            </button>
                        </div>

                        <div class="text-center mt-4">
                            <a href="#" wire:click.prevent="setMode('password')" class="ecc-link text-uppercase">Login with Password</a>
                        </div>
                    </form>
                @else
                    {{-- Step 2: Verify & Login --}}
                    <form wire:submit.prevent="verifyLoginOtp" class="ecc-form">
                        <div class="ecc-field mb-3 mb-md-4 text-center">
                            <label class="ecc-label text-uppercase d-block mb-3">Verification Code</label>
                            <input
                                type="text"
                                wire:model="otp"
                                class="form-control ecc-input text-center fs-2 fw-bold"
                                placeholder="- - - - - -"
                                maxlength="6"
                                style="letter-spacing: 0.6em; padding-left: 0.6em; font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 28px !important; height: 64px; border-color: rgba(242,185,13,.4);"
                            >
                            @error('otp') <div class="ecc-error mt-2 text-center">{{ $message }}</div> @enderror
                            <div class="mt-3 fs-11" style="color: rgba(242,185,13,.5);">Code expires in {{ $otpTtl }} minutes.</div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="ecc-submit-btn w-100" wire:loading.attr="disabled" wire:target="verifyLoginOtp">
                                <span class="ecc-btn-default d-inline-flex align-items-center justify-content-center gap-2" wire:loading.class="d-none" wire:target="verifyLoginOtp">
                                    Verify & Access Club
                                    <span class="material-symbols-outlined ecc-arrow">check_circle</span>
                                </span>
                                <span class="ecc-btn-loading d-none align-items-center justify-content-center gap-2" wire:loading.delay.class.remove="d-none" wire:target="verifyLoginOtp">
                                    Verifying...
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                </span>
                            </button>
                        </div>

                        <div class="text-center mt-4">
                            <a href="#" wire:click.prevent="requestLoginOtp" class="ecc-link text-uppercase">Resend Code</a>
                            <span class="mx-2 opacity-25">|</span>
                            <a href="#" wire:click.prevent="setMode('password')" class="ecc-link text-uppercase">Back to Login</a>
                        </div>
                    </form>
                @endif
            @endif

            {{-- Footer --}}
            <div class="text-center mt-4 mt-lg-3">
                <div class="ecc-footer d-inline-flex align-items-center gap-2 text-uppercase">
                    <span class="material-symbols-outlined" style="font-size: 14px;">shield_lock</span>
                    <span>v1.0.2 • Secure Vault Connection</span>
                </div>
            </div>
        </div>
    </div>

    @teleport('body')
    {{-- Admin Redirect Modal (Bootstrap) --}}
    <div wire:ignore.self class="modal fade ecc-admin-modal" id="eccAdminRedirectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content ecc-admin-modal__content">
                <div class="modal-body p-4 p-md-5">
                    <div class="text-center">
                        <div class="ecc-admin-modal__badge mx-auto mb-3">
                            <span class="material-symbols-outlined">shield</span>
                        </div>

                        <h5 class="ecc-admin-modal__title mb-2">Welcome back</h5>
                        <div class="ecc-admin-modal__subtitle mb-4">
                            Those details belong to an <span class="ecc-admin-modal__gold">Admin</span> account.
                            Continue to the Admin Dashboard?
                        </div>

                        <div class="ecc-admin-modal__meta mx-auto mb-4">
                            <div class="ecc-admin-modal__meta-row">
                                <span class="ecc-admin-modal__meta-label">Account</span>
                                <span class="ecc-admin-modal__meta-value">{{ $adminCandidateEmail }}</span>
                            </div>
                            @if($adminCandidateRoleLabel)
                                <div class="ecc-admin-modal__meta-row">
                                    <span class="ecc-admin-modal__meta-label">Role</span>
                                    <span class="ecc-admin-modal__meta-value">{{ $adminCandidateRoleLabel }}</span>
                                </div>
                            @endif
                        </div>

                        @if($adminModalError)
                            <div class="alert alert-danger py-2 small text-start mb-3">
                                {{ $adminModalError }}
                            </div>
                        @endif

                        <div class="d-grid gap-2">
                            <button type="button"
                                    class="ecc-admin-modal__btn ecc-admin-modal__btn--primary"
                                    wire:click="continueAsAdmin"
                                    wire:loading.attr="disabled"
                                    wire:target="continueAsAdmin">

                                {{-- Default state --}}
                                <span class="ecc-admin-btn-default d-inline-flex align-items-center justify-content-center gap-2"
                                      wire:loading.class="d-none"
                                      wire:target="continueAsAdmin">
                                    Continue to Admin Dashboard
                                    <span class="material-symbols-outlined" style="font-size:20px;">arrow_forward</span>
                                </span>

                                {{-- Loading state (HIDDEN by default; shows ONLY on continueAsAdmin) --}}
                                <span class="ecc-admin-btn-loading d-none align-items-center justify-content-center gap-2"
                                      wire:loading.delay.class.remove="d-none"
                                      wire:target="continueAsAdmin">
                                    Signing in...
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                </span>
                            </button>

                            <button type="button"
                                    class="ecc-admin-modal__btn ecc-admin-modal__btn--ghost"
                                    wire:click="cancelAdminRedirect">
                                Stay on Member Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endteleport
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('eccAdminRedirectModal');
  if (!el || !window.bootstrap) return;

  const getModal = () => bootstrap.Modal.getOrCreateInstance(el, {
    backdrop: 'static',
    keyboard: true
  });

  window.addEventListener('ecc-admin-modal-open', () => getModal().show());
  window.addEventListener('ecc-admin-modal-close', () => getModal().hide());

  // keep Livewire state in sync if user closes by ESC
  el.addEventListener('hidden.bs.modal', () => {
    try {
      const root = document.querySelector('[wire\\:id]');
      if (!root || !window.Livewire) return;
      const cmp = Livewire.find(root.getAttribute('wire:id'));
      if (cmp && cmp.get('showAdminModal')) cmp.call('cancelAdminRedirect');
    } catch (e) {}
  });
});

document.addEventListener('click', function (e) {
    const field = e.target.closest('.ecc-field');
    if (!field) return;

    // do not steal click from password visibility button
    if (e.target.closest('.ecc-eye-btn')) return;

    const input = field.querySelector('input');
    if (input) input.focus();
});
</script>
@endpush

@push('styles')
<style>
    .ecc-user-login{
        min-height: 100dvh;
        background: #020202;
        color: var(--ecc-gold-400);
        font-family: "Manrope", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        isolation: isolate;
    }

    /* Glows */
    .ecc-glow-1{
        width: 560px; height: 560px;
        top: -22%;
        background: rgba(199,167,90,.10);
        border-radius: 9999px;
        filter: blur(140px);
        pointer-events: none !important;
    }
    .ecc-glow-2{
        width: 520px; height: 520px;
        bottom: -18%;
        right: -12%;
        background: rgba(199,167,90,.06);
        border-radius: 9999px;
        filter: blur(120px);
        pointer-events: none !important;
    }

    /* Shell */
    .ecc-login-shell{
        width: 100%;
        max-width: 420px;
        margin: 0 auto;
    }
    @media (min-width: 992px){
        .ecc-login-shell{ max-width: 560px; }
    }

    /* Crest */
    .ecc-crest{
        width: 112px;
        height: 112px;
        border-radius: 9999px;
        background: linear-gradient(to bottom, #0a0a0a, #000);
        border: 1px solid rgba(199,167,90,.30);
        position: relative;
        overflow: hidden;
        box-shadow: 0 30px 80px rgba(0,0,0,.60);
    }
    @media (min-width: 992px){
        .ecc-crest{ width: 104px; height: 104px; }
    }

    .ecc-crest-sheen{
        position:absolute; inset:0;
        background: rgba(199,167,90,.06);
        opacity: 0;
        transition: opacity 600ms ease;
    }
    .ecc-crest:hover .ecc-crest-sheen{ opacity: 1; }

    .ecc-crest-img{
        width: 64px; height: 64px;
        position:absolute; inset:0;
        margin:auto;
        background-size: contain;
        background-position: center;
        background-repeat: no-repeat;
        background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAhzOGy9CW7AuHZ9r5_EEGr9I-sEK3Ax0P0Hd-DdfRe-Op0DDV1XrbxKYcBaz6GtF4l_D259OWN-BuRLDP92r4laRvHvwrYUKRC7n5urXerZi0M0wVp1s165Blw-VM7TpVZP-_VpDSyZ1EmJ_XWssVjn7FvpCjMG3c1D7g4nlg1L7QqengsSioKoNUKuOsK8sbEAkk6qwK3bkCTG-bWIFzXCexcPc_1avCBMqGK6oiUOueq5ybIvCY6Oo-yGOwqQLYBXOBRaKve5oB8');
        filter: invert(86%) sepia(23%) saturate(2329%) hue-rotate(358deg) brightness(96%) contrast(96%);
    }

    /* Title */
    .ecc-title{
        letter-spacing: .26em;
        font-size: clamp(20px, 4.4vw, 26px);
        font-weight: 800;
        color: var(--ecc-gold-400);
        white-space: nowrap; /* prevents ugly wrapping on mobile */
    }
    .ecc-subtext{
        margin-top: 10px;
        max-width: 360px;
        font-size: 14px;
        font-weight: 600;
        color: rgba(199,167,90,.60);
        letter-spacing: .03em;
        line-height: 1.6;
    }

    /* Labels */
    .ecc-label{
        display:block;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .20em;
        color: rgba(199,167,90,.80);
        margin-bottom: 10px;
        margin-left: 4px;
    }

    /* Inputs */
    .ecc-input{
        height: 56px;
        border-radius: 12px;
        background: rgba(199,167,90,.03);
        border: 1px solid rgba(199,167,90,.20);
        color: var(--ecc-gold-300);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        transition: all 220ms ease;
        width: 100%;
        position: relative;
        z-index: 2;
        cursor: text;
        caret-color: var(--ecc-gold-400);
    }
    .ecc-input::placeholder{ color: rgba(199,167,90,.30); }
    .ecc-input:focus{
        background: rgba(199,167,90,.08);
        border-color: rgba(199,167,90,.80);
        box-shadow: none;
        color: var(--ecc-gold-300);
    }

    .ecc-input-icon{
        position:absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(199,167,90,.60);
        font-size: 22px;
        pointer-events: none !important;
        z-index: 3;
    }

    /* Eye button */
    .ecc-eye-btn{
        position:absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        height: 44px;
        width: 44px;
        border: 0;
        background: transparent;
        color: rgba(199,167,90,.55);
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius: 10px;
        transition: all 200ms ease;
        z-index: 4;
        pointer-events: auto;
    }
    .ecc-eye-btn:hover{ color: var(--ecc-gold-400); background: rgba(199,167,90,.06); }

    /* Submit */
    .ecc-submit-btn{
        height: 56px;
        border-radius: 12px;
        border: 1px solid rgba(199,167,90,.55);
        background: linear-gradient(90deg, var(--ecc-gold-400), var(--ecc-gold-500));
        color: #020202;
        font-weight: 900;
        letter-spacing: .15em;
        text-transform: uppercase;
        font-size: 13px;
        box-shadow: 0 18px 50px rgba(199,167,90,.12);
        transition: all 240ms ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
    }
    @media (min-width: 992px){
        .ecc-submit-btn{ height: 54px; }
    }
    .ecc-submit-btn:hover{ filter: brightness(1.08); transform: translateY(-1px); }
    .ecc-submit-btn:active{ transform: scale(.985); }

    .ecc-arrow{ font-size: 20px; transition: transform 220ms ease; }
    .ecc-submit-btn:hover .ecc-arrow{ transform: translateX(3px); }

    /* Loading span needs flex when shown */
    .ecc-btn-loading{
        display: inline-flex;
    }

    /* Links */
    .ecc-link{
        font-size: 11px;
        letter-spacing: .18em;
        color: rgba(199,167,90,.60);
        text-decoration: none;
        font-weight: 700;
    }
    .ecc-link:hover{ color: var(--ecc-gold-400); text-decoration: underline; }

    /* Footer */
    .ecc-footer{
        font-size: 10px;
        letter-spacing: .25em;
        color: rgba(199,167,90,.30);
        text-align: center;
    }

    .ecc-error{
        color: rgba(255, 120, 120, .95);
        font-size: 12px;
        font-weight: 600;
    }

    /* Autofill fix */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active{
        -webkit-text-fill-color: var(--ecc-gold-400) !important;
        -webkit-box-shadow: 0 0 0 30px #050505 inset !important;
        transition: background-color 5000s ease-in-out 0s;
    }

    /* Admin redirect modal (matches dark+gold theme) */
    .ecc-admin-modal .modal-dialog{ max-width: 520px; }
    .ecc-admin-modal__content{
        background: rgba(2,2,2,.94);
        border: 1px solid rgba(199,167,90,.22);
        border-radius: 18px;
        box-shadow: 0 40px 140px rgba(0,0,0,.75);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        color: var(--ecc-gold-400);
    }
    .ecc-admin-modal__badge{
        width: 64px; height: 64px;
        border-radius: 9999px;
        display:flex; align-items:center; justify-content:center;
        background: rgba(199,167,90,.08);
        border: 1px solid rgba(199,167,90,.28);
        box-shadow: 0 0 0 1px rgba(199,167,90,.08), 0 18px 60px rgba(199,167,90,.10);
    }
    .ecc-admin-modal__badge .material-symbols-outlined{
        font-size: 30px;
        color: var(--ecc-gold-400);
    }
    .ecc-admin-modal__title{
        color: var(--ecc-gold-400);
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .ecc-admin-modal__subtitle{
        color: rgba(199,167,90,.62);
        font-weight: 600;
        line-height: 1.55;
        max-width: 420px;
    }
    .ecc-admin-modal__gold{ color: var(--ecc-gold-400); }

    .ecc-admin-modal__meta{
        max-width: 440px;
        border-radius: 14px;
        border: 1px solid rgba(199,167,90,.18);
        background: rgba(199,167,90,.04);
        padding: 14px 14px;
        text-align: left;
    }
    .ecc-admin-modal__meta-row{
        display:flex; align-items:center; justify-content:space-between;
        gap: 10px;
        padding: 6px 4px;
    }
    .ecc-admin-modal__meta-label{
        font-size: 11px;
        letter-spacing: .18em;
        text-transform: uppercase;
        color: rgba(199,167,90,.55);
        font-weight: 800;
    }
    .ecc-admin-modal__meta-value{
        font-size: 13px;
        color: var(--ecc-text-primary);
        font-weight: 700;
        text-align: right;
        max-width: 280px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .ecc-admin-modal__btn{
        height: 54px;
        border-radius: 14px;
        border: 1px solid rgba(199,167,90,.22);
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
        transition: all 220ms ease;
    }
    .ecc-admin-modal__btn--primary{
        background: linear-gradient(90deg, var(--ecc-gold-400), var(--ecc-gold-500));
        color: #020202;
        border-color: rgba(199,167,90,.55);
    }
    .ecc-admin-modal__btn--primary:hover{
        filter: brightness(1.06);
        transform: translateY(-1px);
    }
    .ecc-admin-modal__btn--ghost{
        background: rgba(199,167,90,.03);
        color: rgba(199,167,90,.80);
    }
    .ecc-admin-modal__btn--ghost:hover{
        background: rgba(199,167,90,.08);
        color: var(--ecc-gold-400);
    }

    /* Ensure modal sits above backdrop and is clickable */
    .ecc-admin-modal.modal { z-index: 2005 !important; }
    .modal-backdrop { z-index: 2000 !important; }

    /* Make sure the modal content receives pointer events */
    .ecc-admin-modal .modal-content { pointer-events: auto; }

    .ecc-field, .ecc-field * { -webkit-tap-highlight-color: transparent; }
</style>
@endpush
