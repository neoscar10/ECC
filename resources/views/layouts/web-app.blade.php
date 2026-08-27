<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title', $title ?? config('app.name', 'ECC'))) }}</title>

    <!-- App Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('ecc_user_theme');
                var theme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'light';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (error) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

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
            --luxe-bg: var(--ecc-bg-page);
            --luxe-bg-2: var(--ecc-bg-surface);
            --luxe-surface: var(--ecc-bg-surface-2);
            --luxe-surface-2: var(--ecc-bg-elevated);
            --luxe-card: var(--ecc-bg-input);
            --luxe-card-2: var(--ecc-border-soft);
            --luxe-border: var(--ecc-border);
            --luxe-border-strong: var(--ecc-border-strong);
            --luxe-gold: var(--ecc-primary);
            --luxe-gold-2: var(--ecc-gold-300);
            --luxe-text: var(--ecc-text-primary);
            --luxe-text-soft: var(--ecc-text-secondary);
            --luxe-muted: var(--ecc-text-muted);
            --luxe-danger: var(--ecc-danger);
            --luxe-shadow: var(--ecc-shadow-card);
            --luxe-radius-xl: 24px;
            --luxe-radius-lg: 20px;
            --luxe-radius-md: 16px;
            --luxe-radius-sm: 12px;
            --luxe-max: 1440px;
        }

        html, body {
            background: var(--ecc-bg-page);
            color: var(--ecc-text-primary);
        }

        html[data-theme="dark"] body {
            background:
                radial-gradient(circle at top, rgba(199, 167, 90, 0.08), transparent 22%),
                linear-gradient(180deg, #19140b 0%, #141008 100%);
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
            background: var(--ecc-bg-nav);
            border-bottom: 1px solid var(--ecc-border);
        }

        .luxe-brand {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
            text-decoration: none;
            color: var(--ecc-text-primary);
            font-weight: 800;
            letter-spacing: .01em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .luxe-brand:hover {
            color: var(--ecc-text-primary);
        }

        .luxe-brand-icon {
            width: 86px;
            height: 67px;
            object-fit: contain;
            flex: 0 0 auto;
        }

        .luxe-brand-icon--footer {
            width: 115px;
            height: 90px;
        }

        .luxe-search {
            position: relative;
        }

        .luxe-search .form-control {
            height: 48px;
            border-radius: 999px;
            background: var(--ecc-bg-input);
            border: 1px solid var(--ecc-border);
            color: var(--ecc-text-primary);
            padding-left: 46px;
            box-shadow: none;
        }

        .luxe-search .form-control::placeholder {
            color: var(--ecc-text-muted);
        }

        .luxe-search .form-control:focus {
            border-color: var(--luxe-border-strong);
            box-shadow: 0 0 0 0.2rem rgba(199, 167, 90, .08);
            background: var(--ecc-bg-hover);
            color: var(--ecc-text-primary);
        }

        .luxe-search-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            color: var(--ecc-primary);
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
            color: var(--ecc-text-secondary);
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
            color: var(--ecc-text-inverse);
            background: var(--ecc-primary);
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
            border: 1px solid var(--ecc-border);
            background: var(--ecc-bg-input);
            color: var(--ecc-text-primary);
            transition: .2s ease;
            position: relative;
            text-decoration: none;
        }

        .luxe-icon-btn:hover {
            background: var(--ecc-primary);
            color: var(--ecc-text-inverse);
            border-color: var(--ecc-primary);
        }
        
        .luxe-icon-badge {
            position: absolute; 
            top: -2px; 
            right: -2px;
            background: var(--ecc-gold-500);
            color: #000;
            font-size: 10px;
            font-weight: 800;
        }

        .luxe-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid var(--ecc-primary);
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
            color: var(--ecc-text-primary);
            letter-spacing: -.02em;
        }

        .luxe-section-title-bar {
            width: 7px;
            height: 42px;
            border-radius: 999px;
            background: var(--ecc-primary);
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
            color: var(--ecc-text-primary);
            letter-spacing: -.02em;
        }

        .luxe-subsection-title-bar {
            width: 6px;
            height: 28px;
            border-radius: 999px;
            background: var(--ecc-primary);
            flex: 0 0 auto;
        }

        .luxe-round-control {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid var(--ecc-border);
            background: transparent;
            color: var(--ecc-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
        }

        .luxe-round-control:hover {
            background: var(--ecc-primary);
            color: var(--ecc-text-inverse);
            border-color: var(--ecc-primary);
        }

        .luxe-scroll-rail {
            display: flex;
            gap: 1.25rem;
            overflow-x: auto;
            padding-bottom: .5rem;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: var(--ecc-primary) transparent;
        }

        .luxe-scroll-rail::-webkit-scrollbar {
            height: 6px;
        }

        .luxe-scroll-rail::-webkit-scrollbar-thumb {
            background: var(--ecc-primary);
            border-radius: 999px;
        }

        .luxe-hero-card {
            min-width: 320px;
            width: min(100%, 430px);
            border-radius: var(--luxe-radius-xl);
            overflow: hidden;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02)),
                var(--ecc-bg-surface);
            border: 1px solid rgba(199, 167, 90, 0.12);
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
            background: var(--ecc-bg-page);
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
            color: var(--ecc-text-primary);
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
            border: 1px solid var(--ecc-border);
            background: var(--ecc-overlay-dark);
            color: var(--ecc-text-primary);
            font-size: .72rem;
            font-weight: 700;
            backdrop-filter: blur(10px);
            z-index: 2;
        }

        .luxe-hero-body {
            padding: 1.2rem 1.15rem 1.15rem;
            background:
                transparent,
                #2a2418;
        }

        .luxe-hero-title {
            margin: 0 0 .5rem;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.18;
            color: var(--ecc-text-primary);
            letter-spacing: -.02em;
        }

        .luxe-label {
            color: var(--ecc-text-muted);
            font-size: .68rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: .08em;
            margin-bottom: .2rem;
        }

        .luxe-price {
            color: var(--ecc-gold-300);
            font-size: clamp(1.4rem, 2vw, 2rem);
            font-weight: 900;
            line-height: 1;
        }

        .luxe-time {
            color: var(--ecc-text-primary);
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
            background: var(--ecc-primary);
            color: #111 !important;
            border: none;
            text-decoration: none;
            font-weight: 800;
            letter-spacing: .02em;
            box-shadow: 0 12px 25px rgba(199, 167, 90, .18);
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
            color: var(--ecc-primary) !important;
            border: 1.5px solid var(--ecc-primary);
            text-decoration: none;
            font-weight: 800;
            letter-spacing: .02em;
            transition: .2s ease;
        }

        .luxe-gold-outline-btn:hover {
            background: var(--ecc-primary);
            color: #111 !important;
        }

        .luxe-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }

        .luxe-chip {
            border: 1px solid var(--ecc-border);
            background: var(--ecc-bg-input);
            color: var(--ecc-text-secondary);
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
            background: var(--ecc-primary);
            border-color: var(--ecc-primary);
            color: var(--ecc-text-inverse);
            box-shadow: 0 8px 18px rgba(199, 167, 90, .18);
        }

        .luxe-grid-card {
            border-radius: 22px;
            overflow: hidden;
            background:
                linear-gradient(180deg, var(--ecc-bg-hover), transparent),
                var(--ecc-bg-surface);
            border: 1px solid rgba(199, 167, 90, 0.12);
            box-shadow: 0 18px 38px rgba(0,0,0,.26);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
            height: 100%;
        }

        .luxe-grid-card:hover {
            transform: translateY(-4px);
            border-color: rgba(199, 167, 90, 0.28);
            box-shadow: 0 22px 42px rgba(0,0,0,.35);
        }

        .luxe-grid-media {
            position: relative;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            background: var(--ecc-bg-page);
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
            color: var(--ecc-text-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
            z-index: 20;
        }

        .luxe-fav-btn:hover,
        .luxe-fav-btn.active {
            background: var(--ecc-primary);
            color: var(--ecc-text-inverse);
        }

        .luxe-grid-body {
            padding: 1rem .95rem 1rem;
        }

        .luxe-grid-title {
            margin: 0 0 .2rem;
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--ecc-text-primary);
            letter-spacing: -.015em;
        }

        .luxe-grid-subtitle {
            color: var(--ecc-text-secondary);
            font-size: .82rem;
            margin-bottom: .9rem;
        }

        .luxe-grid-meta {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            border-top: 1px solid rgba(199, 167, 90, .07);
            padding-top: .85rem;
        }

        .luxe-footer {
            background: var(--ecc-bg-page);
            border-top: 1px solid rgba(199, 167, 90, .12);
            padding-top: 3.25rem;
            padding-bottom: 1rem;
        }

        .luxe-footer-title {
            color: var(--ecc-text-primary);
            font-weight: 800;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .luxe-footer-text,
        .luxe-footer-link {
            color: var(--ecc-text-secondary);
            text-decoration: none;
            font-size: .94rem;
            line-height: 1.9;
        }

        .luxe-footer-link:hover {
            color: var(--ecc-primary);
        }

        .luxe-newsletter .form-control {
            background: var(--ecc-bg-input);
            border: 1px solid rgba(199, 167, 90, .14);
            border-radius: 999px;
            color: var(--ecc-text-primary);
            height: 46px;
            box-shadow: none;
        }

        .luxe-newsletter .form-control::placeholder {
            color: #8d826e;
        }

        .luxe-newsletter .form-control:focus {
            background: var(--ecc-bg-input);
            border-color: rgba(199, 167, 90, .32);
            box-shadow: 0 0 0 .2rem rgba(199, 167, 90, .08);
            color: var(--ecc-text-primary);
        }

        .luxe-newsletter-btn {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 0;
            background: var(--ecc-primary);
            color: var(--ecc-text-inverse);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .luxe-legal {
            border-top: 1px solid rgba(199, 167, 90, .08);
            margin-top: 2rem;
            padding-top: 1rem;
            color: var(--ecc-text-muted);
            font-size: .82rem;
        }

        .luxe-mobile-nav {
            border-top: 1px solid var(--ecc-border);
            padding-top: .85rem;
            margin-top: .85rem;
        }

        .luxe-mobile-nav a {
            display: block;
            padding: .8rem 0;
            color: var(--ecc-text-secondary);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid var(--ecc-border-soft);
        }

        .luxe-mobile-nav a:last-child {
            border-bottom: 0;
        }

        .luxe-empty-state {
            border: 1px dashed rgba(199, 167, 90, .18);
            border-radius: 24px;
            padding: 2rem 1.25rem;
            background: var(--ecc-bg-input);
            text-align: center;
            color: var(--ecc-text-secondary);
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
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            background: var(--ecc-overlay-dark);
            backdrop-filter: blur(3px);
            text-align: center;
            cursor: pointer;
        }

        .ecc-lock-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .7rem;
            max-width: 200px;
        }

        .ecc-lock-icon-circle {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: rgba(199, 167, 90, .12);
            border: 1px solid rgba(199, 167, 90, .24);
            color: var(--ecc-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 2px;
        }

        .ecc-lock-title {
            color: var(--ecc-primary);
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .ecc-lock-hint {
            color: var(--ecc-text-primary);
            opacity: 0.8;
            margin: 0;
            font-size: .76rem;
            line-height: 1.4;
            font-weight: 500;
        }

        .ecc-unlock-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: .6rem .95rem;
            border-radius: 999px;
            border: 1px solid var(--ecc-primary);
            background: rgba(199, 167, 90, .10);
            color: var(--ecc-primary);
            font-size: .76rem;
            font-weight: 800;
            text-decoration: none;
            transition: .2s ease;
            margin-top: 4px;
        }

        .ecc-unlock-btn:hover {
            background: var(--ecc-primary);
            color: var(--ecc-text-inverse);
        }

        .luxe-logout-simple {
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .luxe-logout-simple:hover {
            color: var(--ecc-primary) !important;
            text-decoration: underline !important;
        }

        /* Mobile Hamburger Drawer Styles */
        .luxe-mobile-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 1rem;
            background: var(--ecc-bg-surface, #19140b);
            border: 1px solid var(--ecc-border, rgba(199, 167, 90, 0.2));
            border-radius: 16px;
            margin-top: 0.75rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .luxe-mobile-nav a {
            color: var(--ecc-text-primary, #ffffff) !important;
            text-decoration: none !important;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s ease;
        }

        .luxe-mobile-nav a:hover,
        .luxe-mobile-nav a.is-active {
            color: #111111 !important;
            background: var(--ecc-primary, #c5a365) !important;
            border-color: var(--ecc-primary, #c5a365) !important;
        }

        /* Override Tailwind .collapse conflicts on Bootstrap Mobile Nav */
        #luxeMobileNav.collapse.show {
            display: block !important;
            visibility: visible !important;
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
      elseif (request()->is('vault*')) { $active = 'vault'; }
      elseif (request()->is('club*')) { $active = 'club'; }
      elseif (request()->is('orders*')) { $active = 'orders'; }
      elseif (request()->is('settings*')) { $active = 'settings'; }
      elseif (request()->is('shop*') || request()->is('products*')) { $active = 'shop'; }
      elseif (request()->is('home*')) { $active = 'explore'; }

      $rawMainItems = [
        ['key'=>'explore', 'label'=>\App\Models\Setting::get('nav_label_explore', 'Explore'),  'icon'=>'explore',          'href'=>url('/home')],
        ['key'=>'archive', 'label'=>\App\Models\Setting::get('nav_label_archive', 'Archive'),  'icon'=>'inventory_2',      'href'=>url('/archive')],
        ['key'=>'auctions','label'=>\App\Models\Setting::get('nav_label_auctions', 'Auctions'), 'icon'=>'gavel',            'href'=>url('/auctions')],
        ['key'=>'club',    'label'=>\App\Models\Setting::get('nav_label_club', 'Club'),     'icon'=>'shield_person',    'href'=>url('/club')],
        ['key'=>'shop',    'label'=>\App\Models\Setting::get('nav_label_shop', 'Shop'),     'icon'=>'storefront',       'href'=>route('shop.index')],
        ['key'=>'orders',  'label'=>'Orders',   'icon'=>'receipt_long',     'href'=>route('shop.orders')],
        ['key'=>'settings','label'=>\App\Models\Setting::get('nav_label_profile', 'Profile'), 'icon'=>'account_circle',  'href'=>route('settings')],
      ];

      $sequenceJson = \App\Models\Setting::get('nav_sequence');
      $sequence = $sequenceJson ? json_decode($sequenceJson, true) : ['explore', 'archive', 'auctions', 'club', 'shop', 'profile'];
      
      $mainItems = [];
      foreach ($sequence as $k) {
          $actualKey = $k === 'profile' ? 'settings' : $k;
          foreach ($rawMainItems as $item) {
              if ($item['key'] === $actualKey) {
                  $mainItems[] = $item;
                  break;
              }
          }
      }
      foreach ($rawMainItems as $item) {
          $mappedKey = $item['key'] === 'settings' ? 'profile' : $item['key'];
          if (!in_array($mappedKey, $sequence)) {
              $mainItems[] = $item;
          }
      }

      $isOn = fn($k) => $active === $k;
      $cartCount = $cartCount ?? 0;
      $cartUrl = route('shop.cart');

      $isAwaitingApproval = false;
      $isDeactivated = false;
      $deactivationMessage = '';
      if ($user = auth('web')->user()) {
          $isAwaitingApproval = !$user->hasActiveMembership() && $user->memberships()->where('status', 'pending')->exists();
          $isDeactivated = !$user->hasActiveMembership() && $user->memberships()->where('status', 'cancelled')->exists();
          
          if ($isDeactivated) {
              $config = \App\Models\ContactConfig::first();
              $email = $config->support_email ?? 'support@executivecricketclub.com';
              $phone = $config->concierge_phone ?? '';
              $deactivationMessage = "Membership Deactivated: Please contact support at {$email}" . ($phone ? " or call {$phone}" : "") . " to restore access.";
          }
      }
    @endphp
    <div class="luxe-page-shell">
        @if($isDeactivated)
            <div class="deactivated-banner d-flex align-items-center justify-content-center py-2 px-3 ecc-text-primary" style="background: linear-gradient(90deg, #842029 0%, #a52834 100%); font-size: 0.82rem; font-weight: 700; letter-spacing: 0.6px; border-bottom: 1px solid rgba(255,255,255,0.1); position: sticky; top: 0; z-index: 2000;">
                <i class="mdi mdi-alert-circle-outline me-2 fs-6"></i>
                <span class="text-uppercase">{{ $deactivationMessage }}</span>
            </div>
        @endif
        <header class="luxe-header">
            <div class="luxe-container">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3 gap-lg-4 flex-grow-1">
                        <a href="{{ url('/') }}" class="luxe-brand">
                            <img src="{{ asset('ecc_logo_dark.png') }}" class="luxe-brand-icon" alt="ECC Logo">
                        </a>

                        {{-- Search removed as per requirement --}}
                    </div>

                    <div class="d-none d-lg-flex align-items-center gap-3">
                        <nav class="luxe-top-nav">
                            @foreach($mainItems as $it)
                                @php
                                    $disabled = ($isAwaitingApproval || $isDeactivated) && $it['key'] !== 'explore';
                                @endphp
                                <a href="{{ $disabled ? 'javascript:void(0)' : $it['href'] }}"
                                   class="luxe-top-link {{ $isOn($it['key']) ? 'active' : '' }}"
                                   {!! $it['extras'] ?? '' !!}
                                   @if($disabled) style="opacity: 0.45; pointer-events: none; cursor: default;" title="Awaiting Membership Approval" @endif>
                                    <span class="material-symbols-outlined">{{ $it['icon'] }}</span>
                                    <span>{{ $it['label'] }}</span>
                                </a>
                            @endforeach

                            @auth
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="border-0 bg-transparent p-0 ms-3 ecc-text-primary-50 text-decoration-none luxe-logout-simple" title="Logout">
                                        Logout
                                    </button>
                                </form>
                            @endauth
                        </nav>

                        <div class="d-flex align-items-center gap-2">
                            <button
                                type="button"
                                class="luxe-icon-btn ecc-theme-toggle me-1"
                                id="eccThemeToggle"
                                aria-label="Switch color theme"
                                title="Switch theme"
                            >
                                <i class="mdi mdi-weather-night ecc-theme-icon ecc-theme-icon-dark"></i>
                                <i class="mdi mdi-weather-sunny ecc-theme-icon ecc-theme-icon-light"></i>
                            </button>

                            <a href="{{ $cartUrl }}" class="luxe-icon-btn" aria-label="Cart"
                               x-data="{ count: {{ (int)($cartCount ?? 0) }} }"
                               @refresh-cart-badge.window="count = $event.detail.count">
                                <i class="mdi mdi-cart-outline fs-5"></i>
                                <template x-if="count > 0">
                                    <span class="luxe-icon-badge badge rounded-pill" x-text="count"></span>
                                </template>
                            </a>

                            @guest
                                <a href="{{ route('login') }}" class="luxe-top-link ms-2" style="background: var(--ecc-bg-input);">
                                    <span>Log In</span>
                                </a>
                            @endguest
                        </div>
                    </div>

                    <div class="d-flex align-items-center d-lg-none gap-2">
                        <button
                            type="button"
                            class="luxe-icon-btn ecc-theme-toggle"
                            id="eccThemeToggleMobile"
                            aria-label="Switch color theme"
                            title="Switch theme"
                        >
                            <i class="mdi mdi-weather-night ecc-theme-icon ecc-theme-icon-dark"></i>
                            <i class="mdi mdi-weather-sunny ecc-theme-icon ecc-theme-icon-light"></i>
                        </button>

                        <a href="{{ $cartUrl }}" class="luxe-icon-btn d-lg-none" aria-label="Cart"
                           x-data="{ count: {{ (int)($cartCount ?? 0) }} }"
                           @refresh-cart-badge.window="count = $event.detail.count">
                            <i class="mdi mdi-cart-outline fs-5"></i>
                            <template x-if="count > 0">
                                <span class="luxe-icon-badge badge rounded-pill" x-text="count"></span>
                            </template>
                        </a>
                        <button
                            class="btn btn-link text-decoration-none ecc-text-primary p-0 border-0 shadow-none"
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
                                @php
                                    $disabled = $isAwaitingApproval && $it['key'] !== 'explore';
                                @endphp
                                <a href="{{ $disabled ? 'javascript:void(0)' : $it['href'] }}"
                                   class="{{ $isOn($it['key']) ? 'is-active text-warning' : '' }} d-flex align-items-center gap-2"
                                   {!! $it['extras'] ?? '' !!}
                                   @if($disabled) style="opacity: 0.45; pointer-events: none; cursor: default;" @endif>
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
                            <img src="{{ asset('ecc_logo_dark.png') }}" class="luxe-brand-icon luxe-brand-icon--footer" alt="ECC Logo">
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
                            <a href="{{ route('contact') }}" class="luxe-footer-link">Contact Support</a>
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
                    <div>© {{ now()->year }} Executive Club Cricket. All Rights Reserved.</div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('privacy') }}" class="luxe-footer-link">Privacy Policy</a>
                        <a href="javascript:void(0)" class="luxe-footer-link">Accessibility</a>
                        <a href="javascript:void(0)" class="luxe-footer-link">Cookies</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (function () {
            var initialized = false;

            function initThemeToggle() {
                var toggles = document.querySelectorAll('.ecc-theme-toggle');
                if (toggles.length === 0) return;

                toggles.forEach(function(toggle) {
                    if (toggle.dataset.themeToggleReady === '1') return;
                    toggle.dataset.themeToggleReady = '1';

                    toggle.addEventListener('click', function () {
                        var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                        var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                        setTheme(newTheme);
                    });
                });

                function setTheme(theme) {
                    var safeTheme = theme === 'light' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', safeTheme);

                    try {
                        localStorage.setItem('ecc_user_theme', safeTheme);
                    } catch (error) {}

                    toggles.forEach(function(t) {
                        t.setAttribute('aria-label', safeTheme === 'light' ? 'Switch to dark mode' : 'Switch to light mode');
                        t.setAttribute('title', safeTheme === 'light' ? 'Switch to dark mode' : 'Switch to light mode');
                    });
                }

                setTheme(document.documentElement.getAttribute('data-theme') || 'light');
            }

            document.addEventListener('DOMContentLoaded', initThemeToggle);
            document.addEventListener('livewire:navigated', initThemeToggle);
        })();
    </script>

    @vite(['resources/js/app.js'])
    @livewireScripts
    @stack('scripts')
    @includeIf('layouts.partials._overlay_cleanup')
    @livewire('vault.global-access-modal')
</body>
</html>
