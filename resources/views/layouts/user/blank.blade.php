<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'ECC') }}</title>

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

    {{-- Fonts + Icons (match premium designs) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Noto+Sans:ital,wght@0,100..900;1,100..900&family=Manrope:wght@200..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    {{-- Bootstrap 5 (if project already includes Bootstrap globally, this may be duplicate but harmless; keep for layout isolation) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- If this partial exists from previous work, keep using it. If it does NOT exist, create it with the code in section 1B below. --}}
    @includeIf('layouts.user.partials.styles')

    @livewireStyles
    @stack('styles')
</head>

<body class="ecc-user-body h-100" style="font-family: 'Manrope', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;">
    {{ $slot ?? '' }}
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
    @stack('scripts')
</body>
</html>
