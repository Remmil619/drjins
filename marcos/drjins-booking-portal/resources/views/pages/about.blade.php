@extends('layouts.app')
@section('title', 'About the clinic')
@section('content')
<section class="page-hero text-center"><div class="container"><span class="eyebrow">RIGHT HERE IN ABUYOG</span><h1>A clinic for your little ones.</h1><p>{{ config('clinic.name') }} welcomes families for children's checkups in Abuyog, Leyte.</p></div></section>
<section class="section-space"><div class="container"><div class="row align-items-center g-5"><div class="col-md-6"><img src="{{ asset('images/clinic-care.svg') }}" class="hero-art" width="600" height="480" alt="Doctor and child clinic illustration"></div><div class="col-md-6"><span class="eyebrow">LESS TIME PLANNING. MORE TIME TOGETHER.</span><h2>Your visit starts here.</h2><p class="mt-4">Our booking website helps parents and guardians find an available appointment, share the reason for their child's visit, and check updates from the clinic.</p><p>For questions about a service, your appointment, or the clinic location, our contact page makes it easy to get in touch.</p><a class="btn btn-primary" href="{{ route('contact') }}">Get in touch &rarr;</a></div></div></div></section>
@endsection
