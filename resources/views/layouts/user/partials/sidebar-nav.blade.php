@php
    $active = $active ?? null;

    $items = [
        ['key' => 'home',   'label' => 'Home',   'icon' => 'home',                'href' => route('home')],
        ['key' => 'store',  'label' => 'Store',  'icon' => 'storefront',          'href' => route('shop.index')],
        ['key' => 'events', 'label' => 'Events', 'icon' => 'confirmation_number', 'href' => url('/events')],
        ['key' => 'profile','label' => 'Profile','icon' => 'person',              'href' => url('/profile')],
    ];

    $isActive = function($k) use ($active){
        if ($active) return $active === $k;

        // Fallback by URL if active not provided
        return match($k){
            'home' => request()->is('home*'),
            'store' => request()->is('shop*'),
            'events' => request()->is('events*'),
            'profile' => request()->is('profile*'),
        };
    };
@endphp

<div class="p-3">
    <div class="text-uppercase fw-bold ecc-muted mb-2" style="font-size:11px; letter-spacing:.14em;">
        Navigation
    </div>

    <div class="d-grid gap-2">
        @foreach($items as $it)
            @php $on = $isActive($it['key']); @endphp
            <a href="{{ $it['href'] }}"
               class="ecc-nav-link d-flex align-items-center gap-2 px-3 py-2 {{ $on ? 'active' : '' }}">
                <span class="material-symbols-outlined" style="font-size:22px;">
                    {{ $it['icon'] }}
                </span>
                <span class="fw-semibold" style="font-size:14px;">
                    {{ $it['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</div>
