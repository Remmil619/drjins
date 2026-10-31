@extends('layouts.app')
@section('title', 'Plan your visit')
@section('content')
<section class="home-hero photo-hero"><div class="container"><div class="hero-copy">
<span class="eyebrow">DR. JN'S CHILDREN'S CLINIC &middot; ABUYOG, LEYTE</span>
<h1>Little smiles.<br><em>Brighter tomorrows.</em></h1>
<p class="intro">Make time for your child's health. A caring clinic visit starts with a simple appointment.</p>
<div class="hero-actions"><a class="btn btn-primary" href="{{ route('booking.create') }}">Book an appointment &rarr;</a><a class="btn btn-outline-primary" href="{{ route('booking.status') }}">Check appointment</a></div>
<div class="hero-note"><span>&#10003; No account needed</span><span>&#10003; No online payment</span></div>
</div></div></section>
<section class="section-space home-services" id="services"><div class="container"><div class="services-heading"><div><span class="eyebrow">OUR SERVICES &amp; SUPPORT</span><h2>Care for your child.<br>Support for your family.</h2><p>From a child checkup to planning your next appointment, we're here to help you take the next step.</p></div><a class="btn btn-outline-primary" href="{{ route('services') }}">Explore our services &rarr;</a></div>@include('partials.services')</div></section>
<section class="section-space soft-section"><div class="container"><div class="section-heading"><span class="eyebrow">HERE TO HELP</span><h2>Everything for your next visit.</h2></div><div class="row g-4">
    <div class="col-md-4"><a class="action-card" href="{{ route('booking.create') }}"><span class="icon-box" aria-hidden="true">+</span><h3>Book a checkup</h3><p>Pick an available date and time for your child.</p><span class="action-label">Choose a schedule &rarr;</span></a></div>
    <div class="col-md-4"><a class="action-card" href="{{ route('booking.status') }}"><span class="icon-box" aria-hidden="true">&#10003;</span><h3>Track your appointment</h3><p>View your visit details and updates from the clinic.</p><span class="action-label">Check your booking &rarr;</span></a></div>
    <div class="col-md-4"><a class="action-card" href="{{ route('contact') }}"><span class="icon-box" aria-hidden="true">&#8599;</span><h3>Talk to the clinic</h3><p>Find clinic hours, get directions, or send an inquiry.</p><span class="action-label">Contact the clinic &rarr;</span></a></div>
</div></div></section>
<section class="family-stories section-space"><div class="container">
<div class="section-heading"><span class="eyebrow">GROWING WELL, TOGETHER</span><h2>For every little milestone.</h2><p>Make your child's next visit part of a healthier tomorrow.</p></div>
<div class="row g-4">
<div class="col-lg-7"><article class="care-story story-smiles"><div class="story-copy"><span class="eyebrow">LITTLE MOMENTS. BIG SMILES.</span><h3>More moments to smile about.</h3><p>Take the next step in caring for your child's health. Find a schedule that works for your family.</p><a class="btn btn-light" href="{{ route('booking.create') }}">Plan your child's visit &rarr;</a></div></article></div>
<div class="col-lg-5"><article class="care-story story-wellbeing"><div class="story-copy"><span class="eyebrow">CARE STARTS WITH PREPARATION</span><h3>A little preparation.<br>A more comfortable visit.</h3><p>Know what to bring and what to expect before you arrive.</p><a class="btn btn-light" href="{{ route('patient-hub') }}">Explore the visit guide &rarr;</a></div></article></div>
</div></div></section>
<section class="section-space soft-section"><div class="container"><div class="row g-5"><div class="col-lg-5"><span class="eyebrow">BEFORE YOU ARRIVE</span><h2>A smoother visit<br>starts here.</h2><p class="mt-3">Bring your child's relevant health records and arrive 10 minutes before your appointment.</p><a class="btn btn-outline-primary mt-2" href="{{ route('patient-hub') }}">Read the visit guide &rarr;</a></div><div class="col-lg-7">@include('partials.faq')</div></div></div></section>
@include('partials.booking-cta')
@endsection
