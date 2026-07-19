@extends('layouts.app')

@section('content')

<div class="page-header">
    <div class="container">
        <h1 class="fw-bold">Clinic Dashboard</h1>
        <p class="mb-0">Patient records management and appointment monitoring</p>
    </div>
</div>

<div class="container">

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <h5 class="fw-bold mb-3">Appointment Summary</h5>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card clinic-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Appointments</h6>
                    <h2 class="fw-bold">{{ $totalAppointments }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card clinic-card">
                <div class="card-body">
                    <h6 class="text-muted">Appointments Today</h6>
                    <h2 class="fw-bold text-primary">{{ $todayAppointments }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card clinic-card">
                <div class="card-body">
                    <h6 class="text-muted">Pending</h6>
                    <h2 class="fw-bold text-warning">{{ $pendingAppointments }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card clinic-card">
                <div class="card-body">
                    <h6 class="text-muted">Approved</h6>
                    <h2 class="fw-bold text-success">{{ $approvedAppointments }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card clinic-card mt-4">
        <div class="card-body">
            <h4 class="fw-bold mb-3">System Modules</h4>

            <div class="row g-3">
                <div class="col-md-4">
                    <a href="{{ route('appointments.index') }}" class="btn btn-outline-primary w-100 py-3">
                        Appointments
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('patients.index') }}" class="btn btn-outline-primary w-100 py-3">
                        Patient Records
                    </a>
                </div>

                <div class="col-md-4">
                    <button class="btn btn-outline-secondary w-100 py-3" disabled>
                        Consultation Records
                    </button>
                </div>
            </div>

            <p class="text-muted mt-3 mb-0">
                Consultation, prescription, and immunization modules will be added in the next phase.
            </p>
        </div>
    </div>

</div>

@endsection
