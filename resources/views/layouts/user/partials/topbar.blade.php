@php
    $title = $title ?? '';
    $backUrl = $backUrl ?? null;
    $cartCount = $cartCount ?? null;
    $cartUrl = $cartUrl ?? route('shop.cart');
@endphp

<div class="d-flex align-items-center justify-content-between py-2" style="height:56px;">
    {{-- Left: Back --}}
    <div class="d-flex align-items-center" style="width:56px;">
        @if($backUrl)
            <a href="{{ $backUrl }}"
               class="btn btn-sm rounded-circle border-0 ecc-text-primary"
               style="width:40px;height:40px; background: var(--ecc-border-soft);">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
        @endif
    </div>

    {{-- Center: Title --}}
    <div class="flex-grow-1 text-center">
        @php
            $displayTitle = trim($title);
            $lowerTitle = strtolower($displayTitle);
            
            if ($lowerTitle === 'home' || $lowerTitle === 'explore') {
                $displayTitle = \App\Models\Setting::get('nav_label_explore', 'Explore');
            } elseif ($lowerTitle === 'archive') {
                $displayTitle = \App\Models\Setting::get('nav_label_archive', 'Archive');
            } elseif ($lowerTitle === 'auctions') {
                $displayTitle = \App\Models\Setting::get('nav_label_auctions', 'Auctions');
            } elseif ($lowerTitle === 'club') {
                $displayTitle = \App\Models\Setting::get('nav_label_club', 'Club');
            } elseif ($lowerTitle === 'shop') {
                $displayTitle = \App\Models\Setting::get('nav_label_shop', 'Shop');
            } elseif ($lowerTitle === 'profile' || $lowerTitle === 'settings') {
                $displayTitle = \App\Models\Setting::get('nav_label_profile', 'Profile');
            }
            if (!empty($displayTitle) && !str_starts_with(strtolower($displayTitle), 'the ')) {
                $displayTitle = 'The ' . $displayTitle;
            }
        @endphp
        <div class="fw-bold" style="letter-spacing:.5px; font-size: 18px;">
            {{ $displayTitle }}
        </div>
    </div>

    {{-- Right: Cart --}}
    <div class="d-flex align-items-center justify-content-end" style="width:56px;">
        <button
            type="button"
            class="btn btn-sm rounded-circle border-0 ecc-text-primary ecc-theme-toggle me-2"
            id="eccThemeToggle"
            style="width:40px;height:40px; background: var(--ecc-border-soft);"
            aria-label="Switch color theme"
            title="Switch theme"
        >
            <i class="mdi mdi-weather-night ecc-theme-icon ecc-theme-icon-dark"></i>
            <i class="mdi mdi-weather-sunny ecc-theme-icon ecc-theme-icon-light"></i>
        </button>

        <a href="{{ $cartUrl }}"
           class="position-relative btn btn-sm rounded-circle border-0 ecc-text-primary"
           style="width:40px;height:40px; background: var(--ecc-border-soft);"
           x-data="{ count: {{ (int)($cartCount ?? 0) }} }"
           @refresh-cart-badge.window="count = $event.detail.count">
            <span class="material-symbols-outlined">shopping_cart</span>

            <template x-if="count > 0">
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                      style="background: var(--ecc-primary); color:var(--ecc-bg-page); font-weight:800; font-size:10px;"
                      x-text="count">
                </span>
            </template>
        </a>
    </div>
</div>
