<div class="ecc-step ecc-bg min-vh-100 position-relative overflow-hidden">
  <div class="ecc-bg-layer"></div>
  <div class="ecc-bg-grad"></div>
  <div class="ecc-bg-glow"></div>

  <header class="ecc-topbar position-sticky top-0 z-3">
    <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
      <button type="button" class="ecc-icon-btn" onclick="window.location.href='/membership/apply-intro'">
        <span class="material-symbols-outlined">arrow_back_ios_new</span>
      </button>

      <div class="flex-grow-1 text-center">
        <div class="d-inline-flex align-items-center gap-2 opacity-90">
          <img src="{{ asset('ecc_logo_dark.png') }}" style="width: 64px; height: 64px; object-fit: contain;" alt="ECC Logo">
          <div class="ecc-topbar-title">THE APPLICATION</div>
        </div>
      </div>

      <div style="width:40px;"></div>
    </div>
  </header>

  <main class="container-fluid px-4 pt-4 pb-5 position-relative z-2">
    <div class="mx-auto ecc-max">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="ecc-step-kicker">STEP I OF VIII</div>

        <div class="d-flex align-items-center gap-1">
          <div class="ecc-dot ecc-dot--active"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
        </div>
      </div>

      <div class="mb-4">
        <h1 class="ecc-h1-lite mb-2">Create Account</h1>
        <p class="ecc-sub mb-0">Join the exclusive circle of cricket heritage enthusiasts.</p>
      </div>

      @if($errorMessage)
        <div class="alert alert-danger py-2 small mb-3">{{ $errorMessage }}</div>
      @endif

      <form wire:submit.prevent="submit" class="d-flex flex-column gap-3">
        <div>
          <label class="ecc-label">Full Name</label>
          <input type="text" wire:model="name" class="form-control ecc-input" placeholder="Enter your name">
          @error('name') <div class="ecc-err">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="ecc-label">Email Address</label>
          <input type="email" wire:model="email" class="form-control ecc-input" placeholder="email@example.com">
          @error('email') <div class="ecc-err">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="ecc-label">Phone Number</label>
          <input type="text" wire:model="phone" class="form-control ecc-input" placeholder="+1234567890">
          <div class="ecc-hint">Standard rates may apply for OTP verification.</div>
          @error('phone') <div class="ecc-err">{{ $message }}</div> @enderror
        </div>

        <div class="row g-3">
          <div class="col-6" x-data="{ show: false }">
            <label class="ecc-label">Password</label>
            <div class="position-relative">
              <input :type="show ? 'text' : 'password'" wire:model="password" class="form-control ecc-input pe-5">
              <button type="button" @click="show = !show" class="ecc-pass-toggle" style="color: #222;">
                <span class="material-symbols-outlined fs-20" x-text="show ? 'visibility_off' : 'visibility'"></span>
              </button>
            </div>
            @error('password') <div class="ecc-err">{{ $message }}</div> @enderror
          </div>
          <div class="col-6" x-data="{ show: false }">
            <label class="ecc-label">Confirm Password</label>
            <div class="position-relative">
              <input :type="show ? 'text' : 'password'" wire:model="password_confirmation" class="form-control ecc-input pe-5">
              <button type="button" @click="show = !show" class="ecc-pass-toggle">
                <span class="material-symbols-outlined fs-20" x-text="show ? 'visibility_off' : 'visibility'"></span>
              </button>
            </div>
          </div>
        </div>

        <div class="pt-4">
          <button type="submit"
                  class="btn ecc-continue w-100 d-flex align-items-center justify-content-center gap-2"
                  wire:loading.attr="disabled"
                  wire:target="submit">
            <span wire:loading.remove wire:target="submit">Continue</span>
            <span class="material-symbols-outlined" wire:loading.remove wire:target="submit">arrow_forward</span>

            <span wire:loading wire:target="submit" class="align-items-center gap-2" style="display: none;">
              Creating Account...
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </span>
          </button>
        </div>
      </form>

    </div>
  </main>
</div>

@push('styles')
<style>
  :root{
    --ecc-primary:var(--ecc-primary);
    --ecc-primary-dark:var(--ecc-gold-600);
    --ecc-bg:#020202;
    --ecc-surface:#181818;
    --ecc-border:#333333;
  }

  .ecc-bg{ background: var(--ecc-bg); color: #fff; font-family: "Newsreader", serif; }
  .ecc-max{ max-width: 520px; }
  @media (min-width: 992px){ .ecc-max{ max-width: 620px; } }

  .ecc-bg-layer{
    position:absolute; inset:0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4AF37' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity:.55; pointer-events:none;
  }
  .ecc-bg-grad{
    position:absolute; inset:0;
    background: linear-gradient(to bottom, transparent, rgba(2,2,2,.80), rgba(2,2,2,1));
    pointer-events:none;
  }
  .ecc-bg-glow{
    position:absolute; top:0; left:50%;
    transform:translateX(-50%);
    width:520px; height:320px;
    background: rgba(199, 167, 90,.10);
    filter: blur(110px);
    border-radius:9999px;
    pointer-events:none;
  }

  .ecc-topbar{
    background: rgba(2,2,2,.80);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,.05);
  }
  .ecc-icon-btn{
    width:40px; height:40px; border-radius:9999px;
    border:0; background: transparent;
    color: rgba(199, 167, 90,.85);
    display:flex; align-items:center; justify-content:center;
  }
  .ecc-icon-btn:hover{ background: rgba(255,255,255,.05); }
  .text-ecc{ color: rgba(199, 167, 90,.95); }
  .ecc-topbar-title{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: rgba(255,255,255,.90);
    font-size: 12px;
    letter-spacing: .22em;
    font-weight: 700;
  }

  .ecc-step-kicker{
    color: rgba(199, 167, 90,.95);
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .20em;
    text-transform: uppercase;
  }
  .ecc-dot{ height: 4px; width: 10px; border-radius: 9999px; background: var(--ecc-border); transition: all .3s; }
  .ecc-dot--active{ width: 34px; background: var(--ecc-primary); box-shadow: 0 0 10px rgba(199, 167, 90,.30); }

  .ecc-h1-lite{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 34px;
    font-weight: 300;
    letter-spacing: -.02em;
    line-height: 1.15;
  }
  .ecc-sub{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: rgba(255,255,255,.60);
    font-size: 16px;
    line-height: 1.6;
  }

  .ecc-label{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: rgba(255,255,255,.80);
    margin-bottom: 8px;
    margin-left: 14px;
  }
  .ecc-input{
    background: rgba(255,255,255,.05) !important;
    border: 1px solid rgba(255,255,255,.10) !important;
    border-radius: 12px !important;
    color: #fff !important;
    padding: 14px 18px !important;
    font-family: "Noto Sans", system-ui, sans-serif;
  }
  .ecc-input::placeholder{ color: rgba(255,255,255,.25) !important; }
  .ecc-input:focus{ border-color: var(--ecc-primary) !important; box-shadow: 0 0 0 1px var(--ecc-primary) !important; }

  .ecc-hint{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 11px;
    color: rgba(255,255,255,.35);
    margin-top: 6px;
    margin-left: 14px;
  }

  .ecc-err{
    color: #ff6b6b;
    font-size: 11px;
    margin-top: 5px;
    margin-left: 14px;
    font-family: "Noto Sans", system-ui, sans-serif;
  }

  .ecc-continue{
    background: var(--ecc-primary) !important;
    border: 0 !important;
    color: var(--ecc-bg) !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    padding: 16px !important;
    border-radius: 12px !important;
    box-shadow: 0 0 20px rgba(199, 167, 90,.25) !important;
    transition: all .2s ease;
    font-family: "Newsreader", serif;
  }
  .ecc-continue:hover{ background: var(--ecc-primary-dark) !important; }

  .ecc-pass-toggle{
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: 0;
    color: rgba(255,255,255,.30);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4px;
    transition: color .2s;
    z-index: 4;
  }
  .ecc-pass-toggle:hover{ color: var(--ecc-primary); }
</style>
@endpush
