<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? "Dr. Jn's Children's Clinic" }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#147d78">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Dr. Jn Clinic">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/clinic-theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}" rel="stylesheet">
</head>

<body>
    <a href="#main-content" class="skip-link">Skip to content</a>
    <div class="utility-bar">
        <div class="container">Care close to home. Abuyog, Leyte <span class="float-end">Staff workspace</span>
        </div>
    </div>

    @include('layouts.partials.navigation')

    <main id="main-content">
        @yield('content')
    </main>
    <footer class="text-center py-4 mt-5 text-muted">
        <small>
            &copy; {{ date('Y') }} Dr. Jn's Children's Clinic, Abuyog, Leyte
        </small>
    </footer>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>
    <script src="{{ asset('js/uppercase-inputs.js') }}" defer></script>
</body>

</html>
