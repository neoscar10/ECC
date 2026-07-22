<div class="ecc-step ecc-bg min-vh-100 position-relative d-flex flex-column align-items-center justify-content-center px-4 overflow-hidden">
  <div class="ecc-bg-layer"></div>
  <div class="ecc-bg-grad"></div>
  <div class="ecc-bg-glow"></div>

  <header class="ecc-topbar position-absolute top-0 start-0 end-0 z-3">
    <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
      <button type="button" class="ecc-icon-btn" onclick="window.location.href='{{ route('membership.application.step1') }}'">
        <span class="material-symbols-outlined">arrow_back_ios_new</span>
      </button>

      <div class="flex-grow-1 text-center">
        <div class="ecc-topbar-title">MEMBER ACCESS</div>
      </div>

      <div style="width:40px;"></div>
    </div>
  </header>

  <main class="position-relative z-2 text-center w-100 ecc-wizard-container" style="max-width: 480px;">
    
    {{-- Stepper (Added) --}}
    <div class="d-flex align-items-center justify-content-between mb-5 px-2">
      <div class="ecc-step-kicker">STEP II OF VIII</div>

      <div class="d-flex align-items-center gap-1">
        <div class="ecc-dot ecc-dot--done"></div>
        <div class="ecc-dot ecc-dot--active"></div>
        <div class="ecc-dot"></div>
        <div class="ecc-dot"></div>
        <div class="ecc-dot"></div>
        <div class="ecc-dot"></div>
        <div class="ecc-dot"></div>
        <div class="ecc-dot"></div>
      </div>
    </div>

    @if($devOtp)
      <div class="card border-0 mb-4 text-start shadow-sm" style="background: rgba(199,167,90,0.15); border-left: 4px solid var(--ecc-primary) !important; border-radius: 12px;">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-uppercase fw-bold small text-ecc mb-1" style="letter-spacing: 0.05em; font-family: 'Noto Sans', sans-serif;">Developer Mode OTP</div>
              <div class="h3 mb-0 ecc-text-primary font-monospace fw-bold" style="letter-spacing: 0.1em;">{{ $devOtp }}</div>
            </div>
            <button type="button" 
                    class="btn btn-sm btn-outline-light border-0 px-2 py-1 d-flex align-items-center gap-1"
                    style="background: rgba(255,255,255,0.05); color: #fff; font-size: 12px; font-family: 'Noto Sans', sans-serif; border-radius: 8px;"
                    onclick="navigator.clipboard.writeText('{{ $devOtp }}').then(() => { this.innerHTML = '<span class=\'material-symbols-outlined\' style=\'font-size:16px;\'>check</span><span>Copied!</span>'; setTimeout(() => this.innerHTML = '<span class=\'material-symbols-outlined\' style=\'font-size:16px;\'>content_copy</span><span>Copy</span>', 2000) })">
              <span class="material-symbols-outlined" style="font-size: 16px;">content_copy</span>
              <span>Copy</span>
            </button>
          </div>
        </div>
      </div>
    @endif

    <div class="ecc-shield mb-4 mx-auto">
      <span class="material-symbols-outlined">shield</span>
    </div>

    @if(!$showOtpInput && $otpMethod === 'direct_message')
      <h1 class="ecc-h1 mb-2">Request Verification Code</h1>
      <p class="ecc-sub mb-4">
        To receive your OTP, please send us a message on WhatsApp.
      </p>
    @else
      <h1 class="ecc-h1 mb-2">Verification Code</h1>
      <p class="ecc-sub mb-4">
        We sent a 6-digit code to<br>
        <span class="ecc-text-primary fw-bold">{{ $maskedPhone }}</span>
      </p>
    @endif

    @if($errorMessage)
      <div class="alert alert-danger py-2 small mb-4 border-0" style="background: rgba(255,107,107,0.1); color: #ff6b6b;">
        {{ $errorMessage }}
      </div>
    @endif

    @if(!$showOtpInput && $otpMethod === 'direct_message')
      <div class="mb-4">
        <a href="https://wa.me/{{ ltrim($whatsappNumber, '+') }}?text={{ urlencode('Request OTP') }}" 
           target="_blank" 
           rel="noopener noreferrer" 
           wire:click="openWhatsApp"
           class="btn ecc-verify-btn w-100 d-flex align-items-center justify-content-center gap-2 mb-3"
           style="color: #000000 !important; text-decoration: none;">
          <span class="material-symbols-outlined" style="color: #000000 !important;">chat</span>
          <span style="color: #000000 !important;">Open WhatsApp to Request OTP</span>
        </a>
        <p class="small text-muted mb-0">After sending the message, you will receive the OTP in WhatsApp. Copy and paste it here.</p>
      </div>
      
      <div class="ecc-timer-container mb-4">
        @if($hasExpiry)
          <div id="otp-timer-block" class="{{ $resendRemaining > 0 ? '' : 'd-none' }}">
            <span class="ecc-timer-text">Resend code in <span id="timer-display">00:00</span></span>
          </div>
        @endif
      </div>
    @else
      <form wire:submit.prevent="verify" class="mb-4">
        <div class="d-flex justify-content-between gap-2 mb-4" 
             id="otp-inputs" 
             x-data="{ 
                init() {
                    this.$nextTick(() => {
                        this.$refs.input0.focus();
                    });
                },
                handleInput(e, index) {
                    const val = e.target.value;
                    if (!/^\d$/.test(val)) {
                        e.target.value = '';
                        return;
                    }
                    if (val && index < 5) {
                        this.$refs['input' + (index + 1)].focus();
                    }
                },
                handleKeydown(e, index) {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        this.$refs['input' + (index - 1)].focus();
                    }
                },
                handlePaste(e) {
                    const data = e.clipboardData.getData('text').trim();
                    if (/^\d{6}$/.test(data)) {
                        data.split('').forEach((digit, i) => {
                            if (this.$refs['input' + i]) {
                                this.$refs['input' + i].value = digit;
                                @this.set('digits.' + i, digit);
                            }
                        });
                        this.$refs.input5.focus();
                        e.preventDefault();
                    }
                }
             }">
          @foreach($digits as $index => $digit)
            <input type="text" 
                   maxlength="1" 
                   class="form-control ecc-otp-input" 
                   wire:model.defer="digits.{{ $index }}"
                   x-ref="input{{ $index }}"
                   x-on:input="handleInput($event, {{ $index }})"
                   x-on:keydown="handleKeydown($event, {{ $index }})"
                   x-on:paste="if({{ $index }} === 0) handlePaste($event)"
                   inputmode="numeric"
                   pattern="[0-9]*"
                   autocomplete="one-time-code">
          @endforeach
        </div>

        <div class="ecc-timer-container mb-4">
          @if($hasExpiry)
            <div id="otp-timer-block" class="{{ $resendRemaining > 0 ? '' : 'd-none' }}">
              <span class="ecc-timer-text">Resend code in <span id="timer-display">00:00</span></span>
            </div>
            <div id="otp-resend-block" class="{{ $resendRemaining > 0 ? 'd-none' : '' }}">
              <button type="button" class="btn btn-link text-ecc p-0 text-decoration-none fw-bold" wire:click="resend">Resend Code</button>
            </div>
          @else
            <button type="button" class="btn btn-link text-ecc p-0 text-decoration-none fw-bold" wire:click="resend">Resend Code</button>
          @endif
        </div>

        <button type="submit" 
                class="btn ecc-verify-btn w-100 d-flex align-items-center justify-content-center gap-2"
                wire:loading.attr="disabled"
                wire:target="verify">
          <span wire:loading.remove wire:target="verify">Verify & Enter Lounge</span>
          <span class="material-symbols-outlined" wire:loading.remove wire:target="verify">arrow_forward</span>
          
          <span wire:loading wire:target="verify" class="align-items-center gap-2" style="display: none;">
            Verifying...
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          </span>
        </button>
      </form>
    @endif

    <div class="pt-2">
      <p class="ecc-sub-small mb-1">
        Having trouble logging in? <a href="#" class="text-ecc text-decoration-none">Contact Support</a>
      </p>
      <p class="ecc-sub-small mt-3">
        <a href="#" wire:click.prevent="logout" class="text-ecc text-decoration-none" style="opacity: 0.8; font-size: 0.9em;">
          Log out / Use different account
        </a>
      </p>
    </div>

  </main>
</div>

@push('styles')
<style>
  :root{
    --ecc-bg: var(--ecc-bg-page);
    --ecc-surface: var(--ecc-bg-surface);
    --ecc-border: var(--ecc-border);
    --ecc-border-soft: rgba(255, 255, 255, 0.065);
    --ecc-text-primary: var(--ecc-text-primary);
    --ecc-text-secondary: var(--ecc-text-secondary);
    --ecc-text-muted: var(--ecc-text-muted);
    --ecc-text-subtle: var(--ecc-text-subtle);
    --ecc-primary: #C7A75A;
    --ecc-primary-dark: #9C7D35;
  }

  .ecc-bg{
    --ecc-bg: var(--ecc-bg-page);
    --ecc-surface: var(--ecc-bg-surface);
    --ecc-border: var(--ecc-border);
    --ecc-border-soft: rgba(255, 255, 255, 0.065);
    --ecc-text-primary: var(--ecc-text-primary);
    --ecc-text-secondary: var(--ecc-text-secondary);
    --ecc-text-muted: var(--ecc-text-muted);
    --ecc-text-subtle: var(--ecc-text-subtle);
    --ecc-primary: #C7A75A;
    --ecc-primary-dark: #9C7D35;

    background: var(--ecc-bg) !important;
    color: var(--ecc-text-primary) !important;
    font-family: "Newsreader", serif;
  }
  
  .ecc-bg-layer{
    position:absolute; inset:0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4AF37' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity:.55; pointer-events:none;
  }
  .ecc-bg-grad{
    position:absolute; inset:0;
    background: radial-gradient(circle at center, rgba(199, 167, 90,0.05) 0%, rgba(2,2,2,1) 70%);
    pointer-events:none;
  }
  .ecc-bg-glow{
    position:absolute; top:50%; left:50%;
    transform:translate(-50%, -50%);
    width:600px; height:600px;
    background: rgba(199, 167, 90,0.03);
    filter: blur(120px);
    border-radius:9999px;
    pointer-events:none;
  }

  /* Desktop Spacing Fix */
  @media (min-width: 992px) {
    .ecc-wizard-container {
      padding-top: 60px;
    }
  }

  .ecc-topbar{
    border-bottom: 1px solid var(--ecc-border-soft);
  }
  .ecc-topbar-title{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: var(--ecc-text-muted);
    font-size: 11px;
    letter-spacing: .25em;
    font-weight: 700;
    text-transform: uppercase;
  }
  .ecc-icon-btn{
    width:40px; height:40px; border-radius:9999px;
    border:0; background: transparent;
    color: var(--ecc-primary);
    display:flex; align-items:center; justify-content:center;
  }

  /* Stepper Styles */
  .ecc-step-kicker{
    color: var(--ecc-primary);
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .20em;
    text-transform: uppercase;
  }
  .ecc-dot{ height: 4px; width: 10px; border-radius: 9999px; background: var(--ecc-border); }
  .ecc-dot--done{ width: 14px; background: rgba(199, 167, 90,.35); }
  .ecc-dot--active{ width: 34px; background: var(--ecc-primary); box-shadow: 0 0 10px rgba(199, 167, 90,.30); }

  .ecc-shield{
    width: 64px; height: 64px;
    background: rgba(199, 167, 90,0.1);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    color: var(--ecc-primary);
  }
  .ecc-shield .material-symbols-outlined{ font-size: 36px; font-variation-settings: 'FILL' 1; }

  .ecc-h1{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: var(--ecc-text-primary) !important;
  }
  .ecc-sub{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: var(--ecc-text-muted) !important;
    font-size: 15px;
    line-height: 1.6;
  }

  .ecc-otp-input{
    width: 15%; height: 64px;
    background: var(--ecc-surface) !important;
    border: 1px solid var(--ecc-border) !important;
    border-radius: 12px !important;
    color: var(--ecc-text-primary) !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    text-align: center !important;
    padding: 0 !important;
    transition: all 0.2s;
  }
  .ecc-otp-input:focus{
    border-color: var(--ecc-primary) !important;
    background: rgba(199, 167, 90,0.05) !important;
    box-shadow: 0 0 15px rgba(199, 167, 90,0.1) !important;
    color: var(--ecc-text-primary) !important;
    outline: none !important;
  }

  /* Autofill styling overrides */
  .ecc-otp-input:-webkit-autofill,
  .ecc-otp-input:-webkit-autofill:hover,
  .ecc-otp-input:-webkit-autofill:focus,
  .ecc-otp-input:-webkit-autofill:active {
    -webkit-text-fill-color: var(--ecc-text-primary) !important;
    -webkit-box-shadow: 0 0 0px 1000px var(--ecc-surface) inset !important;
    transition: background-color 5000s ease-in-out 0s;
  }

  .ecc-timer-text{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 14px;
    color: var(--ecc-text-primary);
  }
  .text-ecc{ color: var(--ecc-primary); }

  .ecc-verify-btn{
    background: var(--ecc-primary) !important;
    color: #000 !important;
    font-weight: 800 !important;
    font-size: 16px !important;
    padding: 16px !important;
    border-radius: 14px !important;
    border: 0 !important;
    box-shadow: 0 10px 25px rgba(199, 167, 90,0.2) !important;
    font-family: "Noto Sans", system-ui, sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.02em;
  }

  .ecc-sub-small{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 13px;
    color: var(--ecc-text-muted) !important;
  }
</style>
@endpush

<script>
    // Timer reset event listener — fired by Livewire after a successful resend
    window.addEventListener('ecc-otp-countdown-reset', event => {
        if (window.otpTimer) window.otpTimer.start(event.detail.seconds);
    });

    function eccInitOtpTimer(initialSeconds) {
        const timerDisplay = document.getElementById('timer-display');
        const timerBlock   = document.getElementById('otp-timer-block');
        const resendBlock  = document.getElementById('otp-resend-block');

        if (!timerDisplay || !timerBlock || !resendBlock) return;

        let interval = null;

        window.otpTimer = {
            start(seconds) {
                if (interval) clearInterval(interval);
                if (seconds <= 0) {
                    this.showResend();
                    return;
                }
                this.showTimer();
                this.update(seconds);
                interval = setInterval(() => {
                    seconds--;
                    this.update(seconds);
                    if (seconds <= 0) {
                        clearInterval(interval);
                        this.showResend();
                    }
                }, 1000);
            },
            update(seconds) {
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                timerDisplay.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            },
            showTimer() {
                timerBlock.classList.remove('d-none');
                resendBlock.classList.add('d-none');
            },
            showResend() {
                timerBlock.classList.add('d-none');
                resendBlock.classList.remove('d-none');
                @this.set('resendRemaining', 0);
            }
        };

        window.otpTimer.start(initialSeconds);
    }

    // Start once the DOM is ready — handles initial page load
    document.addEventListener('DOMContentLoaded', () => {
        eccInitOtpTimer(@js($resendRemaining));
    });

    // Re-init after any Livewire navigation (full-page Livewire navigate)
    document.addEventListener('livewire:navigated', () => {
        eccInitOtpTimer(@js($resendRemaining));
    });
</script>
