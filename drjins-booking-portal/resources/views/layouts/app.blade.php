<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Book your child's clinic visit in Abuyog, Leyte and track appointments online.">
<title>@yield('title', 'Plan your visit') | {{ config('clinic.name') }}</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/clinic.css') }}" rel="stylesheet">
<link href="{{ asset('css/portal-refresh.css') }}?v={{ filemtime(public_path('css/portal-refresh.css')) }}" rel="stylesheet">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="utility-bar"><div class="container d-flex justify-content-between gap-3"><span>Care for little ones. Support for families.</span><span class="d-none d-sm-inline">Abuyog, Leyte &middot; Philippines</span></div></div>
<header class="site-header"><nav class="navbar navbar-expand-xl container" aria-label="Main navigation">
<a class="clinic-brand" href="{{ route('home') }}"><span class="brand-symbol" aria-hidden="true">+</span><span>Dr. Jn's<small>CHILDREN'S CLINIC</small></span></a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle menu"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="mainNavigation"><ul class="navbar-nav ms-auto align-items-xl-center">
@foreach(['home' => 'Home', 'booking.status' => 'Track appointment', 'patient-hub' => 'Visit guide', 'contact' => 'Contact'] as $page => $label)
<li class="nav-item"><a class="nav-link {{ request()->routeIs($page) ? 'active' : '' }}" @if(request()->routeIs($page)) aria-current="page" @endif href="{{ route($page) }}">{{ $label }}</a></li>
@endforeach
</ul><div class="header-actions"><a class="btn btn-primary" href="{{ route('booking.create') }}">Book Now &rarr;</a></div></div>
</nav></header>
<main id="main-content" tabindex="-1">@yield('content')</main>
<footer class="site-footer"><div class="container"><div class="footer-main"><div><a class="clinic-brand" href="{{ route('home') }}"><span class="brand-symbol" aria-hidden="true">+</span><span>Dr. Jn's<small>CHILDREN'S CLINIC</small></span></a><p class="mt-3 mb-0">{{ config('clinic.address') }}</p></div><nav class="footer-links" aria-label="Clinic information"><a href="{{ route('about') }}">About the clinic</a><a href="{{ route('services') }}">Services</a><a href="{{ route('privacy') }}">Privacy notice</a>@if(config('clinic.phone'))<a href="tel:{{ preg_replace('/[^+0-9]/', '', config('clinic.phone')) }}">Call {{ config('clinic.phone') }}</a>@endif</nav></div><div class="footer-bottom"><span>&copy; {{ date('Y') }} {{ config('clinic.name') }}</span><span>For urgent concerns, contact the clinic directly.</span></div></div></footer>
@include('partials.clinic-welcome')
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/clinic-welcome.js') }}?v={{ filemtime(public_path('js/clinic-welcome.js')) }}" defer></script>
@stack('scripts')
<script src="{{ asset('js/uppercase-inputs.js') }}?v={{ filemtime(public_path('js/uppercase-inputs.js')) }}" defer></script>
</body></html>
