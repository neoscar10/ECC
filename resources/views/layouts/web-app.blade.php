<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title', $title ?? config('app.name', 'ECC'))) }}</title>

    {{-- Fonts + Icons from original app layout --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@200..700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">

    {{-- Theme styles for ECC user web (dark + gold) --}}
    @includeIf('layouts.user.partials.styles')

    <style>
        :root {
            --luxe-bg: #17130b;
            --luxe-bg-2: #1d170d;
            --luxe-surface: #221b11;
            --luxe-surface-2: #2a2115;
            --luxe-card: rgba(255,255,255,0.04);
            --luxe-card-2: rgba(255,255,255,0.06);
            --luxe-border: rgba(212, 175, 55, 0.18);
            --luxe-border-strong: rgba(212, 175, 55, 0.34);
            --luxe-gold: #d4af37;
            --luxe-gold-2: #e4c85b;
            --luxe-text: #f5f0e7;
            --luxe-text-soft: #b8ab91;
            --luxe-muted: #8f826b;
            --luxe-danger: #ff4d4f;
            --luxe-shadow: 0 20px 45px rgba(0,0,0,0.35);
            --luxe-radius-xl: 24px;
            --luxe-radius-lg: 20px;
            --luxe-radius-md: 16px;
            --luxe-radius-sm: 12px;
            --luxe-max: 1440px;
        }

        html, body {
            background:
                radial-gradient(circle at top, rgba(212,175,55,0.08), transparent 22%),
                linear-gradient(180deg, #19140b 0%, #141008 100%);
            color: var(--luxe-text);
        }

        body.web-app-layout {
            min-height: 100vh;
            font-family: "Manrope", "Public Sans", system-ui, -apple-system, sans-serif;
        }

        .luxe-page-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .luxe-container {
            width: min(100% - 24px, var(--luxe-max));
            margin-inline: auto;
        }

        @media (min-width: 768px) {
            .luxe-container {
                width: min(100% - 48px, var(--luxe-max));
            }
        }

        .luxe-header {
            position: sticky;
            top: 0;
            z-index: 1050;
            backdrop-filter: blur(16px);
            background: rgba(23, 19, 11, 0.92);
            border-bottom: 1px solid var(--luxe-border);
        }

        .luxe-brand {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
            text-decoration: none;
            color: var(--luxe-text);
            font-weight: 800;
            letter-spacing: .01em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .luxe-brand:hover {
            color: var(--luxe-text);
        }

        .luxe-brand-icon {
            width: 28px;
            height: 28px;
            color: var(--luxe-gold);
            flex: 0 0 auto;
        }

        .luxe-search {
            position: relative;
        }

        .luxe-search .form-control {
            height: 48px;
            border-radius: 999px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--luxe-border);
            color: var(--luxe-text);
            padding-left: 46px;
            box-shadow: none;
        }

        .luxe-search .form-control::placeholder {
            color: #8f8878;
        }

        .luxe-search .form-control:focus {
            border-color: var(--luxe-border-strong);
            box-shadow: 0 0 0 0.2rem rgba(212,175,55,.08);
            background: rgba(255,255,255,0.04);
            color: var(--luxe-text);
        }

        .luxe-search-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            color: var(--luxe-gold);
            pointer-events: none;
            line-height: 0;
        }

        .luxe-top-nav {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: nowrap;
        }

        .luxe-top-link {
            color: rgba(245,240,231,0.88);
            text-decoration: none;
            font-size: .92rem;
            font-weight: 600;
            padding: .65rem .8rem;
            border-radius: 999px;
            transition: .2s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .luxe-top-link:hover,
        .luxe-top-link.active {
            color: #111;
            background: var(--luxe-gold);
        }
        
        .luxe-top-link .material-symbols-outlined {
            font-size: 18px;
        }

        .luxe-icon-btn {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.06);
            background: rgba(255,255,255,0.06);
            color: var(--luxe-text);
            transition: .2s ease;
            position: relative;
            text-decoration: none;
        }

        .luxe-icon-btn:hover {
            background: var(--luxe-gold);
            color: #111;
            border-color: var(--luxe-gold);
        }
        
        .luxe-icon-badge {
            position: absolute; 
            top: -2px; 
            right: -2px;
            background: #f2b90d;
            color: #000;
            font-size: 10px;
            font-weight: 800;
        }

        .luxe-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid var(--luxe-gold);
            object-fit: cover;
            display: block;
        }

        .luxe-main {
            flex: 1 1 auto;
            padding-block: 1.75rem 3.5rem;
        }

        @media (min-width: 992px) {
            .luxe-main {
                padding-block: 2rem 4.5rem;
            }
        }

        .luxe-section-title {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
            font-size: clamp(1.65rem, 2vw, 2.35rem);
            font-weight: 800;
            line-height: 1.1;
            margin: 0;
            color: #fff;
            letter-spacing: -.02em;
        }

        .luxe-section-title-bar {
            width: 7px;
            height: 42px;
            border-radius: 999px;
            background: var(--luxe-gold);
            flex: 0 0 auto;
        }

        .luxe-subsection-title {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
            font-size: clamp(1.4rem, 1.5vw, 1.9rem);
            font-weight: 800;
            line-height: 1.1;
            margin: 0;
            color: #fff;
            letter-spacing: -.02em;
        }

        .luxe-subsection-title-bar {
            width: 6px;
            height: 28px;
            border-radius: 999px;
            background: var(--luxe-gold);
            flex: 0 0 auto;
        }

        .luxe-round-control {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid var(--luxe-border);
            background: transparent;
            color: var(--luxe-gold);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
        }

        .luxe-round-control:hover {
            background: var(--luxe-gold);
            color: #111;
            border-color: var(--luxe-gold);
        }

        .luxe-scroll-rail {
            display: flex;
            gap: 1.25rem;
            overflow-x: auto;
            padding-bottom: .5rem;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: var(--luxe-gold) transparent;
        }

        .luxe-scroll-rail::-webkit-scrollbar {
            height: 6px;
        }

        .luxe-scroll-rail::-webkit-scrollbar-thumb {
            background: var(--luxe-gold);
            border-radius: 999px;
        }

        .luxe-hero-card {
            min-width: 320px;
            width: min(100%, 430px);
            border-radius: var(--luxe-radius-xl);
            overflow: hidden;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02)),
                var(--luxe-surface);
            border: 1px solid rgba(212,175,55,0.12);
            box-shadow: var(--luxe-shadow);
            flex: 0 0 auto;
        }

        @media (min-width: 992px) {
            .luxe-hero-card {
                min-width: 390px;
            }
        }

        .luxe-hero-media {
            position: relative;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            background: #0f0c07;
        }

        .luxe-hero-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .45s ease;
        }

        .luxe-hero-card:hover .luxe-hero-media img {
            transform: scale(1.045);
        }

        .luxe-live-pill {
            position: absolute;
            top: 14px;
            left: 14px;
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            padding: .35rem .7rem;
            border-radius: 999px;
            background: #ff463f;
            color: #fff;
            font-size: .72rem;
            font-weight: 800;
            line-height: 1;
            z-index: 2;
        }

        .luxe-live-pill-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #fff;
            display: inline-block;
        }

        .luxe-lot-badge {
            position: absolute;
            right: 14px;
            bottom: 14px;
            padding: .55rem .85rem;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(0,0,0,.45);
            color: #fff;
            font-size: .72rem;
            font-weight: 700;
            backdrop-filter: blur(10px);
            z-index: 2;
        }

        .luxe-hero-body {
            padding: 1.2rem 1.15rem 1.15rem;
            background:
                linear-gradient(180deg, rgba(255,255,255,.02), rgba(255,255,255,.02)),
                #2a2418;
        }

        .luxe-hero-title {
            margin: 0 0 .5rem;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.18;
            color: #fff;
            letter-spacing: -.02em;
        }

        .luxe-label {
            color: var(--luxe-muted);
            font-size: .68rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: .08em;
            margin-bottom: .2rem;
        }

        .luxe-price {
            color: var(--luxe-gold-2);
            font-size: clamp(1.4rem, 2vw, 2rem);
            font-weight: 900;
            line-height: 1;
        }

        .luxe-time {
            color: #fff;
            font-size: 1rem;
            font-weight: 800;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .luxe-gold-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            height: 52px;
            border-radius: 999px;
            background: var(--luxe-gold);
            color: #111 !important;
            border: none;
            text-decoration: none;
            font-weight: 800;
            letter-spacing: .02em;
            box-shadow: 0 12px 25px rgba(212,175,55,.18);
            transition: transform .15s ease, filter .2s ease, box-shadow .2s ease;
        }

        .luxe-gold-btn:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
            color: #111 !important;
        }

        .luxe-gold-outline-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            height: 52px;
            padding-inline: 1.5rem;
            border-radius: 999px;
            background: transparent;
            color: var(--luxe-gold) !important;
            border: 1.5px solid var(--luxe-gold);
            text-decoration: none;
            font-weight: 800;
            letter-spacing: .02em;
            transition: .2s ease;
        }

        .luxe-gold-outline-btn:hover {
            background: var(--luxe-gold);
            color: #111 !important;
        }

        .luxe-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }

        .luxe-chip {
            border: 1px solid rgba(212,175,55,.14);
            background: rgba(255,255,255,.03);
            color: rgba(245,240,231,.92);
            border-radius: 999px;
            padding: .72rem 1rem;
            font-size: .9rem;
            font-weight: 600;
            line-height: 1;
            text-decoration: none;
            transition: .2s ease;
        }

        .luxe-chip:hover,
        .luxe-chip.active {
            background: var(--luxe-gold);
            border-color: var(--luxe-gold);
            color: #111;
            box-shadow: 0 8px 18px rgba(212,175,55,.18);
        }

        .luxe-grid-card {
            border-radius: 22px;
            overflow: hidden;
            background:
                linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
                var(--luxe-surface);
            border: 1px solid rgba(212,175,55,0.12);
            box-shadow: 0 18px 38px rgba(0,0,0,.26);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
            height: 100%;
        }

        .luxe-grid-card:hover {
            transform: translateY(-4px);
            border-color: rgba(212,175,55,0.28);
            box-shadow: 0 22px 42px rgba(0,0,0,.35);
        }

        .luxe-grid-media {
            position: relative;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            background: #0f0c07;
        }

        .luxe-grid-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .45s ease;
            cursor: pointer;
        }

        .luxe-grid-card:hover .luxe-grid-media img {
            transform: scale(1.06);
        }

        .luxe-fav-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 0;
            background: rgba(255,255,255,.14);
            backdrop-filter: blur(10px);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
            z-index: 20;
        }

        .luxe-fav-btn:hover,
        .luxe-fav-btn.active {
            background: var(--luxe-gold);
            color: #111;
        }

        .luxe-grid-body {
            padding: 1rem .95rem 1rem;
        }

        .luxe-grid-title {
            margin: 0 0 .2rem;
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.2;
            color: #fff;
            letter-spacing: -.015em;
        }

        .luxe-grid-subtitle {
            color: var(--luxe-text-soft);
            font-size: .82rem;
            margin-bottom: .9rem;
        }

        .luxe-grid-meta {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            border-top: 1px solid rgba(212,175,55,.07);
            padding-top: .85rem;
        }

        .luxe-footer {
            background: #0f0c07;
            border-top: 1px solid rgba(212,175,55,.12);
            padding-top: 3.25rem;
            padding-bottom: 1rem;
        }

        .luxe-footer-title {
            color: #fff;
            font-weight: 800;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .luxe-footer-text,
        .luxe-footer-link {
            color: var(--luxe-text-soft);
            text-decoration: none;
            font-size: .94rem;
            line-height: 1.9;
        }

        .luxe-footer-link:hover {
            color: var(--luxe-gold);
        }

        .luxe-newsletter .form-control {
            background: rgba(255,255,255,.02);
            border: 1px solid rgba(212,175,55,.14);
            border-radius: 999px;
            color: #fff;
            height: 46px;
            box-shadow: none;
        }

        .luxe-newsletter .form-control::placeholder {
            color: #8d826e;
        }

        .luxe-newsletter .form-control:focus {
            background: rgba(255,255,255,.03);
            border-color: rgba(212,175,55,.32);
            box-shadow: 0 0 0 .2rem rgba(212,175,55,.08);
            color: #fff;
        }

        .luxe-newsletter-btn {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 0;
            background: var(--luxe-gold);
            color: #111;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .luxe-legal {
            border-top: 1px solid rgba(212,175,55,.08);
            margin-top: 2rem;
            padding-top: 1rem;
            color: var(--luxe-muted);
            font-size: .82rem;
        }

        .luxe-mobile-nav {
            border-top: 1px solid rgba(212,175,55,.08);
            padding-top: .85rem;
            margin-top: .85rem;
        }

        .luxe-mobile-nav a {
            display: block;
            padding: .8rem 0;
            color: rgba(245,240,231,.92);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,.04);
        }

        .luxe-mobile-nav a:last-child {
            border-bottom: 0;
        }

        .luxe-empty-state {
            border: 1px dashed rgba(212,175,55,.18);
            border-radius: 24px;
            padding: 2rem 1.25rem;
            background: rgba(255,255,255,.02);
            text-align: center;
            color: var(--luxe-text-soft);
        }
        
        .ecc-blur {
            position: absolute; inset: 0;
            backdrop-filter: blur(8px);
            background: rgba(35, 30, 16, 0.3);
            z-index: 12;
            cursor: pointer;
        }

        .ecc-lock-overlay {
            position: absolute; inset: 0;
            z-index: 15;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 15px;
            cursor: pointer;
        }

        .ecc-lock-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(242, 185, 13, 0.3);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
            color: #f2b90d;
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        }

        .ecc-lock-title {
            color: #f5ebd0;
            font-weight: 800;
            font-size: 11px;
            letter-spacing: 0.05em;
        }

        .ecc-lock-hint {
            color: #cbbc90;
            margin-top: 5px;
            font-size: 10px;
            font-weight: 600;
        }

        .luxe-logout-simple {
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .luxe-logout-simple:hover {
            color: var(--luxe-gold) !important;
            text-decoration: underline !important;
        }
    </style>

    @livewireStyles
    @stack('styles')
</head>
<body class="web-app-layout">
    @php
      $active = 'home';
      if (request()->is('auctions*')) { $active = 'auctions'; }
      elseif (request()->is('archive*')) { $active = 'archive'; }
      elseif (request()->is('club*')) { $active = 'club'; }
      elseif (request()->is('orders*')) { $active = 'orders'; }
      elseif (request()->is('settings*')) { $active = 'settings'; }
      elseif (request()->is('shop*') || request()->is('products*')) { $active = 'shop'; }
      elseif (request()->is('home*')) { $active = 'explore'; }

      $mainItems = [
        ['key'=>'explore', 'label'=>'Explore',  'icon'=>'explore',          'href'=>url('/home')],
        ['key'=>'archive', 'label'=>'Archive',  'icon'=>'inventory_2',      'href'=>url('/archive')],
        ['key'=>'auctions','label'=>'Auctions', 'icon'=>'gavel',            'href'=>url('/auctions')],
        ['key'=>'club',    'label'=>'Club',     'icon'=>'shield_person',    'href'=>url('/club')],
        ['key'=>'shop',    'label'=>'Shop',     'icon'=>'storefront',       'href'=>route('shop.index')],
        ['key'=>'orders',  'label'=>'Orders',   'icon'=>'receipt_long',     'href'=>route('shop.orders')],
        ['key'=>'settings','label'=>'Settings', 'icon'=>'settings',         'href'=>route('settings')],
      ];

      $isOn = fn($k) => $active === $k;
      $cartCount = $cartCount ?? 0;
      $cartUrl = route('shop.cart');
    @endphp
    <div class="luxe-page-shell">
        <header class="luxe-header">
            <div class="luxe-container py-3">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3 gap-lg-4 flex-grow-1">
                        <a href="{{ url('/') }}" class="luxe-brand">
                            <svg class="luxe-brand-icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M4 4H17.3334V17.3334H30.6666V30.6666H44V44H4V4Z" fill="currentColor"/>
                            </svg>
                            <span>ECC</span>
                        </a>

                        {{-- Search removed as per requirement --}}
                    </div>

                    <div class="d-none d-lg-flex align-items-center gap-3">
                        <nav class="luxe-top-nav">
                            @foreach($mainItems as $it)
                                <a href="{{ $it['href'] }}" class="luxe-top-link {{ $isOn($it['key']) ? 'active' : '' }}">
                                    <span class="material-symbols-outlined">{{ $it['icon'] }}</span>
                                    <span>{{ $it['label'] }}</span>
                                </a>
                            @endforeach

                            @auth
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="border-0 bg-transparent p-0 ms-3 text-white-50 text-decoration-none luxe-logout-simple" title="Logout">
                                        Logout
                                    </button>
                                </form>
                            @endauth
                        </nav>

                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ $cartUrl }}" class="luxe-icon-btn" aria-label="Cart"
                               x-data="{ count: {{ (int)($cartCount ?? 0) }} }"
                               @refresh-cart-badge.window="count = $event.detail.count">
                                <i class="mdi mdi-cart-outline fs-5"></i>
                                <template x-if="count > 0">
                                    <span class="luxe-icon-badge badge rounded-pill" x-text="count"></span>
                                </template>
                            </a>

                            @guest
                                <a href="{{ route('login') }}" class="luxe-top-link ms-2" style="background: rgba(255,255,255,0.06);">
                                    <span>Log In</span>
                                </a>
                            @endguest
                        </div>
                    </div>

                    <div class="d-flex align-items-center d-lg-none gap-2">
                        <a href="{{ $cartUrl }}" class="luxe-icon-btn d-lg-none" aria-label="Cart">
                            <i class="mdi mdi-cart-outline fs-5"></i>
                        </a>
                        <button
                            class="btn btn-link text-decoration-none text-white p-0 border-0 shadow-none"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#luxeMobileNav"
                            aria-expanded="false"
                            aria-controls="luxeMobileNav"
                        >
                            <i class="mdi mdi-menu fs-2"></i>
                        </button>
                    </div>
                </div>

                <div class="collapse d-lg-none" id="luxeMobileNav">
                    <div class="pt-3">
                        {{-- Mobile Search removed as per requirement --}}

                        <div class="luxe-mobile-nav">
                            @foreach($mainItems as $it)
                                <a href="{{ $it['href'] }}" class="{{ $isOn($it['key']) ? 'text-warning' : '' }} d-flex align-items-center gap-2">
                                    <span class="material-symbols-outlined fs-5">{{ $it['icon'] }}</span>
                                    <span>{{ $it['label'] }}</span>
                                </a>
                            @endforeach

                            @auth
                                <form action="{{ route('logout') }}" method="POST" class="d-block mt-3">
                                    @csrf
                                    <button type="submit" class="border-0 bg-transparent p-0 text-danger-subtitle text-decoration-none fw-bold luxe-logout-simple">
                                        Logout
                                    </button>
                                </form>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="luxe-main">
            <div class="luxe-container">
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>

        <footer class="luxe-footer">
            <div class="luxe-container">
                <div class="row g-4 g-lg-5">
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <svg class="luxe-brand-icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M4 4H17.3334V17.3334H30.6666V30.6666H44V44H4V4Z" fill="currentColor"/>
                            </svg>
                            <span class="fw-bold text-uppercase text-white">Executive club cricket</span>
                        </div>
                        <p class="luxe-footer-text mb-0">
                            The world’s premier destination for rare collectibles, luxury watches, and fine art. Connecting discerning collectors with extraordinary items.
                        </p>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="luxe-footer-title">Quick Links</div>
                        <div class="d-flex flex-column">
                            <a href="{{ route('auctions.index') }}" class="luxe-footer-link">Live Auctions</a>
                            <a href="{{ url('/archive') }}" class="luxe-footer-link">Archive</a>
                            <a href="{{ route('shop.index') }}" class="luxe-footer-link">Shop</a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="luxe-footer-title">Support</div>
                        <div class="d-flex flex-column">
                            <a href="javascript:void(0)" class="luxe-footer-link">Contact Support</a>
                            <a href="javascript:void(0)" class="luxe-footer-link">FAQ</a>
                            <a href="javascript:void(0)" class="luxe-footer-link">Buyer Protection</a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="luxe-footer-title">Newsletter</div>
                        <p class="luxe-footer-text mb-3">Stay informed about upcoming exclusive auctions.</p>
                        <form class="luxe-newsletter d-flex align-items-center gap-2" onsubmit="return false;">
                            <input type="email" class="form-control" placeholder="Email address">
                            <button type="submit" class="luxe-newsletter-btn">
                                <i class="mdi mdi-send"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="luxe-legal d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <div>© {{ now()->year }} Executive club cricket. All Rights Reserved.</div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="javascript:void(0)" class="luxe-footer-link">Privacy Policy</a>
                        <a href="javascript:void(0)" class="luxe-footer-link">Accessibility</a>
                        <a href="javascript:void(0)" class="luxe-footer-link">Cookies</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @livewireScripts
    @stack('scripts')
    @includeIf('layouts.partials._overlay_cleanup')
</body>
</html>
