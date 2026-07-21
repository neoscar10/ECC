<div class="ecc-app-step ecc-bg min-vh-100 position-relative overflow-hidden">
  {{-- Background layers --}}
  <div class="ecc-bg-layer"></div>
  <div class="ecc-bg-grad"></div>
  <div class="ecc-bg-glow"></div>

  {{-- Header --}}
  <header class="ecc-topbar position-sticky top-0 z-3">
    <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
      <button type="button" class="ecc-icon-btn" onclick="window.location.href='{{ route('membership.application.step2') }}'">
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

  {{-- Body --}}
  <main class="container-fluid px-4 pt-4 pb-5 position-relative z-2">
    <div class="mx-auto ecc-max">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="ecc-step-kicker">STEP III OF VIII</div>

        <div class="d-flex align-items-center gap-1">
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--done"></div>
          <div class="ecc-dot ecc-dot--active"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
          <div class="ecc-dot"></div>
        </div>
      </div>

      <div class="mb-4">
        <h1 class="ecc-h1 mb-2">Personal Details</h1>
        <p class="ecc-sub mb-0">Please provide your particulars to begin the formal vetting process.</p>
      </div>

      @if($errorMessage)
        <div class="alert alert-danger py-2 small mb-3">{{ $errorMessage }}</div>
      @endif

      <form wire:submit.prevent="submit" class="d-flex flex-column gap-4">
        {{-- Full Name --}}
        <div class="ecc-field">
          <label class="ecc-label" for="full_name">Full Name</label>
          <div class="position-relative">
            <input id="full_name" type="text"
                   wire:model.defer="full_name"
                   class="form-control ecc-input @error('full_name') is-invalid @enderror"
                   placeholder="As per government ID"
                   autocomplete="name">
            <span class="material-symbols-outlined ecc-input-ic">badge</span>
          </div>
          @error('full_name') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </div>

        {{-- DOB --}}
        <div class="ecc-field" wire:ignore x-data="{
             initFlatpickr() {
                 flatpickr($refs.dobInput, {
                     dateFormat: 'Y-m-d',
                     altInput: true,
                     altFormat: 'd / m / Y',
                     maxDate: 'today',
                     disableMobile: true,
                     allowInput: true,
                     onChange: (selectedDates, dateStr) => {
                         $wire.set('date_of_birth', dateStr);
                     }
                 });
             }
        }" x-init="initFlatpickr()">
          <label class="ecc-label" for="date_of_birth">Date of Birth</label>
          <div class="position-relative">
            <input id="date_of_birth" type="text"
                   x-ref="dobInput"
                   wire:model.defer="date_of_birth"
                   class="form-control ecc-input @error('date_of_birth') is-invalid @enderror"
                   placeholder="DD / MM / YYYY">
            <span class="material-symbols-outlined ecc-input-ic" style="cursor: pointer;" @click="$refs.dobInput._flatpickr.open()">calendar_month</span>
          </div>
          @error('date_of_birth') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </div>

        {{-- Country --}}
        <div class="ecc-field">
          <label class="ecc-label" for="country">Country of Residence</label>
          <div class="position-relative">
            <select id="country" wire:model.defer="country" class="form-select ecc-input ecc-select @error('country') is-invalid @enderror">
              <option value="" disabled>Select Country</option>
              @foreach(config('ecc_countries', []) as $cn)
                <option value="{{ $cn }}">{{ $cn }}</option>
              @endforeach
            </select>
            <span class="material-symbols-outlined ecc-input-ic">expand_more</span>
          </div>
          @error('country') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </div>

        {{-- City --}}
        <div class="ecc-field">
          <label class="ecc-label" for="city">City</label>
          <div class="position-relative">
            <input id="city" type="text"
                   wire:model.defer="city"
                   class="form-control ecc-input @error('city') is-invalid @enderror"
                   placeholder="Primary City"
                   autocomplete="address-level2">
            <span class="material-symbols-outlined ecc-input-ic">location_city</span>
          </div>
          @error('city') <div class="ecc-err mt-2">{{ $message }}</div> @enderror
        </div>

        {{-- Continue (NOT sticky) --}}
        <div class="pt-2">
          <button type="submit"
                  class="btn ecc-continue w-100 d-flex align-items-center justify-content-center gap-2"
                  wire:loading.attr="disabled"
                  wire:target="submit">
            
            {{-- Normal State: Shown when NOT loading submit --}}
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
    font-family: "Newsreader", serif;
  }
  .ecc-max{ max-width: 520px; }
  @media (min-width: 992px){
    .ecc-max{ max-width: 620px; }
  }

  /* pattern (from reference) */
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
    position:absolute;
    top:0; left:50%;
    transform:translateX(-50%);
    width:500px; height:300px;
    background: rgba(199, 167, 90,.10);
    filter: blur(100px);
    border-radius:9999px;
    pointer-events:none;
  }

  .ecc-topbar{
    background: rgba(2,2,2,.80);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--ecc-border-soft);
  }
  .ecc-icon-btn{
    width:40px; height:40px; border-radius:9999px;
    border:0; background: transparent;
    color: rgba(199, 167, 90,.85);
    display:flex; align-items:center; justify-content:center;
    transition: background .2s ease;
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
  .ecc-dot--done{ height: 4px; width: 14px; border-radius: 9999px; background: rgba(199, 167, 90,.35); }
  .ecc-dot--active{
    width: 34px;
    background: var(--ecc-primary);
    box-shadow: 0 0 10px rgba(199, 167, 90,.30);
  }

  .ecc-h1{
    font-size: 44px;
    font-style: italic;
    font-weight: 500;
    letter-spacing: -.02em;
    color: var(--ecc-text-primary) !important;
  }
  .ecc-sub{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: var(--ecc-text-muted) !important;
    font-size: 18px;
    line-height: 1.6;
    font-weight: 300;
  }

  .ecc-label{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: rgba(199, 167, 90,.90);
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    padding-left: 2px;
    letter-spacing: .02em;
  }

  .ecc-input{
    background: var(--ecc-surface) !important;
    border: 0 !important;
    border-bottom: 1px solid var(--ecc-border) !important;
    color: #ffffff !important;
    padding: 14px 44px 14px 16px !important;
    border-radius: 12px 12px 0 0 !important;
    font-size: 18px !important;
    font-family: "Noto Sans", system-ui, sans-serif;
    box-shadow: none !important;
  }
  .ecc-input::placeholder{ color: var(--ecc-text-subtle) !important; }
  .ecc-input:focus{
    border-bottom-color: var(--ecc-primary) !important;
    color: #ffffff !important;
    outline: none;
  }

  /* Autofill styling overrides */
  .ecc-input:-webkit-autofill,
  .ecc-input:-webkit-autofill:hover,
  .ecc-input:-webkit-autofill:focus,
  .ecc-input:-webkit-autofill:active {
    -webkit-text-fill-color: #ffffff !important;
    -webkit-box-shadow: 0 0 0px 1000px var(--ecc-surface) inset !important;
    transition: background-color 5000s ease-in-out 0s;
  }
  .ecc-select{ appearance: none; }

  .ecc-input-ic{
    position:absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ecc-text-primary);
    transition: color .2s ease;
    pointer-events:none;
  }
  .ecc-field:focus-within .ecc-input-ic{ color: var(--ecc-primary); }

  .ecc-err{
    font-family: "Noto Sans", system-ui, sans-serif;
    color: rgba(255,120,120,.95);
    font-size: 12px;
    font-weight: 600;
  }

  .ecc-continue{
    margin-top: 10px;
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

  /* Flatpickr Custom Theme */
  .flatpickr-calendar {
    background: #181818 !important;
    border: 1px solid #333 !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
    font-family: "Noto Sans", sans-serif !important;
  }
  .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange,
  .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange,
  .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus,
  .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover,
  .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay,
  .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
    background: var(--ecc-primary) !important;
    border-color: var(--ecc-primary) !important;
    color: #020202 !important;
    font-weight: 700 !important;
  }
  .flatpickr-day { color: #fff !important; }
  .flatpickr-day:hover { background: rgba(199, 167, 90,0.2) !important; }
  .flatpickr-months .flatpickr-month, .flatpickr-current-month .flatpickr-monthDropdown-months,
  .flatpickr-current-month input.cur-year, .flatpickr-weekday {
    color: #fff !important;
    fill: #fff !important;
  }
  .flatpickr-monthDropdown-months {
    background: #181818 !important;
  }
  .numInputWrapper span.arrowUp:after {
    border-bottom-color: var(--ecc-text-primary) !important;
  }
  .numInputWrapper span.arrowDown:after {
    border-top-color: var(--ecc-text-primary) !important;
  }
  .flatpickr-months .flatpickr-prev-month, .flatpickr-months .flatpickr-next-month {
    fill: var(--ecc-primary) !important;
  }
  .flatpickr-day.flatpickr-disabled, .flatpickr-day.flatpickr-disabled:hover {
    color: var(--ecc-text-primary) !important;
  }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endpush
