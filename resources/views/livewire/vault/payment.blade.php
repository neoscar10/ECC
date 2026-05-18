<div class="ecc-pay ecc-bg min-vh-100 position-relative overflow-hidden">
  <div class="ecc-bg-layer"></div>
  <div class="ecc-bg-grad"></div>
  <div class="ecc-bg-glow"></div>

  <header class="ecc-topbar position-sticky top-0 z-3">
    <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
      <button type="button" class="ecc-icon-btn" onclick="(history.length>1)?history.back():window.location.href='/vault'">
        <span class="material-symbols-outlined">arrow_back_ios_new</span>
      </button>

      <div class="flex-grow-1 text-center">
        <div class="d-inline-flex align-items-center gap-2 opacity-90">
          <span class="material-symbols-outlined text-ecc">local_shipping</span>
          <div class="ecc-topbar-title">VAULT DELIVERY PAYMENT</div>
        </div>
      </div>

      <div style="width:40px;"></div>
    </div>

    <div class="container-fluid px-4 pb-3">
      <div class="d-flex align-items-center justify-content-between mx-auto ecc-max">
        <div class="ecc-step-kicker">SECURE CHECKOUT</div>
        <div class="d-flex align-items-center gap-1">
          <div class="ecc-dot ecc-dot--active"></div>
        </div>
      </div>
    </div>
  </header>

  <main class="container-fluid px-4 pt-4 pb-4 position-relative z-2">
    <div class="mx-auto ecc-max">

      {{-- Request summary --}}
      <section class="ecc-tier-sum mb-4">
        <div class="d-flex align-items-stretch justify-content-between gap-3">
          <div class="flex-grow-1">
            <div class="ecc-mini text-uppercase">PHYSICAL DELIVERY FEE</div>
            <div class="ecc-tier-title">{{ $itemTitle }}</div>
            
            @if($itemRef)
                <div class="fs-12 text-white opacity-50 mb-2">{{ $itemRef }}</div>
            @endif

            <div class="mt-2 d-flex flex-column gap-1">
                <div class="d-flex justify-content-between align-items-center opacity-75">
                  <div class="ecc-mini" style="font-size:10px;">Selected Courier</div>
                  <div class="text-white" style="font-size:12px; font-weight:600;">{{ $courierName }}</div>
                </div>
                <hr class="my-1" style="border-color: rgba(199, 167, 90,0.3);">
                <div class="d-flex justify-content-between align-items-center mt-1">
                  <div class="ecc-mini">Total Payable</div>
                  <div class="ecc-amount">{{ $amountFormatted }}</div>
                </div>
            </div>
          </div>

          <div class="ecc-tier-img" aria-label="Asset Vault Image"></div>
        </div>
      </section>

      {{-- Payment method --}}
      <section class="mb-4">
        <div class="ecc-pay-title mb-2">Payment Method</div>
        <div class="ecc-method">
          <label class="ecc-method__opt {{ $method==='card' ? 'is-on' : '' }}">
            <input type="radio" class="d-none" wire:model.live="method" value="card">
            <span class="d-inline-flex align-items-center gap-2">
              <span class="material-symbols-outlined">credit_card</span> Card
            </span>
          </label>
          <label class="ecc-method__opt {{ $method==='upi' ? 'is-on' : '' }}">
            <input type="radio" class="d-none" wire:model.live="method" value="upi">
            <span class="d-inline-flex align-items-center gap-2">
              <span class="material-symbols-outlined">qr_code_scanner</span> UPI
            </span>
          </label>
        </div>
      </section>

      {{-- Card form --}}
      <form wire:submit.prevent="submit" class="d-flex flex-column gap-3">
        @if($errorMessage)
          <div class="alert alert-danger py-2 small">{{ $errorMessage }}</div>
        @endif

        <div class="ecc-field">
          <div class="ecc-lab mb-2">Card Number</div>
          <div class="position-relative">
            <input type="text" wire:model.defer="card_number" class="form-control ecc-inp"
                   placeholder="0000 0000 0000 0000" @if($method!=='card') disabled @endif>
            <span class="material-symbols-outlined ecc-ic">credit_card</span>
          </div>
          @error('card_number') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </div>

        <div class="row g-3">
          <div class="col-6">
            <div class="ecc-field">
              <div class="ecc-lab mb-2">Expiry Date</div>
              <div class="position-relative">
                <input type="text" wire:model.defer="expiry" class="form-control ecc-inp"
                       placeholder="MM/YY" @if($method!=='card') disabled @endif>
                <span class="material-symbols-outlined ecc-ic">calendar_month</span>
              </div>
              @error('expiry') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
            </div>
          </div>
          <div class="col-6">
            <div class="ecc-field">
              <div class="ecc-lab mb-2">CVV</div>
              <div class="position-relative">
                <input type="password" wire:model.defer="cvv" class="form-control ecc-inp"
                       placeholder="123" @if($method!=='card') disabled @endif>
                <span class="material-symbols-outlined ecc-ic">lock</span>
              </div>
              @error('cvv') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
            </div>
          </div>
        </div>

        <div class="ecc-field">
          <div class="ecc-lab mb-2">Cardholder Name</div>
          <div class="position-relative">
            <input type="text" wire:model.defer="cardholder_name" class="form-control ecc-inp"
                   placeholder="John Doe" @if($method!=='card') disabled @endif>
            <span class="material-symbols-outlined ecc-ic">person</span>
          </div>
          @error('cardholder_name') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex align-items-center gap-3 mt-1">
          <div class="form-check">
            <input class="form-check-input ecc-check" type="checkbox" id="saveCard" wire:model.defer="save_card" @if($method!=='card') disabled @endif>
          </div>
          <label class="ecc-save" for="saveCard">Save this card for future payments</label>
        </div>

        <div class="pt-3">
          <button type="submit"
                  class="btn ecc-paybtn w-100 d-flex align-items-center justify-content-center gap-2"
                  wire:loading.attr="disabled"
                  wire:target="submit">
            
            <span wire:loading.remove wire:target="submit" class="ecc-btn-load-wrapper">
                <span>Confirm & Pay {{ $amountFormatted }}</span>
                <span class="material-symbols-outlined">arrow_forward</span>
            </span>

            <span wire:loading wire:target="submit" class="ecc-btn-load-wrapper">
              <span>Processing Payment...</span>
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </span>
          </button>

          <div class="ecc-sec mt-3 d-flex align-items-center justify-content-center gap-2">
            <span class="material-symbols-outlined">lock</span>
            <span>Payments are secure and encrypted</span>
          </div>
        </div>

      </form>

    </div>
  </main>
</div>

@push('styles')
<style>
  :root{
    --ecc-primary:var(--ecc-primary); --ecc-primary-dark:var(--ecc-gold-600);
    --ecc-bg:#020202; --ecc-surface:#0a0a0a; --ecc-border:rgba(199, 167, 90,.30);
  }
  .ecc-bg{ background:var(--ecc-bg); color: var(--ecc-text-primary); font-family:"Work Sans","Noto Sans",system-ui,sans-serif; }
  .ecc-max{ max-width:520px; }
  @media(min-width:992px){ .ecc-max{ max-width:620px; } }

  .ecc-btn-load-wrapper { display: inline-flex; align-items: center; gap: 8px; }
  [wire\:loading].ecc-btn-load-wrapper { display: none !important; }

  .ecc-bg-layer{
    position:absolute; inset:0;
    background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4AF37' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity:.55; pointer-events:none;
  }
  .ecc-bg-grad{ position:absolute; inset:0; background:linear-gradient(to bottom, transparent, rgba(2,2,2,.80), rgba(2,2,2,1)); pointer-events:none;}
  .ecc-bg-glow{ position:absolute; top:0; left:50%; transform:translateX(-50%); width:520px; height:320px; background:rgba(199, 167, 90,.10); filter:blur(110px); border-radius:9999px; pointer-events:none;}

  .ecc-topbar{ background:rgba(2,2,2,.80); backdrop-filter:blur(10px); border-bottom:1px solid var(--ecc-text-primary); }
  .ecc-icon-btn{ width:40px; height:40px; border-radius:9999px; border:0; background:transparent; color:rgba(199, 167, 90,.85); display:flex; align-items:center; justify-content:center; }
  .ecc-icon-btn:hover{ background:var(--ecc-text-primary); }
  .text-ecc{ color:rgba(199, 167, 90,.95); }
  .ecc-topbar-title{ font-family:"Noto Sans",system-ui,sans-serif; color:var(--ecc-text-primary); font-size:12px; letter-spacing:.22em; font-weight:700; }

  .ecc-step-kicker{ color:rgba(199, 167, 90,.95); font-family:"Noto Sans",system-ui,sans-serif; font-size:11px; font-weight:800; letter-spacing:.20em; text-transform:uppercase; }
  .ecc-dot{ height:4px; width:10px; border-radius:9999px; background:rgba(199, 167, 90,.35); }
  .ecc-dot--done{ width:18px; background:rgba(199, 167, 90,.45); }
  .ecc-dot--active{ width:34px; background:var(--ecc-primary); box-shadow:0 0 8px rgba(199, 167, 90,.60); }

  .ecc-tier-sum{
    border:1px solid rgba(199, 167, 90,.55);
    background:rgba(10,10,10,.85);
    border-radius:16px;
    padding:16px;
    box-shadow:0 12px 40px rgba(0,0,0,.55);
  }
  .ecc-mini{ font-size:11px; letter-spacing:.18em; text-transform:uppercase; color:rgba(199, 167, 90,.85); font-weight:700; }
  .ecc-tier-title{ font-size:18px; font-weight:800; color:rgba(199, 167, 90,.95); margin-top:2px; }
  .ecc-amount{ font-size:28px; font-weight:900; color:rgba(199, 167, 90,.95); }
  .ecc-tier-img{
    width:96px; min-width:96px; border-radius:12px;
    border:1px solid rgba(199, 167, 90,.30);
    background-image:url("https://placehold.co/400x400/17130b/d4af37?text=Delivery");
    background-size:cover; background-position:center;
    position:relative; overflow:hidden;
  }
  .ecc-tier-img:after{ content:""; position:absolute; inset:0; background:rgba(0,0,0,.20); }

  .ecc-pay-title{ color:rgba(199, 167, 90,.95); font-weight:700; font-size:16px; padding-left:2px; }
  .ecc-method{
    display:flex; border-radius:14px; padding:6px;
    border:1px solid rgba(199, 167, 90,.55);
    background:rgba(10,10,10,.85);
    gap:6px;
  }
  .ecc-method__opt{
    flex:1; border-radius:12px; padding:10px 12px;
    display:flex; justify-content:center; align-items:center;
    color:rgba(199, 167, 90,.85);
    cursor:pointer; font-weight:800;
    transition:all .18s ease;
  }
  .ecc-method__opt.is-on{
    background:var(--ecc-primary);
    color:#111;
    box-shadow:0 6px 18px rgba(199, 167, 90,.18);
  }

  .ecc-lab{ color:rgba(199, 167, 90,.85); font-weight:700; font-size:14px; padding-left:2px; }
  .ecc-inp{
    height:56px;
    border-radius:14px;
    background:rgba(10,10,10,.90) !important;
    border:1px solid rgba(199, 167, 90,.55) !important;
    color:rgba(199, 167, 90,.95) !important;
    padding-left:48px !important;
    box-shadow:none !important;
  }
  .ecc-inp::placeholder{ color:rgba(199, 167, 90,.35); }
  .ecc-inp:focus{ border-color:var(--ecc-primary) !important; box-shadow:0 0 0 .15rem rgba(199, 167, 90,.15) !important; }
  .ecc-ic{ position:absolute; left:14px; top:50%; transform:translateY(-50%); color:rgba(199, 167, 90,.95); }
  .ecc-check{ background:rgba(10,10,10,.90) !important; border-color:rgba(199, 167, 90,.55) !important; }
  .ecc-check:checked{ background:var(--ecc-primary) !important; border-color:var(--ecc-primary) !important; }
  .ecc-save{ color:rgba(199, 167, 90,.85); font-size:14px; font-weight:600; }

  .ecc-paybtn{
    background:var(--ecc-primary) !important; border:0 !important; color:#111 !important;
    font-size:18px !important; font-weight:900 !important; padding:16px !important; border-radius:14px !important;
    box-shadow:0 0 15px rgba(199, 167, 90,.30) !important;
  }
  .ecc-paybtn:hover{ background:#eac855 !important; }
  .ecc-sec{ color:rgba(199, 167, 90,.80); font-size:12px; opacity:.9; }
  .ecc-sec .material-symbols-outlined{ font-size:16px; }

  .ecc-err{ font-family:"Noto Sans",system-ui,sans-serif; color:rgba(255,120,120,.95); font-size:12px; font-weight:600; }

  [wire:loading] { display: none; }
</style>
@endpush
