<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Book your child's clinic visit in Abuyog, Leyte and track appointments online.">
<title>@yield('title', 'Care for your little ones') | {{ config('clinic.name') }}</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/clinic.css') }}" rel="stylesheet">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="utility-bar"><div class="container d-flex justify-content-between gap-3"><span>Care close to home. <span class="d-none d-sm-inline">Abuyog, Leyte</span></span><a href="{{ route('booking.status') }}">Track your appointment &rarr;</a></div></div>
<header class="site-header"><nav class="navbar navbar-expand-xl container" aria-label="Main navigation">
<a class="clinic-brand" href="{{ route('home') }}"><span class="brand-symbol" aria-hidden="true">+</span><span>Dr. Jn's<small>CHILDREN'S CLINIC</small></span></a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle menu"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="mainNavigation"><ul class="navbar-nav ms-auto align-items-xl-center">
@foreach(['home' => 'Home', 'services' => 'Services', 'products' => 'Products'] as $page => $label)
<li class="nav-item"><a class="nav-link {{ request()->routeIs($page) ? 'active' : '' }}" @if(request()->routeIs($page)) aria-current="page" @endif href="{{ route($page) }}">{{ $label }}</a></li>
@endforeach
<li class="nav-item dropdown"><button class="nav-link dropdown-toggle {{ request()->routeIs('patient-hub', 'booking.*') ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">Patient Hub</button><ul class="dropdown-menu"><li><a class="dropdown-item" href="{{ route('patient-hub') }}">Visit guide & FAQs</a></li><li><a class="dropdown-item" href="{{ route('booking.status') }}">Check appointment status</a></li></ul></li>
@foreach(['contact' => 'Contact Us', 'about' => 'About Us'] as $page => $label)
<li class="nav-item"><a class="nav-link {{ request()->routeIs($page) ? 'active' : '' }}" @if(request()->routeIs($page)) aria-current="page" @endif href="{{ route($page) }}">{{ $label }}</a></li>
@endforeach
</ul><div class="header-actions">
@if(config('clinic.phone'))<a class="btn btn-call" href="tel:{{ preg_replace('/[^+0-9]/', '', config('clinic.phone')) }}">Call Now</a>@else<a class="btn btn-call" href="{{ route('contact') }}">Contact Clinic</a>@endif
<a class="btn btn-primary" href="{{ route('booking.create') }}">Book Now &rarr;</a>
</div></div></nav></header>
<main id="main-content" tabindex="-1">@yield('content')</main>
<section class="footer-cta"><div class="container d-flex flex-wrap align-items-center justify-content-between gap-4"><div><span class="eyebrow">LET'S PLAN YOUR VISIT</span><h2>A little planning. A smoother clinic day.</h2><p class="mb-0">Choose an available date and time that works for your family.</p></div><a class="btn btn-primary" href="{{ route('booking.create') }}">Book an appointment &rarr;</a></div></section>
<footer class="site-footer"><div class="container"><div class="row g-4 pb-5">
<div class="col-lg-4"><a class="clinic-brand" href="{{ route('home') }}"><span class="brand-symbol" aria-hidden="true">+</span><span>Dr. Jn's<small>CHILDREN'S CLINIC</small></span></a><p class="mt-3 footer-description">Helping families in Abuyog plan their children's clinic visits, one appointment at a time.</p></div>
<div class="col-6 col-lg-2"><h3>Explore</h3><a href="{{ route('home') }}">Home</a><a href="{{ route('services') }}">Services</a><a href="{{ route('products') }}">Products</a><a href="{{ route('about') }}">About the clinic</a></div>
<div class="col-6 col-lg-3"><h3>For families</h3><a href="{{ route('booking.create') }}">Book an appointment</a><a href="{{ route('booking.status') }}">Track appointment</a><a href="{{ route('patient-hub') }}">Visit guide & FAQs</a><a href="{{ route('privacy') }}">Privacy notice</a></div>
<div class="col-lg-3"><h3>Visit us</h3><p>{{ config('clinic.address') }}</p><a href="{{ route('contact') }}">Contact & booking hours &rarr;</a>@if(config('clinic.phone'))<a href="tel:{{ preg_replace('/[^+0-9]/', '', config('clinic.phone')) }}">{{ config('clinic.phone') }}</a>@endif</div>
</div><div class="footer-bottom"><span>&copy; {{ date('Y') }} {{ config('clinic.name') }}</span><span>Online appointments for growing families.</span></div></div></footer>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body></html>
