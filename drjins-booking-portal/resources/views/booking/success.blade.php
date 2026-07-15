@extends('layouts.app')

@section('content')

<div class="hero text-center">
    <div class="container">
        <h1 class="fw-bold">Appointment Submitted</h1>
        <p class="mb-0">Please wait for clinic staff confirmation.</p>
    </div>
</div>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card booking-card text-center">
                <div class="card-body p-5">
                    <h3 class="fw-bold text-success">Thank you!</h3>

                    <p class="text-muted">
                        Your appointment request has been submitted successfully.
                    </p>

                    <div class="alert alert-primary">
                        <strong>Appointment Reference Number</strong>
                        <div class="fs-3 fw-bold mt-2">{{ $appointment->appointment_no }}</div>
                    </div>

                    <div class="status-box bg-light mb-4">
                        <p class="mb-1">
                            <strong>Child:</strong> {{ $appointment->child_name }}
                        </p>

                        <p class="mb-1">
                            <strong>Preferred Date:</strong> {{ date('F d, Y', strtotime($appointment->appointment_date)) }}
                        </p>

                        <p class="mb-1">
                            <strong>Preferred Time:</strong> {{ date('h:i A', strtotime($appointment->appointment_time)) }}
                        </p>

                        <p class="mb-0">
                            <strong>Status:</strong> {{ $appointment->status == 'Approved' ? 'Booked' : $appointment->status }}
                        </p>
                    </div>

                    <p class="text-muted">
                        Save your reference number. You can use it to check the status of your appointment.
                    </p>

                    <a href="{{ route('booking.status') }}" class="btn btn-book">
                        Check Appointment Status
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection