@extends('layouts.app')
@section('title', 'Appointment details')

@section('content')

@if (session('booking_success') && $appointment->status === 'Approved')
    <div class="container mt-4">
        <div class="alert alert-success" id="bookingSuccessNotice" role="status" tabindex="-1">
            <strong>Booking successful!</strong> {{ session('booking_success') }}
        </div>
    </div>
@endif

<div class="hero text-center">
    <div class="container">
        <h1 class="fw-bold">{{ $appointment->status === 'Approved' ? 'Appointment Booked' : 'Appointment Details' }}</h1>
        <p class="mb-0">{{ $appointment->status === 'Approved' ? 'Your visit is scheduled. Please arrive 10 minutes early.' : 'See the latest appointment status and clinic response below.' }}</p>
    </div>
</div>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card booking-card text-center">
                <div class="card-body p-5">
                    <h3 class="fw-bold">{{ $appointment->status === 'Approved' ? 'Booking confirmed!' : $appointment->status }}</h3>

                    <p class="text-muted" role="status" aria-live="polite">
                        {{ $appointment->staff_response }}
                    </p>

                    <div class="alert alert-primary">
                        <strong>Child's full name</strong>
                        <div class="fs-3 fw-bold mt-2">{{ $appointment->child_name }}</div>
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
                        Use your child's full name and booking mobile number to check appointment status.
                    </p>

                    <a href="{{ route('booking.status', ['child_name' => $appointment->child_name]) }}" class="btn btn-book">
                        Check Appointment Status
                    </a>
                    
                </div>
            </div>
        </div>
    </div>
</div>

@if (session('booking_success') && $appointment->status === 'Approved')
<div class="modal fade" id="bookingConfirmation" tabindex="-1" aria-labelledby="bookingConfirmationTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fs-4 fw-bold text-success" id="bookingConfirmationTitle">Appointment confirmed!</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Your appointment for <strong>{{ $appointment->child_name }}</strong> was booked successfully.</p>
                <p class="mb-1">Child's full name</p>
                <p class="fs-5 fw-bold mb-0">{{ $appointment->child_name }}</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    window.addEventListener('DOMContentLoaded', function () {
        const confirmation = document.getElementById('bookingConfirmation');
        if (confirmation && window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(confirmation).show();
        }
    });
</script>
@endpush
