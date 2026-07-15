@extends('layouts.app')

@section('content')

<div class="page-header text-center">
    <div class="container">
        <h1 class="fw-bold">Appointment Submitted</h1>
        <p class="mb-0">Please wait for clinic confirmation.</p>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card clinic-card text-center">
                <div class="card-body p-5">
                    <h3 class="fw-bold text-success">Thank you!</h3>
                    <p class="text-muted">
                        Your appointment request has been successfully submitted.
                    </p>

                    <div class="alert alert-primary">
                        <strong>Appointment Reference No.:</strong><br>
                        <span class="fs-4">{{ $appointment_no }}</span>
                    </div>

                    <p>
                        Please save your reference number. The clinic staff will review your appointment request.
                    </p>

                    <a href="{{ route('booking.create') }}" class="btn btn-clinic">
                        Book Another Appointment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection