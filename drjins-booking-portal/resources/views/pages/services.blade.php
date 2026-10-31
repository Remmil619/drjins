@extends('layouts.app')
@section('title', 'Services')
@section('content')
<section class="page-hero text-center"><div class="container"><span class="eyebrow">CARE FOR GROWING FAMILIES</span><h1>Our services</h1><p>Book a child checkup, or contact the clinic to ask about other care needs.</p></div></section>
<section class="section-space"><div class="container">@include('partials.services')<div class="clinic-note mt-4">Appointment fees and any additional services are confirmed directly by the clinic. Online booking does not require payment.</div></div></section>
@include('partials.booking-cta')
@endsection
