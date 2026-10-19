@extends('layouts.app')
@section('title', 'Check appointment status')
@section('content')
<div class="hero text-center"><div class="container"><span class="eyebrow">YOUR VISIT, AT A GLANCE</span><h1>Check appointment status</h1><p>Use your child's full name and the mobile number you provided when booking.</p></div></div>
<div class="container mt-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-7"><div class="card booking-card"><div class="card-body p-4 p-md-5">
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('booking.status') }}" method="POST">@csrf
<div class="mb-3"><label class="form-label" for="child_name">Child's full name</label><input id="child_name" name="child_name" class="form-control" value="{{ old('child_name', $lookup['child_name'] ?? request('child_name')) }}" placeholder="Full name used when booking" maxlength="255" required></div>
<div class="mb-4"><label class="form-label" for="contact_number">Booking mobile number</label><input id="contact_number" type="tel" name="contact_number" class="form-control" value="{{ old('contact_number', $lookup['contact_number'] ?? '') }}" placeholder="09XXXXXXXXX" autocomplete="tel" required></div>
<button class="btn btn-primary w-100" type="submit">Check appointment</button></form>
@if(request()->isMethod('post') && $appointments->isEmpty())<div class="alert alert-warning mt-4 mb-0" role="status">No matching appointment found. Check both the child's full name and booking mobile number.</div>@endif
@foreach($appointments as $appointment)<div class="status-box mt-4" style="background:var(--mint)" role="status"><span class="badge text-bg-success mb-3">{{ $appointment->status === 'Approved' ? 'Booked' : $appointment->status }}</span><h2 class="fs-5">{{ $appointment->child_name }}</h2><dl class="row mt-3 mb-0"><dt class="col-sm-4">Guardian</dt><dd class="col-sm-8">{{ $appointment->guardian_name }}</dd><dt class="col-sm-4">Child</dt><dd class="col-sm-8">{{ $appointment->child_name }}</dd><dt class="col-sm-4">Date</dt><dd class="col-sm-8">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') }}</dd><dt class="col-sm-4">Time</dt><dd class="col-sm-8">{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</dd></dl></div>@if($appointment->staff_response)<div class="clinic-note mt-3"><strong>Clinic response</strong><p class="mb-0 mt-2">{{ $appointment->staff_response }}</p></div>@endif
@endforeach

<p class="small mt-4 mb-0">Need to change your booking? <a href="tel:{{ config('clinic.phone') }}">Call the clinic</a> with your child's full name and appointment date.</p>
</div></div></div></div></div>
@endsection
