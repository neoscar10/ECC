<div class="ecc-step ecc-bg min-vh-100 position-relative overflow-hidden">
  <div class="ecc-bg-layer"></div>
  <div class="ecc-bg-grad"></div>
  <div class="ecc-bg-glow"></div>

  <header class="ecc-topbar position-sticky top-0 z-3">
    <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
      <button type="button" class="ecc-icon-btn" onclick="(history.length>1)?history.back():window.location.href='{{ route('membership.application.step5') }}'">
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
        <div class="ecc-step-kicker">STEP VI OF VIII</div>
        <div class="d-flex align-items-center gap-1">
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--active"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
        </div>
      </div>

      <div class="mb-4">
        <h1 class="ecc-h1-lite mb-2">Select Membership Tier</h1>
        <p class="ecc-sub mb-0">Based on your profile, we recommend a tier — you can still choose any tier you prefer.</p>
      </div>

      {{-- Recommended --}}
      <div class="ecc-rec mb-4" @if(empty($recommendation)) style="display:none;" @endif>
        <div class="d-flex align-items-start justify-content-between gap-3">
          <div>
            <div class="ecc-section-title mb-2">Recommended for you</div>
            <div class="ecc-rec-tier">
              {{ $recommendation['tier_name'] ?? '—' }}
            </div>
            <ul class="ecc-rec-list mt-2 mb-0">
              @foreach(($recommendation['reasons'] ?? []) as $r)
                <li>{{ $r }}</li>
              @endforeach
            </ul>
          </div>
          <button type="button"
                  class="btn ecc-rec-btn"
                  @if(!empty($recommendation['tier_id'])) wire:click="$set('selectedTierId', {{ (int)($recommendation['tier_id']) }})" @endif>
            Use
          </button>
        </div>
      </div>

      {{-- Tier cards --}}
      <form wire:submit.prevent="submit" class="d-flex flex-column gap-3">
        @if($errorMessage)
          <div class="alert alert-danger py-2 small">{{ $errorMessage }}</div>
        @endif

        <div class="row g-3">
          @foreach($tiers as $t)
            @php $on = ((int)$selectedTierId === (int)$t['id']); @endphp
            <div class="col-12 col-md-6 d-flex">
              <button type="button"
                      class="ecc-tier-card h-100 {{ $on ? 'is-on' : '' }}"
                      wire:click="$set('selectedTierId', {{ (int)$t['id'] }})">
                <div class="d-flex align-items-start justify-content-between gap-3">
                  <div class="flex-grow-1">
                    <div class="ecc-tier-name">{{ $t['name'] }}</div>
                    <div class="ecc-tier-price">
                      {{ $t['price_formatted'] }}
                      <span class="ecc-tier-dur">/ {{ $t['duration_label'] }}</span>
                    </div>
                    <div class="ecc-tier-desc">{{ $t['short_desc'] }}</div>

                    <ul class="ecc-tier-benefits">
                      @forelse(($t['benefits_list'] ?? []) as $benefit)
                        <li class="ecc-benefit-li">
                          <span class="material-symbols-outlined">check_circle</span>
                          <span>{{ $benefit }}</span>
                        </li>
                      @empty
                        <li class="ecc-benefit-li opacity-50">
                          <span>No privileges configured</span>
                        </li>
                      @endforelse
                    </ul>
                    
                    @if(($t['benefits_count'] ?? 0) > 4)
                      <div class="ecc-benefit-more">+{{ $t['benefits_count'] - 4 }} More Benefits</div>
                    @endif

                    @if(!empty($t['perks']))
                      <div class="ecc-tier-perks">
                        @foreach($t['perks'] as $p)
                          <span class="ecc-perk">{{ $p }}</span>
                        @endforeach
                      </div>
                    @endif
                  </div>

                  <span class="ecc-tier-check {{ $on ? 'is-on' : '' }}">
                    <span class="material-symbols-outlined">check</span>
                  </span>
                </div>
              </button>
            </div>
          @endforeach
        </div>

        @error('selectedTierId') <div class="ecc-err mt-2">{{ $message }}</div> @enderror

        <div class="pt-3">
          <button type="submit"
                  class="btn ecc-continue w-100 d-flex align-items-center justify-content-center gap-2"
                  wire:loading.attr="disabled"
                  wire:target="submit">
            
            <span wire:loading.remove wire:target="submit" class="ecc-btn-load-wrapper">
                <span>Continue to Payment</span>
                <span class="material-symbols-outlined">arrow_forward</span>
            </span>

            <span wire:loading wire:target="submit" class="ecc-btn-load-wrapper">
              <span>Saving...</span>
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
    --ecc-bg:#020202; --ecc-surface:#181818; --ecc-border:#333333;
  }
  .ecc-bg{ background:var(--ecc-bg); color: var(--ecc-text-primary); font-family:"Newsreader", serif; }
  .ecc-max{ max-width:520px; }
  @media(min-width:992px){ .ecc-max{ max-width:820px; } }

  /* New flex wrapper for button content to avoid Bootstrap !important displays */
  .ecc-btn-load-wrapper { display: inline-flex; align-items: center; gap: 8px; }
  [wire\:loading].ecc-btn-load-wrapper { display: none !important; }
  /* When Livewire shows it, it will add style="display: inline-flex !important;" which will win */

  .ecc-bg-layer{
    position:absolute; inset:0;
    background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4AF37' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity:.55; pointer-events:none;
  }
  .ecc-bg-grad{ position:absolute; inset:0; background:linear-gradient(to bottom, transparent, rgba(2,2,2,.80), rgba(2,2,2,1)); pointer-events:none;}
  .ecc-bg-glow{ position:absolute; top:0; left:50%; transform:translateX(-50%); width:520px; height:320px; background:rgba(199, 167, 90,.10); filter:blur(110px); border-radius:9999px; pointer-events:none;}

  .ecc-topbar{ background:rgba(2,2,2,.80); backdrop-filter:blur(10px); border-bottom:1px solid var(--ecc-text-primary);}
  .ecc-icon-btn{ width:40px; height:40px; border-radius:9999px; border:0; background:transparent; color:rgba(199, 167, 90,.85); display:flex; align-items:center; justify-content:center;}
  .ecc-icon-btn:hover{ background:var(--ecc-text-primary); }
  .text-ecc{ color:rgba(199, 167, 90,.95); }
  .ecc-topbar-title{ font-family:"Noto Sans",system-ui,sans-serif; color:var(--ecc-text-primary); font-size:12px; letter-spacing:.22em; font-weight:700;}

  .ecc-step-kicker{ color:rgba(199, 167, 90,.95); font-family:"Noto Sans",system-ui,sans-serif; font-size:11px; font-weight:800; letter-spacing:.20em; text-transform:uppercase;}
  .ecc-dot{ height:4px; width:10px; border-radius:9999px; background:var(--ecc-border);}
  .ecc-dot--done{ width:18px; background:rgba(199, 167, 90,.55);}
  .ecc-dot--active{ width:34px; background:var(--ecc-primary); box-shadow:0 0 10px rgba(199, 167, 90,.30);}

  .ecc-h1-lite{ font-family:"Noto Sans",system-ui,sans-serif; font-size:34px; font-weight:700; letter-spacing:-.02em; line-height:1.15;}
  .ecc-sub{ font-family:"Noto Sans",system-ui,sans-serif; color:var(--ecc-text-primary); font-size:16px; line-height:1.6;}
  .ecc-section-title{ font-family:"Noto Sans",system-ui,sans-serif; color:rgba(199, 167, 90,.90); font-size:12px; font-weight:700; letter-spacing:.22em; text-transform:uppercase;}

  .ecc-rec{
    border:1px solid rgba(199, 167, 90,.25);
    background:rgba(199, 167, 90,.06);
    border-radius:16px;
    padding:16px;
  }
  .ecc-rec-tier{ font-family:"Noto Sans",system-ui,sans-serif; font-size:18px; font-weight:800; color:var(--ecc-primary); }
  .ecc-rec-list{ padding-left:18px; color:var(--ecc-text-primary); font-family:"Noto Sans",system-ui,sans-serif; font-size:13px; line-height:1.6;}
  .ecc-rec-btn{ border:1px solid rgba(199, 167, 90,.50) !important; color:var(--ecc-primary) !important; background:transparent !important; border-radius:12px !important; padding:10px 14px !important; font-weight:800; font-family:"Noto Sans",system-ui,sans-serif;}
  .ecc-rec-btn:hover{ background:rgba(199, 167, 90,.10) !important; }

  .ecc-tier-card{
    width:100%;
    border:1px solid var(--ecc-border);
    background:var(--ecc-surface);
    border-radius:16px;
    padding:16px;
    text-align:left;
    transition:all .18s ease;
  }
  .ecc-tier-card:hover{ border-color:rgba(199, 167, 90,.35); }
  .ecc-tier-card.is-on{
    border-color:rgba(199, 167, 90,.95);
    box-shadow:0 0 0 2px rgba(199, 167, 90,.35), 0 18px 50px rgba(199, 167, 90,.08);
    background:rgba(199, 167, 90,.06);
  }
  .ecc-tier-name{ font-family:"Noto Sans",system-ui,sans-serif; font-size:18px; font-weight:800; color:var(--ecc-text-primary); }
  .ecc-tier-price{ font-family:"Noto Sans",system-ui,sans-serif; font-size:20px; font-weight:900; color:var(--ecc-primary); margin-top:2px;}
  .ecc-tier-dur{ font-size:12px; font-weight:700; color:var(--ecc-text-primary); }
  .ecc-tier-desc{ margin-top:8px; color:var(--ecc-text-primary); font-family:"Noto Sans",system-ui,sans-serif; font-size:13px; line-height:1.5; }

  .ecc-tier-benefits{ margin-top:12px; padding:0; list-style:none; }
  .ecc-benefit-li{ display:flex; align-items:start; gap:8px; margin-bottom:4px; font-size:13px; color:var(--ecc-text-primary); font-family:"Noto Sans",system-ui,sans-serif; }
  .ecc-benefit-li .material-symbols-outlined{ font-size:16px; color:var(--ecc-primary); margin-top:2px; }
  .ecc-benefit-more{ font-size:11px; color:rgba(199, 167, 90,.85); font-weight:800; margin-left:24px; text-transform:uppercase; letter-spacing:.05em; }

  .ecc-tier-perks{ display:flex; flex-wrap:wrap; gap:8px; margin-top:14px;}
  .ecc-perk{ border:1px solid rgba(242,185,13,.18); color:rgba(242,185,13,.75); padding:6px 10px; border-radius:9999px; font-size:11px; letter-spacing:.06em; font-weight:800; font-family:"Noto Sans",system-ui,sans-serif; }

  .ecc-tier-check{
    width:28px; height:28px;
    border-radius:9999px;
    display:flex; align-items:center; justify-content:center;
    border:1px solid var(--ecc-border);
    background:rgba(0,0,0,.25);
    color:transparent;
  }
  .ecc-tier-check .material-symbols-outlined{ font-size:18px; }
  .ecc-tier-check.is-on{ background:var(--ecc-primary); border-color:var(--ecc-primary); color:#111; }

  .ecc-continue{
    background:var(--ecc-primary) !important; border:0 !important; color:var(--ecc-bg) !important;
    font-size:18px !important; font-weight:700 !important; padding:16px !important; border-radius:12px !important;
    box-shadow:0 0 20px rgba(199, 167, 90,.25) !important; font-family:"Newsreader",serif;
  }
  .ecc-continue:hover{ background:var(--ecc-primary-dark) !important; }

  .ecc-err{ font-family:"Noto Sans",system-ui,sans-serif; color:rgba(255,120,120,.95); font-size:12px; font-weight:600;}

  /* Prevent loading flashes */
  [wire:loading] { display: none; }
</style>
@endpush
