@php
  $active = $active ?? null;

  $items = [
    ['key'=>'explore','label'=>'Explore','icon'=>'explore',       'href'=>url('/home')],
    ['key'=>'archive','label'=>'Archive','icon'=>'inventory_2',   'href'=>url('/archive')],
    ['key'=>'auctions','label'=>'Auctions','icon'=>'gavel',       'href'=>url('/auctions')],
    ['key'=>'club',    'label'=>'Club',   'icon'=>'shield_person','href'=>url('/club')],
    ['key'=>'shop',    'label'=>'Shop',   'icon'=>'storefront',   'href'=>route('shop.index')],
    ['key'=>'settings','label'=>'Profile','icon'=>'account_circle','href'=>url('/settings')],
  ];

  $path = trim(request()->path(), '/');

  $keyFromPath = match (true) {
    $path === '' || str_starts_with($path, 'home')                        => 'explore',
    str_starts_with($path, 'archive')                                     => 'archive',
    str_starts_with($path, 'auctions')                                    => 'auctions',
    str_starts_with($path, 'club')                                        => 'club',
    str_starts_with($path, 'store') || str_starts_with($path, 'shop')     => 'shop',
    str_starts_with($path, 'settings')                                    => 'settings',
    default                                                               => null,
  };

  $active = $active ?? $keyFromPath;

  $isOn = fn($k) => $active === $k;

  $isAwaitingApproval = false;
  if ($user = auth('web')->user()) {
      $isAwaitingApproval = !$user->hasActiveMembership() && $user->memberships()->where('status', 'pending')->exists();
  }
@endphp

<div class="ecc-app-nav-wrapper fixed-bottom d-flex d-md-none justify-content-center w-100 pb-2">
  <nav class="ecc-app-nav">
    <div class="ecc-app-nav__container">
      <div class="row g-0 text-center flex-nowrap overflow-auto hide-scrollbar">
        @foreach($items as $it)
          @php 
            $on = $isOn($it['key']); 
            $disabled = $isAwaitingApproval && $it['key'] !== 'explore';
          @endphp
          <div class="col px-1">
            <a href="{{ $disabled ? 'javascript:void(0)' : $it['href'] }}"
               {!! $it['extras'] ?? '' !!}
               class="mx-0 px-0 mx-md-4 px-md-2 ecc-app-nav__item d-inline-flex flex-column align-items-center justify-content-center gap-1 text-decoration-none {{ $on ? 'is-active' : '' }}"
               @if($disabled) style="opacity: 0.45; pointer-events: none; cursor: default;" @endif>
              <span class="material-symbols-outlined ecc-app-nav__icon">{{ $it['icon'] }}</span>
              <span class="ecc-app-nav__label">{{ $it['label'] }}</span>
              @if($on)
                <span class="ecc-app-nav__glow" aria-hidden="true"></span>
              @endif
            </a>
          </div>
        @endforeach
      </div>
    </div>
  </nav>
</div>


    <style>
      .ecc-app-nav-wrapper {
        pointer-events: none;
        z-index: 1030;
      }

      .ecc-app-nav {
        pointer-events: auto;
        background: var(--ecc-bg-nav);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--ecc-border);
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.45);
        min-width: 320px;
        max-width: min(760px, calc(100% - 24px));
        margin: 0 auto;
      }

      .ecc-app-nav__container {
        padding: 8px 12px calc(env(safe-area-inset-bottom, 0px) + 8px);
      }

      /* --- Prevent Bootstrap/default blue links inside the bottom nav --- */
      .ecc-app-nav a.ecc-app-nav__item,
      .ecc-app-nav a.ecc-app-nav__item:link,
      .ecc-app-nav a.ecc-app-nav__item:visited {
        color: var(--ecc-text-muted) !important;
      }

      .ecc-app-nav a.ecc-app-nav__item:hover {
        color: var(--ecc-primary) !important;
      }

      .ecc-app-nav a.ecc-app-nav__item.is-active,
      .ecc-app-nav a.ecc-app-nav__item.is-active:link,
      .ecc-app-nav a.ecc-app-nav__item.is-active:visited {
        color: var(--ecc-primary) !important;
      }

      .ecc-app-nav__item {
        position: relative;
        padding: 6px 4px;
        min-width: 54px;
        transition: color 200ms ease;
      }

      .ecc-app-nav__icon {
        font-size: 24px;
        transition: transform 180ms ease;
        font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
      }

      .ecc-app-nav__label {
        font-size: 9px;
        letter-spacing: .10em;
        font-weight: 700;
        text-transform: uppercase;
      }

      .ecc-app-nav__item.is-active .ecc-app-nav__icon {
        font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        filter: drop-shadow(0 0 8px var(--ecc-primary-shadow));
      }

      .ecc-app-nav__glow {
        position: absolute;
        top: -6px;
        left: 50%;
        transform: translateX(-50%);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--ecc-primary-soft);
        filter: blur(12px);
        pointer-events: none;
      }

      .hide-scrollbar::-webkit-scrollbar { display: none; }
      .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
 
