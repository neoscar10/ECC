<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'ECC') }}</title>

    {{-- Fonts + Icons (match stitched mobile design style) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@200..700&display=swap" rel="stylesheet">

   
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        {{-- Theme styles for ECC user web (dark + gold) --}}
        @include('layouts.user.partials.styles')

        <style>
            :root {
                --ecc-page-max: 1100px;
                --ecc-sidebar-w: 260px;
            }
            .ecc-app-shell {
                background: var(--ecc-bg);
            }
            .ecc-content {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            .ecc-main-inner {
                width: 100%;
                max-width: var(--ecc-page-max);
                margin: 0 auto;
                padding-bottom: 120px; /* Space for footer */
            }
            @media (min-width: 768px) {
                .ecc-sidebar-aside {
                    width: var(--ecc-sidebar-w);
                    position: fixed;
                    left: 0;
                    top: 70px;
                    height: calc(100vh - 70px);
                    overflow-y: auto;
                    z-index: 20;
                }
                .ecc-content {
                    margin-left: var(--ecc-sidebar-w);
                    width: calc(100% - var(--ecc-sidebar-w));
                }
            }
        </style>

        @livewireStyles
        @stack('styles')
    </head>

    <body class="ecc-user-body h-100">
        <div class="ecc-app-shell d-flex flex-column min-vh-100 overflow-x-hidden">

            {{-- Topbar (fixed) --}}
            <header class="ecc-topbar position-sticky top-0 z-3">
                <div class="container-fluid px-3 px-md-4">
                    @include('layouts.user.partials.topbar', [
                        'title' => $title ?? null,
                        'backUrl' => $backUrl ?? null,
                        'cartCount' => $cartCount ?? null,
                        'cartUrl' => $cartUrl ?? url('/cart'),
                    ])
                </div>
            </header>

            {{-- Main layout: Sidebar on md+ + Content --}}
            <div class="flex-grow-1">
                <div class="container-fluid px-0 h-100">
                    <div class="d-flex h-100">
                        {{-- Desktop sidebar --}}
                        <aside class="ecc-sidebar-aside d-none d-md-block flex-shrink-0">
                            @include('layouts.user.partials.app-sidebar-nav', [
                                'active' => $activeNav ?? null
                            ])
                        </aside>

                        {{-- Content --}}
                        <main class="flex-grow-1 ecc-content overflow-auto">
                            <div class="ecc-main-inner px-3 px-md-4 py-4">
                                {{ $slot ?? '' }}
                                @yield('content')
                            </div>
                        </main>
                    </div>
                </div>
            </div>

            @if(empty($hideBottomNav))
                @include('layouts.user.partials.app-bottom-nav', [
                    'active' => $activeNav ?? null
                ])
            @endif
        </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @livewireScripts
    @stack('scripts')
    @include('layouts.partials._overlay_cleanup')
</body>
</html>
