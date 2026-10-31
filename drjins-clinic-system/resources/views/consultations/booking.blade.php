@extends('layouts.app')
@section('content')
<section class="page-header"><div class="container"><h1>{{ $booking->child_name }}</h1><p>Online booking � {{ $booking->appointment_no }}</p></div></section>
<div class="container">@if(filled($booking->allergies))<div class="alert alert-warning"><strong>Reported allergies:</strong> {{ $booking->allergies }}</div>@endif<p>Complete the consultation to add this patient to Patients records.</p><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#consultation-wizard">Continue consultation</button> <a class="btn btn-outline-primary" href="{{ route('queue.index') }}">Back to queue</a></div>
@include('consultations.wizard')
@endsection
