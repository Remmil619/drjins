<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? "Dr. Jn's Childer Clinic" }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Dr. Jin Clinic">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>

@include('layouts.partials.navigation')

<main>
    @yield('content')
</main>

<footer class="text-center py-4 mt-5 text-muted">
    <small>
        &copy; {{ date('Y') }} Dr. Jn's Childer Clinic, Abuyog, Leyte
    </small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js');
        });
    }
</script>
</body>
</html>
