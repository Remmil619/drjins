@extends('layouts.app')

@section('content')

<div class="hero text-center">
    <div class="container">
        <h1 class="fw-bold">Check Appointment Status</h1>
        <p class="mb-0">Enter your appointment reference number below.</p>
    </div>
</div>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">

            <div class="card booking-card">
                <div class="card-body p-4">
                    <form action="{{ route('booking.status') }}" method="GET">
                        <label class="form-label">Appointment Reference Number</label>

                        <div class="input-group">
                            <input type="text" name="appointment_no" class="form-control" value="{{ request('appointment_no') }}" placeholder="Example: APT-20260709-ABCDE">
                            <button class="btn btn-primary" type="submit">Check</button>
                        </div>
                    </form>

                    @if (request('appointment_no') && !$appointment)
                        <div class="alert alert-danger mt-4 mb-0">
                            No appointment found using that reference number.
                        </div>
                    @endif

                    @if ($appointment)
                        <div class="alert alert-info mt-4">
                            <strong>Appointment No.:</strong> {{ $appointment->appointment_no }}<br>
                            <strong>Guardian:</strong> {{ $appointment->guardian_name }}<br>
                            <strong>Child:</strong> {{ $appointment->child_name }}<br>
                            <strong>Date:</strong> {{ date('F d, Y', strtotime($appointment->appointment_date)) }}<br>
                            <strong>Time:</strong> {{ date('h:i A', strtotime($appointment->appointment_time)) }}<br>
                            <strong>Status:</strong> {{ $appointment->status == 'Approved' ? 'Booked' : $appointment->status }}
                        </div>

                        @if ($appointment->staff_response)
                            <div class="alert alert-secondary">
                                <strong>Clinic Response:</strong><br>
                                {{ $appointment->staff_response }}
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                The clinic has not responded yet. Please check again later.
                            </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@endsection