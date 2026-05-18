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
          <div class="d-flex flex-column gap-4">
              <!-- Saved Cards -->
              <div>
                  <div class="ecc-pay-title mb-3">SAVED CARDS</div>
                  <div class="d-flex flex-column gap-3">
                      @forelse($savedPaymentMethods as $method)
                          <label class="ecc-payment-card mb-0 {{ (string) $selectedPaymentMethod === (string) $method->id ? 'is-selected' : '' }}">
                              <div class="d-flex align-items-center gap-3 flex-grow-1">
                                  <div class="ecc-card-brand-box">
                                      {{ $method->brand_label }}
                                  </div>
                                  <div class="flex-grow-1">
                                      <div class="d-flex flex-wrap align-items-center gap-2">
                                          <div class="fw-bold text-white">
                                              {{ $method->display_name }}
                                          </div>
                                          @if($method->is_default)
                                              <span class="ecc-badge-gold subtle">DEFAULT</span>
                                          @endif
                                      </div>
                                      <div class="ecc-mini mt-1 opacity-75 text-white" style="font-size: 10px;">
                                          Expires {{ $method->expiry_label }}
                                      </div>
                                  </div>
                              </div>
                              <div class="ms-3">
                                  <input class="form-check-input ecc-radio" type="radio" wire:model.live="selectedPaymentMethod" value="{{ $method->id }}">
                              </div>
                          </label>
                      @empty
                          <div class="ecc-empty-panel py-4">
                              <div class="fw-bold mb-1">No saved cards</div>
                          </div>
                      @endforelse
                  </div>
              </div>

              <!-- Wallets -->
              @if(!empty($walletOptions) && count($walletOptions))
                  <div>
                      <div class="ecc-pay-title mb-3 mt-2">DIGITAL WALLETS</div>
                      <div class="d-flex flex-column gap-3">
                          @foreach($walletOptions as $wallet)
                              <label class="ecc-payment-card mb-0 {{ (string) $selectedPaymentMethod === (string) $wallet['value'] ? 'is-selected' : '' }}">
                                  <div class="d-flex align-items-center gap-3 flex-grow-1">
                                      <div class="ecc-wallet-box">
                                          <i class="{{ $wallet['icon'] }} fs-4"></i>
                                      </div>
                                      <div class="fw-bold text-white">{{ $wallet['label'] }}</div>
                                  </div>
                                  <div class="ms-3">
                                      <input class="form-check-input ecc-radio" type="radio" wire:model.live="selectedPaymentMethod" value="{{ $wallet['value'] }}">
                                  </div>
                              </label>
                          @endforeach
                      </div>
                  </div>
              @endif

              <!-- Add Card CTA -->
              <button type="button" class="ecc-add-card-panel" wire:click="handleAddPaymentMethod">
                  <div class="d-flex align-items-center gap-3">
                      <div class="ecc-add-icon">
                          <i class="mdi mdi-plus text-secondary"></i>
                      </div>
                      <div class="text-start">
                          <div class="fw-bold text-white">Add New Card</div>
                          <div class="ecc-mini opacity-75 mt-1" style="font-size: 10px; color: white;">Save securely for future premium acquisitions</div>
                      </div>
                  </div>
                  <i class="mdi mdi-chevron-right text-secondary"></i>
              </button>
          </div>
      </section>

      <form wire:submit.prevent="submit" class="d-flex flex-column gap-3 mt-4">
        @if($errorMessage)
          <div class="alert alert-danger py-2 small border-0 rounded-3" style="background: rgba(220, 53, 69, 0.1); color: #ff8e99;">{{ $errorMessage }}</div>
        @endif
        @if (session()->has('info'))
            <div class="alert alert-info py-2 small border-0 rounded-3" style="background: rgba(13, 110, 253, 0.1); color: #8ec5ff;">{{ session('info') }}</div>
        @endif

        <div class="pt-2">
          <button type="submit"
                  class="btn ecc-paybtn w-100 d-flex align-items-center justify-content-center gap-2"
                  wire:loading.attr="disabled"
                  wire:target="submit">
            
            <span wire:loading.remove wire:target="submit" class="ecc-btn-load-wrapper">
                <span>CONFIRM & PAY {{ $amountFormatted }}</span>
                <span class="material-symbols-outlined">arrow_forward</span>
            </span>

            <span wire:loading wire:target="submit" class="ecc-btn-load-wrapper">
              <span>PROCESSING PAYMENT...</span>
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </span>
          </button>

          <div class="ecc-sec mt-3 d-flex align-items-center justify-content-center gap-2">
            <span class="material-symbols-outlined">lock</span>
            <span>PAYMENTS ARE SECURE AND ENCRYPTED</span>
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

  .ecc-pay-title{ color:rgba(199, 167, 90,.95); font-weight:800; font-size:12px; letter-spacing:.15em; text-transform:uppercase; padding-left:2px; }

  /* New Payment Method Styles ported from checkout */
  .ecc-payment-card {
      padding: 1.25rem 1.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      cursor: pointer;
      margin-bottom: 0.75rem;
      background: rgba(10,10,10,.85);
      border: 1px solid rgba(199, 167, 90,.30);
      border-radius: 14px;
      transition: .25s ease;
  }
  .ecc-payment-card:hover {
      border-color: rgba(199, 167, 90,.60);
      box-shadow: 0 8px 24px rgba(0,0,0,.3);
  }
  .ecc-payment-card.is-selected {
      border-color: var(--ecc-primary);
      background: rgba(199, 167, 90, .10);
      box-shadow: 0 0 0 1px rgba(199, 167, 90,.2), 0 12px 30px rgba(0,0,0,.4);
  }
  .ecc-card-brand-box, .ecc-wallet-box {
      width: 56px;
      height: 38px;
      border-radius: .55rem;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: rgba(255,255,255,.9);
      color: #111;
      font-size: .85rem;
      font-weight: 900;
  }
  .ecc-wallet-box { background: rgba(255,255,255,.1); color: white; border: 1px solid rgba(255,255,255,.2); }
  .ecc-radio {
      width: 1.15rem;
      height: 1.15rem;
      border: 2px solid rgba(199, 167, 90,.55);
      box-shadow: none !important;
      background-color: transparent;
      border-radius: 50% !important;
  }
  .ecc-radio:checked {
      background-color: var(--ecc-primary);
      border-color: var(--ecc-primary);
  }
  .ecc-badge-gold {
      display: inline-flex;
      align-items: center;
      padding: .35rem .6rem;
      border-radius: 999px;
      background: rgba(199, 167, 90,.15);
      color: var(--ecc-primary);
      font-size: .68rem;
      font-weight: 800;
      letter-spacing: .12em;
      text-transform: uppercase;
  }
  .ecc-badge-gold.subtle { font-size: .55rem; padding: .2rem .45rem; }
  .ecc-add-card-panel {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-style: dashed;
      background: transparent;
      padding: 1.25rem;
      border: 1px dashed rgba(199, 167, 90,.40);
      border-radius: 14px;
      margin-top: 0.5rem;
      transition: .25s ease;
  }
  .ecc-add-card-panel:hover {
      border-color: var(--ecc-primary);
      background: rgba(199, 167, 90,.05);
  }
  .ecc-add-icon {
      width: 42px;
      height: 42px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(255,255,255,.1);
      font-size: 1.15rem;
  }

  .ecc-paybtn{
    background:var(--ecc-primary) !important; border:0 !important; color:#111 !important;
    font-size:16px !important; font-weight:900 !important; padding:16px !important; border-radius:14px !important;
    box-shadow:0 0 15px rgba(199, 167, 90,.30) !important;
    letter-spacing: 0.1em;
  }
  .ecc-paybtn:hover{ background:#eac855 !important; }
  .ecc-sec{ color:rgba(199, 167, 90,.80); font-size:10px; font-weight:800; letter-spacing: 0.1em; text-transform: uppercase; opacity:.9; }
  .ecc-sec .material-symbols-outlined{ font-size:16px; }

  [wire\:loading] { display: none; }
</style>
@endpush
