<div class="ecc-step ecc-bg min-vh-100 position-relative overflow-hidden">
  <div class="ecc-bg-layer"></div>
  <div class="ecc-bg-grad"></div>
  <div class="ecc-bg-glow"></div>

  <header class="ecc-topbar position-sticky top-0 z-3">
    <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
      <button type="button" class="ecc-icon-btn" onclick="window.location.href='{{ route('membership.application.step4') }}'">
        <span class="material-symbols-outlined">arrow_back_ios_new</span>
      </button>

      <div class="flex-grow-1 text-center">
        <div class="d-inline-flex align-items-center gap-2 opacity-90">
          <img src="{{ asset('ecc_logo_dark.png') }}" style="width: 64px; height: 64px; object-fit: contain;" alt="ECC Logo">
          <div class="ecc-topbar-title">THE APPLICATION</div>
        </div>
      </div>

      <button type="button" class="ecc-icon-btn" aria-label="Help">
        <span class="material-symbols-outlined">help</span>
      </button>
    </div>
  </header>

  <main class="container-fluid px-4 pt-4 pb-5 position-relative z-2">
    <div class="mx-auto ecc-max">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="ecc-step-kicker">STEP V OF VIII</div>

        <div class="d-flex align-items-center gap-1">
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--active"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
        </div>
      </div>

      <div class="mb-4">
        <h1 class="ecc-h1-lite mb-2">Collector Intent</h1>
        <p class="ecc-sub mb-0">Help us curate your private showroom experience by understanding your intent.</p>
      </div>

      @if($errorMessage)
        <div class="alert alert-danger py-2 small mb-3">{{ $errorMessage }}</div>
      @endif

      <form wire:submit.prevent="submit" class="d-flex flex-column gap-5">

        {{-- HISTORY --}}
        <section>
          <div class="ecc-section-title mb-3">History</div>
          <div class="ecc-q mb-3">Have you previously acquired match-worn or signed memorabilia?</div>

          <div class="ecc-seg">
            <button type="button"
                    class="ecc-seg__btn {{ $has_acquired_memorabilia_before ? 'is-on' : '' }}"
                    wire:click="$set('has_acquired_memorabilia_before', true)">
              Yes, I have
            </button>

            <button type="button"
                    class="ecc-seg__btn {{ !$has_acquired_memorabilia_before ? 'is-on' : '' }}"
                    wire:click="$set('has_acquired_memorabilia_before', false)">
              No, I haven't
            </button>
          </div>

          @error('has_acquired_memorabilia_before') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </section>

        {{-- FOCUS --}}
        <section>
          <div class="ecc-section-title mb-3">Focus</div>
          <div class="ecc-q mb-3">What interests you most?</div>

          <div class="row g-3">
            <div class="col-4">
              <button type="button"
                      class="ecc-focus {{ $focus === 'LEGACY' ? 'is-on' : '' }}"
                      wire:click="$set('focus','LEGACY')">
                <span class="material-symbols-outlined ecc-focus__ic">history_edu</span>
                <span class="ecc-focus__tx">Legacy</span>
              </button>
            </div>

            <div class="col-4">
              <button type="button"
                      class="ecc-focus {{ $focus === 'RARITY' ? 'is-on' : '' }}"
                      wire:click="$set('focus','RARITY')">
                <span class="material-symbols-outlined ecc-focus__ic">diamond</span>
                <span class="ecc-focus__tx">Rarity</span>
              </button>
            </div>

            <div class="col-4">
              <button type="button"
                      class="ecc-focus {{ $focus === 'VALUE' ? 'is-on' : '' }}"
                      wire:click="$set('focus','VALUE')">
                <span class="material-symbols-outlined ecc-focus__ic">trending_up</span>
                <span class="ecc-focus__tx">Value</span>
              </button>
            </div>
          </div>

          @error('focus') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </section>

        {{-- HORIZON --}}
        <section>
          <div class="d-flex align-items-end justify-content-between mb-3">
            <div>
              <div class="ecc-section-title mb-1">Horizon</div>
              <div class="ecc-q mb-0">Investment Timeline</div>
            </div>
            <div class="ecc-hlabel">{{ $this->horizonLabel }}</div>
          </div>

          <div class="ecc-range">
            <input type="range" min="1" max="100"
                   wire:model.live="horizon"
                   class="ecc-range__input">
            <div class="ecc-range__ends d-flex justify-content-between">
              <span>Short Term</span>
              <span>Generational</span>
            </div>
          </div>

          @error('investment_horizon') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </section>

        {{-- CTA (NOT STICKY) --}}
        <div class="pt-2">
          <button type="submit"
                  class="btn ecc-continue w-100 d-flex align-items-center justify-content-center gap-2"
                  wire:loading.attr="disabled"
                  wire:target="submit">
            
            {{-- Normal State --}}
            <span wire:loading.remove wire:target="submit" class="d-flex align-items-center gap-2">
              <span>Continue Application</span>
              <span class="material-symbols-outlined">arrow_forward</span>
            </span>

            {{-- Loading State: Hidden by default --}}
            <span wire:loading wire:target="submit" class="align-items-center gap-2" style="display: none;">
              Saving...
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
    font-family:"Newsreader", serif;
  }
  .ecc-max{ max-width: 520px; }
  @media (min-width: 992px){ .ecc-max{ max-width: 620px; } }

  /* same background system as step1/2 */
  .ecc-bg-layer{
    position:absolute; inset:0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4AF37' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity:.55; pointer-events:none;
  }
  .ecc-bg-grad{
    position:absolute; inset:0;
    background: var(--ecc-bg-grad, linear-gradient(to bottom, transparent, rgba(2,2,2,.80), rgba(2,2,2,1)));
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
    background: var(--ecc-bg-nav-transparent, rgba(2,2,2,.80));
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--ecc-border-soft);
  }
  .ecc-icon-btn{
    width:40px; height:40px; border-radius:9999px;
    border:0; background:transparent;
    color: rgba(199, 167, 90,.85);
    display:flex; align-items:center; justify-content:center;
  }
  .ecc-icon-btn:hover{ background: var(--ecc-text-primary); }
  .text-ecc{ color: rgba(199, 167, 90,.95); }
  .ecc-topbar-title{
    font-family:"Noto Sans", system-ui, sans-serif;
    color: var(--ecc-text-primary);
    font-size: 12px;
    letter-spacing:.22em;
    font-weight:700;
  }

  .ecc-step-kicker{
    color: rgba(199, 167, 90,.95);
    font-family:"Noto Sans", system-ui, sans-serif;
    font-size: 11px;
    font-weight: 800;
    letter-spacing:.20em;
    text-transform: uppercase;
  }
  .ecc-dot{ height:4px; width:10px; border-radius:9999px; background: var(--ecc-border); }
  .ecc-dot--done{ width:18px; background: rgba(199, 167, 90,.55); }
  .ecc-dot--active{ width:34px; background: var(--ecc-primary); box-shadow: 0 0 10px rgba(199, 167, 90,.30); }

  .ecc-h1-lite{
    font-family:"Noto Sans", system-ui, sans-serif;
    font-size: 34px;
    font-weight: 700;
    letter-spacing:-.02em;
    line-height:1.15;
    color: var(--ecc-text-primary) !important;
  }
  .ecc-sub{
    font-family:"Noto Sans", system-ui, sans-serif;
    color: var(--ecc-text-muted) !important;
    font-size: 16px;
    line-height:1.6;
  }

  .ecc-section-title{
    font-family:"Noto Sans", system-ui, sans-serif;
    color: rgba(199, 167, 90,.90);
    font-size: 12px;
    font-weight: 700;
    letter-spacing:.22em;
    text-transform: uppercase;
    padding-left:2px;
  }
  .ecc-q{
    font-family:"Noto Sans", system-ui, sans-serif;
    color: var(--ecc-text-primary);
    font-size: 18px;
    font-weight: 500;
  }

  /* segmented */
  .ecc-seg{
    display:flex;
    padding: 6px;
    border-radius: 14px;
    background: var(--ecc-bg-input);
    border: 1px solid var(--ecc-border);
    gap: 6px;
  }
  .ecc-seg__btn{
    flex: 1;
    padding: 14px 10px;
    border-radius: 12px;
    border: 0;
    background: transparent;
    color: var(--ecc-text-muted);
    font-family:"Noto Sans", system-ui, sans-serif;
    font-size: 14px;
    font-weight: 700;
    transition: all .2s ease;
  }
  .ecc-seg__btn.is-on{
    background: var(--ecc-primary);
    color: #111;
    box-shadow: 0 0 15px rgba(199, 167, 90,.25);
  }

  /* focus cards */
  .ecc-focus{
    width:100%;
    aspect-ratio: 3/4;
    border-radius: 14px;
    border: 1px solid var(--ecc-border);
    background: var(--ecc-bg-input);
    color: var(--ecc-text-muted);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap: 12px;
    transition: all .2s ease;
  }
  .ecc-focus__ic{ font-size: 34px; color: var(--ecc-text-subtle); }
  .ecc-focus__tx{
    font-family:"Noto Sans", system-ui, sans-serif;
    font-size: 13px;
    letter-spacing:.06em;
    font-weight: 600;
  }
  .ecc-focus:hover{ border-color: rgba(199, 167, 90,.45); }
  .ecc-focus.is-on{
    background: rgba(199, 167, 90,.10);
    border-color: var(--ecc-primary);
    box-shadow: 0 0 20px rgba(199, 167, 90,.12);
    color: var(--ecc-primary);
  }
  .ecc-focus.is-on .ecc-focus__ic{ color: var(--ecc-primary); }

  /* horizon */
  .ecc-hlabel{
    font-family:"Noto Sans", system-ui, sans-serif;
    color: var(--ecc-primary);
    font-size: 18px;
    font-weight: 800;
    letter-spacing:.02em;
  }
  .ecc-range__input{
    width: 100%;
    height: 4px;
    appearance: none;
    background: rgba(199, 167, 90,.18);
    border-radius: 9999px;
    outline: none;
  }
  .ecc-range__input::-webkit-slider-thumb{
    -webkit-appearance:none;
    width: 22px; height: 22px;
    border-radius: 9999px;
    background: var(--ecc-primary);
    box-shadow: 0 0 0 4px #050505;
    cursor: pointer;
  }
  .ecc-range__input::-moz-range-thumb{
    width: 22px; height: 22px;
    border-radius: 9999px;
    background: var(--ecc-primary);
    border: 0;
    box-shadow: 0 0 0 4px #050505;
    cursor: pointer;
  }
  .ecc-range__ends{
    margin-top: 8px;
    font-family:"Noto Sans", system-ui, sans-serif;
    font-size: 11px;
    letter-spacing:.20em;
    text-transform: uppercase;
    color: var(--ecc-text-subtle);
  }

  /* CTA */
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
    font-family:"Newsreader", serif;
  }
  .ecc-continue:hover{ background: var(--ecc-primary-dark) !important; }

  .ecc-err{
    font-family:"Noto Sans", system-ui, sans-serif;
    color: rgba(255,120,120,.95);
    font-size: 12px;
    font-weight: 600;
  }
</style>
@endpush
