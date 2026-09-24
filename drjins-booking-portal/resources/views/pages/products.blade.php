@extends('layouts.app')
@section('title', 'Products')
@section('content')
<section class="page-hero text-center"><div class="container"><span class="eyebrow">ASK BEFORE YOUR VISIT</span><h1>Product inquiries</h1><p>Contact the clinic about product availability, pricing, and collection.</p></div></section>
<section class="section-space"><div class="container"><div class="row justify-content-center"><div class="col-lg-8"><div class="info-card text-center"><span class="icon-box mx-auto" aria-hidden="true">+</span><h2 class="mt-4">Looking for a particular product?</h2><p class="mt-3">The clinic has not published a product catalog yet. Send the product name in your inquiry or call us to confirm what is available.</p><div class="hero-actions justify-content-center"><a class="btn btn-primary" href="{{ route('contact', ['topic' => 'Product availability']) }}">Ask about a product &rarr;</a><a class="btn btn-outline-primary" href="tel:{{ config('clinic.phone') }}">Call the clinic</a></div></div></div></div></div></section>
@endsection
