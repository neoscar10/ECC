<div class="ecc-step ecc-bg min-vh-100 position-relative overflow-hidden">
  <div class="ecc-bg-layer"></div>
  <div class="ecc-bg-grad"></div>
  <div class="ecc-bg-glow"></div>

  <header class="ecc-topbar position-sticky top-0 z-3">
    <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
      <button type="button" class="ecc-icon-btn" onclick="window.location.href='{{ route('membership.application.step3') }}'">
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
        <div class="ecc-step-kicker">STEP IV OF VIII</div>

        <div class="d-flex align-items-center gap-1">
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--active"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
        </div>
      </div>

      <div class="mb-4">
        <h1 class="ecc-h1-lite mb-2">Your Cricket Profile</h1>
        <p class="ecc-sub mb-0">
          Which formats define your passion? This helps us curate collections specifically for you.
        </p>
      </div>

      @if($errorMessage)
        <div class="alert alert-danger py-2 small mb-3">{{ $errorMessage }}</div>
      @endif

      <form wire:submit.prevent="submit" class="d-flex flex-column gap-4">

        {{-- Preferred Formats --}}
        <div>
          <div class="ecc-section-title mb-3">Preferred Formats</div>

          <div class="row g-3">
            @foreach($this->formatOptions() as $opt)
              @php $selected = in_array($opt['key'], $preferred_formats, true); @endphp
              <div class="col-6">
                <button type="button"
                        wire:click="toggleFormat('{{ $opt['key'] }}')"
                        class="ecc-card {{ $selected ? 'is-selected' : '' }}"
                        style="--bg:url('{{ $opt['image'] }}');">
                  <span class="ecc-card__overlay"></span>

                  <span class="ecc-card__check {{ $selected ? 'is-on' : '' }}">
                    <span class="material-symbols-outlined">check</span>
                  </span>

                  <div class="ecc-card__body">
                    <div class="ecc-card__title">{{ $opt['title'] }}</div>
                    <div class="ecc-card__sub">{{ $opt['sub'] }}</div>
                  </div>
                </button>
              </div>
            @endforeach
          </div>

          @error('preferred_formats') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </div>

        {{-- Eras --}}
        <div>
          <h2 class="ecc-era-q mb-3">Which eras resonate with you?</h2>

          <div class="d-flex flex-wrap gap-2">
            @foreach($this->eraOptions() as $era)
              @php $on = in_array($era['key'], $eras, true); @endphp
              <button type="button"
                      wire:click="toggleEra('{{ $era['key'] }}')"
                      class="ecc-chip {{ $on ? 'is-on' : '' }}">
                <span>{{ $era['label'] }}</span>
                @if($on)
                  <span class="material-symbols-outlined ecc-chip__ic">check</span>
                @endif
              </button>
            @endforeach
          </div>
        </div>

        {{-- Actions (not sticky; matches Step 1 fix) --}}
        <div class="pt-2 d-flex flex-column gap-3">
          <button type="submit"
                  class="btn ecc-continue w-100 d-flex align-items-center justify-content-center gap-2"
                  wire:loading.attr="disabled"
                  wire:target="submit">
            {{-- Normal State --}}
            <span wire:loading.remove wire:target="submit" class="d-flex align-items-center gap-2">
              <span>Continue</span>
              <span class="material-symbols-outlined">arrow_forward</span>
            </span>

            {{-- Loading State: Hidden by default, shown ONLY when loading submit --}}
            <span wire:loading wire:target="submit" class="align-items-center gap-2" style="display: none;">
              Saving...
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </span>
          </button>

          <button type="button"
                  class="btn ecc-later w-100"
                  wire:click="chooseLater"
                  wire:loading.attr="disabled"
                  wire:target="chooseLater">
            I’ll choose later
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
  .ecc-max{ max-width: 520px; }
  @media (min-width: 992px){ .ecc-max{ max-width: 620px; } }

  /* pattern */
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
    position:absolute;
    top:0; left:50%;
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
    border:0; background: transparent;
    color: rgba(199, 167, 90,.85);
    display:flex; align-items:center; justify-content:center;
  }
  .ecc-icon-btn:hover{ background: var(--ecc-text-primary); }
  .text-ecc{ color: rgba(199, 167, 90,.95); }
  .ecc-topbar-title{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: var(--ecc-text-primary);
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
  .ecc-dot{
    height: 4px; width: 10px;
    border-radius: 9999px;
    background: var(--ecc-border);
  }
  .ecc-dot--done{
    width: 18px;
    background: rgba(199, 167, 90,.55);
  }
  .ecc-dot--active{
    width: 34px;
    background: var(--ecc-primary);
    box-shadow: 0 0 10px rgba(199, 167, 90,.30);
  }

  .ecc-h1-lite{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 34px;
    font-weight: 300;
    letter-spacing: -.02em;
    line-height: 1.15;
    color: var(--ecc-text-primary) !important;
  }

  .ecc-sub{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: var(--ecc-text-muted) !important;
    font-size: 16px;
    line-height: 1.6;
  }

  .ecc-section-title{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: rgba(199, 167, 90,.90);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .22em;
    text-transform: uppercase;
    padding-left: 2px;
  }

  /* Cards */
  .ecc-card{
    width: 100%;
    border: 1px solid var(--ecc-border);
    background: #221f10;
    border-radius: 14px;
    overflow: hidden;
    padding: 0;
    text-align: left;
    position: relative;
    aspect-ratio: 4/3;
    cursor: pointer;
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    background-image: var(--bg);
    background-size: cover;
    background-position: center;
    color: var(--ecc-text-primary);
  }
  .ecc-card__overlay{
    position:absolute; inset:0;
    background: linear-gradient(to top, rgba(0,0,0,.88), rgba(0,0,0,.20), rgba(0,0,0,0));
    pointer-events:none;
  }
  .ecc-card__body{
    position:absolute; left:0; right:0; bottom:0;
    padding: 14px;
    z-index: 2;
  }
  .ecc-card__title{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 4px;
    color: #ffffff !important;
    text-shadow: 0 1px 4px rgba(0,0,0,0.9);
  }
  .ecc-card__sub{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 10px;
    letter-spacing: .20em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.85) !important;
    text-shadow: 0 1px 3px rgba(0,0,0,0.9);
  }

  .ecc-card__check{
    position:absolute;
    top: 10px; right: 10px;
    width: 26px; height: 26px;
    border-radius: 9999px;
    display:flex; align-items:center; justify-content:center;
    border: 1px solid var(--ecc-text-primary);
    background: rgba(0,0,0,.25);
    color: transparent;
    z-index: 3;
  }
  .ecc-card__check .material-symbols-outlined{ font-size: 18px; }
  .ecc-card.is-selected{
    border-color: rgba(199, 167, 90,.95);
    box-shadow: 0 0 0 2px rgba(199, 167, 90,.55), 0 10px 30px rgba(199, 167, 90,.10);
  }
  .ecc-card.is-selected .ecc-card__check{
    background: var(--ecc-primary);
    border-color: var(--ecc-primary);
    color: #111;
  }

  /* Chips */
  .ecc-era-q{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 18px;
    font-weight: 600;
  }
  .ecc-chip{
    border-radius: 9999px;
    border: 1px solid var(--ecc-border);
    background: rgba(34,31,16,.55);
    color: var(--ecc-text-secondary);
    padding: 10px 14px;
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all .18s ease;
  }
  .ecc-chip:hover{
    border-color: rgba(199, 167, 90,.40);
    color: var(--ecc-text-primary);
  }
  .ecc-chip.is-on{
    background: var(--ecc-primary);
    color: #221f10;
    box-shadow: 0 0 15px rgba(199, 167, 90,.22);
    border-color: transparent;
    font-weight: 700;
  }
  .ecc-chip__ic{ font-size: 16px; }

  /* Buttons */
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

  .ecc-later{
    background: transparent !important;
    border: 0 !important;
    color: var(--ecc-text-primary) !important;
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 14px !important;
    padding: 10px 0 !important;
  }
  .ecc-later:hover{ color: var(--ecc-text-primary) !important; }

  .ecc-err{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: rgba(255,120,120,.95);
    font-size: 12px;
    font-weight: 600;
  }
</style>
@endpush
