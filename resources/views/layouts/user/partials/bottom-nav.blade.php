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

        return match($k){
            'home' => request()->is('home*'),
            'store' => request()->is('shop*'),
            'events' => request()->is('events*'),
            'profile' => request()->is('profile*'),
        };
    };
@endphp

<div class="container-fluid h-100">
    <div class="row h-100 align-items-center text-center">
        @foreach($items as $it)
            @php $on = $isActive($it['key']); @endphp
            <div class="col">
                <a href="{{ $it['href'] }}"
                   class="d-inline-flex flex-column align-items-center justify-content-center gap-1 text-decoration-none"
                   style="color: {{ $on ? 'var(--ecc-primary)' : 'var(--ecc-text-muted)' }};">
                    <span class="material-symbols-outlined" style="font-size:24px;">
                        {{ $it['icon'] }}
                    </span>
                    <span class="fw-semibold" style="font-size:10px;">
                        {{ $it['label'] }}
                    </span>
                </a>
            </div>
        @endforeach
    </div>
</div>
