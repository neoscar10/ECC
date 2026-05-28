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
          <div class="input-group position-relative" x-data="{
              open: false,
              search: '',
              selected: { name: 'India', code: '+91', flag: '🇮🇳' },
              countries: [
                  { name: 'India', code: '+91', flag: '🇮🇳' },
                  { name: 'United Kingdom', code: '+44', flag: '🇬🇧' },
                  { name: 'United States', code: '+1', flag: '🇺🇸' },
                  { name: 'Canada', code: '+1', flag: '🇨🇦' },
                  { name: 'Australia', code: '+61', flag: '🇦🇺' },
                  { name: 'United Arab Emirates', code: '+971', flag: '🇦🇪' },
                  { name: 'Singapore', code: '+65', flag: '🇸🇬' },
                  { name: 'South Africa', code: '+27', flag: '🇿🇦' },
                  { name: 'New Zealand', code: '+64', flag: '🇳🇿' },
                  { name: 'Nigeria', code: '+234', flag: '🇳🇬' },
                  { name: 'Pakistan', code: '+92', flag: '🇵🇰' },
                  { name: 'Bangladesh', code: '+880', flag: '🇧🇩' },
                  { name: 'Sri Lanka', code: '+94', flag: '🇱🇰' },
                  { name: 'Ireland', code: '+353', flag: '🇮🇪' },
                  { name: 'Germany', code: '+49', flag: '🇩🇪' },
                  { name: 'France', code: '+33', flag: '🇫🇷' },
                  { name: 'Italy', code: '+39', flag: '🇮🇹' },
                  { name: 'Spain', code: '+34', flag: '🇪🇸' },
                  { name: 'Netherlands', code: '+31', flag: '🇳🇱' },
                  { name: 'Switzerland', code: '+41', flag: '🇨🇭' },
                  { name: 'Sweden', code: '+46', flag: '🇸🇪' },
                  { name: 'Norway', code: '+47', flag: '🇳🇴' },
                  { name: 'Denmark', code: '+45', flag: '🇩🇰' },
                  { name: 'Belgium', code: '+32', flag: '🇧🇪' },
                  { name: 'Austria', code: '+43', flag: '🇦🇹' },
                  { name: 'Portugal', code: '+351', flag: '🇵🇹' },
                  { name: 'Greece', code: '+30', flag: '🇬🇷' },
                  { name: 'Japan', code: '+81', flag: '🇯🇵' },
                  { name: 'South Korea', code: '+82', flag: '🇰🇷' },
                  { name: 'China', code: '+86', flag: '🇨🇳' },
                  { name: 'Hong Kong', code: '+852', flag: '🇭🇰' },
                  { name: 'Malaysia', code: '+60', flag: '🇲🇾' },
                  { name: 'Indonesia', code: '+62', flag: '🇮🇩' },
                  { name: 'Philippines', code: '+63', flag: '🇵🇭' },
                  { name: 'Thailand', code: '+66', flag: '🇹🇭' },
                  { name: 'Vietnam', code: '+84', flag: '🇻🇳' },
                  { name: 'Kenya', code: '+254', flag: '🇰🇪' },
                  { name: 'Ghana', code: '+233', flag: '🇬🇭' },
                  { name: 'Saudi Arabia', code: '+966', flag: '🇸🇦' },
                  { name: 'Qatar', code: '+974', flag: '🇶🇦' },
                  { name: 'Kuwait', code: '+965', flag: '🇰🇼' },
                  { name: 'Oman', code: '+968', flag: '🇴🇲' },
                  { name: 'Bahrain', code: '+973', flag: '🇧🇭' },
                  { name: 'Turkey', code: '+90', flag: '🇹🇷' },
                  { name: 'Brazil', code: '+55', flag: '🇧🇷' },
                  { name: 'Argentina', code: '+54', flag: '🇦🇷' },
                  { name: 'Mexico', code: '+52', flag: '🇲🇽' },
                  { name: 'Egypt', code: '+20', flag: '🇪🇬' },
                  { name: 'Israel', code: '+972', flag: '🇮🇱' },
                  { name: 'Zimbabwe', code: '+263', flag: '🇿🇼' },
                  { name: 'Jamaica', code: '+1876', flag: '🇯🇲' },
                  { name: 'Nepal', code: '+977', flag: '🇳🇵' },
                  { name: 'Afghanistan', code: '+93', flag: '🇦🇫' },
                  { name: 'Albania', code: '+355', flag: '🇦🇱' },
                  { name: 'Algeria', code: '+213', flag: '🇩🇿' },
                  { name: 'Andorra', code: '+376', flag: '🇦🇩' },
                  { name: 'Angola', code: '+244', flag: '🇦🇴' },
                  { name: 'Armenia', code: '+374', flag: '🇦🇲' },
                  { name: 'Azerbaijan', code: '+994', flag: '🇦🇿' },
                  { name: 'Bahamas', code: '+1242', flag: '🇧🇸' },
                  { name: 'Barbados', code: '+1246', flag: '🇧🇧' },
                  { name: 'Belarus', code: '+375', flag: '🇧🇾' },
                  { name: 'Bolivia', code: '+591', flag: '🇧🇴' },
                  { name: 'Bosnia and Herzegovina', code: '+387', flag: '🇧🇦' },
                  { name: 'Botswana', code: '+267', flag: '🇧🇼' },
                  { name: 'Bulgaria', code: '+359', flag: '🇧🇬' },
                  { name: 'Cambodia', code: '+855', flag: '🇰🇭' },
                  { name: 'Cameroon', code: '+237', flag: '🇨🇲' },
                  { name: 'Chile', code: '+56', flag: '🇨🇱' },
                  { name: 'Colombia', code: '+57', flag: '🇨🇴' },
                  { name: 'Costa Rica', code: '+506', flag: '🇨🇷' },
                  { name: 'Croatia', code: '+385', flag: '🇭🇷' },
                  { name: 'Cuba', code: '+53', flag: '🇨🇺' },
                  { name: 'Cyprus', code: '+357', flag: '🇨🇾' },
                  { name: 'Czech Republic', code: '+420', flag: '🇨🇿' },
                  { name: 'Ecuador', code: '+593', flag: '🇪🇨' },
                  { name: 'El Salvador', code: '+503', flag: '🇸🇻' },
                  { name: 'Estonia', code: '+372', flag: '🇪🇪' },
                  { name: 'Ethiopia', code: '+251', flag: '🇪🇹' },
                  { name: 'Fiji', code: '+679', flag: '🇫🇯' },
                  { name: 'Finland', code: '+358', flag: '🇫🇮' },
                  { name: 'Georgia', code: '+995', flag: '🇬🇪' },
                  { name: 'Guatemala', code: '+502', flag: '🇬🇹' },
                  { name: 'Honduras', code: '+504', flag: '🇭🇳' },
                  { name: 'Hungary', code: '+36', flag: '🇭🇺' },
                  { name: 'Iceland', code: '+354', flag: '🇮🇸' },
                  { name: 'Iran', code: '+98', flag: '🇮🇷' },
                  { name: 'Iraq', code: '+964', flag: '🇮🇶' },
                  { name: 'Jordan', code: '+962', flag: '🇯🇴' },
                  { name: 'Kazakhstan', code: '+7', flag: '🇰🇿' },
                  { name: 'Latvia', code: '+371', flag: '🇱🇻' },
                  { name: 'Lebanon', code: '+961', flag: '🇱🇧' },
                  { name: 'Libya', code: '+218', flag: '🇱🇾' },
                  { name: 'Liechtenstein', code: '+423', flag: '🇱🇮' },
                  { name: 'Lithuania', code: '+370', flag: '🇱🇹' },
                  { name: 'Luxembourg', code: '+352', flag: '🇱🇺' },
                  { name: 'Macao', code: '+853', flag: '🇲🇴' },
                  { name: 'Macedonia', code: '+389', flag: '🇲🇰' },
                  { name: 'Madagascar', code: '+261', flag: '🇲🇬' },
                  { name: 'Malta', code: '+356', flag: '🇲🇹' },
                  { name: 'Mauritius', code: '+230', flag: '🇲🇺' },
                  { name: 'Monaco', code: '+377', flag: '🇲🇨' },
                  { name: 'Mongolia', code: '+976', flag: '🇲🇳' },
                  { name: 'Montenegro', code: '+382', flag: '🇲🇪' },
                  { name: 'Morocco', code: '+212', flag: '🇲🇦' },
                  { name: 'Mozambique', code: '+258', flag: '🇲🇿' },
                  { name: 'Myanmar', code: '+95', flag: '🇲🇲' },
                  { name: 'Namibia', code: '+264', flag: '🇳🇦' },
                  { name: 'Nicaragua', code: '+505', flag: '🇳🇮' },
                  { name: 'Panama', code: '+507', flag: '🇵🇦' },
                  { name: 'Paraguay', code: '+595', flag: '🇵🇾' },
                  { name: 'Peru', code: '+51', flag: '🇵🇪' },
                  { name: 'Poland', code: '+48', flag: '🇵🇱' },
                  { name: 'Romania', code: '+40', flag: '🇷🇴' },
                  { name: 'Russia', code: '+7', flag: '🇷🇺' },
                  { name: 'Serbia', code: '+381', flag: '🇷🇸' },
                  { name: 'Slovakia', code: '+421', flag: '🇸🇰' },
                  { name: 'Slovenia', code: '+386', flag: '🇸🇮' },
                  { name: 'Taiwan', code: '+886', flag: '🇹🇼' },
                  { name: 'Uganda', code: '+256', flag: '🇺🇬' },
                  { name: 'Ukraine', code: '+380', flag: '🇺🇦' },
                  { name: 'Uruguay', code: '+598', flag: '🇺🇾' },
                  { name: 'Uzbekistan', code: '+998', flag: '🇺🇿' },
                  { name: 'Venezuela', code: '+58', flag: '🇻🇪' }
              ],
              get filteredCountries() {
                  if (!this.search) return this.countries;
                  return this.countries.filter(c => c.name.toLowerCase().includes(this.search.toLowerCase()) || c.code.includes(this.search));
              },
              init() {
                  this.countries.sort((a, b) => a.name.localeCompare(b.name));
                  let india = this.countries.find(c => c.name === 'India');
                  let filtered = this.countries.filter(c => c.name !== 'India');
                  this.countries = india ? [india, ...filtered] : this.countries;
              }
          }" @click.outside="open = false">
            
            <button type="button" @click="open = !open" class="btn ecc-input d-flex align-items-center gap-2" style="border-radius: 12px 0 0 12px !important; border-right: 0 !important; max-width: 140px; background-color: var(--ecc-surface) !important; color: #fff; border: 1px solid var(--ecc-border) !important;">
              <span x-text="selected.flag" style="font-size: 18px;"></span>
              <span x-text="selected.code" style="font-size: 14px; font-weight: 600;"></span>
              <span class="material-symbols-outlined" style="font-size: 16px; opacity: 0.6;">keyboard_arrow_down</span>
            </button>

            <div x-show="open" class="position-absolute z-3 mt-1 shadow-lg bg-dark border border-secondary" style="display: none; top: 100%; left: 0; width: 300px; border-radius: 12px; max-height: 280px; overflow-y: auto; background-color: #121212 !important; border-color: #333 !important;">
              <div class="p-2 sticky-top" style="background-color: #121212;">
                <input type="text" x-model="search" class="form-control form-control-sm text-white" placeholder="Search country..." style="background-color: #222 !important; border-color: #444 !important; color: #fff !important; font-size: 13px;">
              </div>
              
              <div class="list-group list-group-flush">
                <template x-for="c in filteredCountries" :key="c.name + c.code">
                  <button type="button" @click="selected = c; @this.set('country_code', c.code); open = false; search = '';" class="list-group-item list-group-item-action text-white d-flex align-items-center justify-content-between py-2 px-3 border-0" style="background-color: transparent; font-size: 13px; border-bottom: 1px solid #222 !important;">
                    <div class="d-flex align-items-center gap-2">
                      <span x-text="c.flag" style="font-size: 16px;"></span>
                      <span x-text="c.name" class="text-truncate" style="max-width: 160px; font-family: 'Noto Sans', sans-serif;"></span>
                    </div>
                    <span x-text="c.code" class="text-muted fw-bold" style="font-family: 'Noto Sans', sans-serif;"></span>
                  </button>
                </template>
              </div>
            </div>

            <input type="text" wire:model="phone" class="form-control ecc-input" placeholder="98765 43210" style="border-radius: 0 12px 12px 0 !important;">
          </div>
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
    --ecc-bg:#020202;
    --ecc-surface:#181818;
    --ecc-border:#333333;
    --ecc-border-soft: rgba(255, 255, 255, 0.065);
    --ecc-text-primary: #ffffff;
    --ecc-text-secondary: rgba(255, 255, 255, 0.78);
    --ecc-text-muted: rgba(255, 255, 255, 0.58);
    --ecc-text-subtle: rgba(255, 255, 255, 0.42);
    --ecc-primary: #C7A75A;
    --ecc-primary-dark: #9C7D35;
  }

  .ecc-bg{
    --ecc-bg:#020202;
    --ecc-surface:#181818;
    --ecc-border:#333333;
    --ecc-border-soft: rgba(255, 255, 255, 0.065);
    --ecc-text-primary: #ffffff;
    --ecc-text-secondary: rgba(255, 255, 255, 0.78);
    --ecc-text-muted: rgba(255, 255, 255, 0.58);
    --ecc-text-subtle: rgba(255, 255, 255, 0.42);
    --ecc-primary: #C7A75A;
    --ecc-primary-dark: #9C7D35;

    background: var(--ecc-bg) !important;
    color: var(--ecc-text-primary) !important;
    font-family: "Newsreader", serif;
  }
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
  .ecc-dot{ height: 4px; width: 10px; border-radius: 9999px; background: var(--ecc-border); transition: all .3s; }
  .ecc-dot--active{ width: 34px; background: var(--ecc-primary); box-shadow: 0 0 10px rgba(199, 167, 90,.30); }

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

  .ecc-label{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--ecc-text-primary) !important;
    margin-bottom: 8px;
    margin-left: 14px;
  }
  .ecc-input{
    background: var(--ecc-surface) !important;
    border: 1px solid var(--ecc-border) !important;
    border-radius: 12px !important;
    color: #ffffff !important;
    padding: 14px 18px !important;
    font-family: "Noto Sans", system-ui, sans-serif;
  }
  .ecc-input::placeholder{ color: var(--ecc-text-subtle) !important; }
  .ecc-input:focus,
  .ecc-input:active {
    background: var(--ecc-surface) !important;
    border-color: var(--ecc-primary) !important;
    box-shadow: 0 0 0 1px var(--ecc-primary) !important;
    color: #ffffff !important;
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

  .ecc-hint{
    font-family: "Noto Sans", system-ui, sans-serif;
    font-size: 11px;
    color: var(--ecc-text-subtle);
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
    color: var(--ecc-text-primary);
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
