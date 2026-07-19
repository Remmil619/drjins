@extends('layouts.app')

@section('content')

<div class="page-header text-center">
    <div class="container">
        <h1 class="fw-bold">Book an Appointment</h1>
        <p class="mb-0">Dr. Jn's Childer Clinic, Abuyog, Leyte</p>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please check the form.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card clinic-card">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-3">Appointment Request Form</h4>
                    <p class="text-muted">
                        Fill out the form below. Your appointment will be reviewed by the clinic staff.
                    </p>

                    <form action="{{ route('booking.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Guardian Name</label>
                            <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Child's Name</label>
                            <input type="text" name="child_name" class="form-control" value="{{ old('child_name') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number') }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preferred Date</label>
                                <input type="date" name="appointment_date" class="form-control" value="{{ old('appointment_date') }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preferred Time</label>
                                <input type="time" name="appointment_time" class="form-control" value="{{ old('appointment_time') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Visit</label>
                            <textarea name="reason_for_visit" class="form-control" rows="4">{{ old('reason_for_visit') }}</textarea>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="consent" value="1" required>
                            <label class="form-check-label">
                                I consent to the collection of the provided information for appointment processing purposes only.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-clinic w-100">
                            Submit Appointment Request
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
