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

<body class="staff-workspace" data-theme="{{ \App\Models\ClinicSetting::theme() }}">
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

    <div class="confirmation-overlay" id="confirmationCard" hidden>
        <section class="confirmation-card" role="dialog" aria-modal="true" aria-labelledby="confirmationCardTitle" aria-describedby="confirmationCardMessage">
                <div class="confirmation-icon" aria-hidden="true">?</div>
                <h2 class="h4 mb-2" id="confirmationCardTitle">Please confirm</h2>
                <p class="text-muted mb-4" id="confirmationCardMessage">Are you sure you want to continue?</p>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-confirm-cancel>Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmationCardAccept">Confirm</button>
                </div>
        </section>
    </div>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>
    <script src="{{ asset('js/uppercase-inputs.js') }}" defer></script>
    <script src="{{ asset('js/auto-filters.js') }}?v={{ filemtime(public_path('js/auto-filters.js')) }}" defer></script>
    <script src="{{ asset('js/date-format.js') }}?v={{ filemtime(public_path('js/date-format.js')) }}" defer></script>
    <script src="{{ asset('js/confirmation-card.js') }}?v={{ filemtime(public_path('js/confirmation-card.js')) }}" defer></script>
</body>

</html>
