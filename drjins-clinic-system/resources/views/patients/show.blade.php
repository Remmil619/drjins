@extends('layouts.app')

@section('content')

<div class="page-header">
    <div class="container">
        <h1 class="fw-bold">Patient Profile</h1>
        <p class="mb-0">View child patient information and clinic record summary.</p>
    </div>
</div>

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">
            Back to Patients
        </a>
    </div>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card clinic-card">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 90px; height: 90px; font-size: 32px;">
                        {{ strtoupper(substr($patient->first_name, 0, 1)) }}{{ strtoupper(substr($patient->last_name, 0, 1)) }}
                    </div>

                    <h4 class="fw-bold mb-1">{{ $patient->full_name }}</h4>
                    <p class="text-muted mb-2">{{ $patient->patient_no }}</p>

                    @if ($patient->status == 'Active')
                        <span class="status-badge status-approved">Active</span>
                    @else
                        <span class="status-badge status-declined">Inactive</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card clinic-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Basic Information</h5>

                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Full Name</div>
                        <div class="col-md-8 fw-semibold">{{ $patient->full_name }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Birthdate</div>
                        <div class="col-md-8 fw-semibold">{{ date('F d, Y', strtotime($patient->birthdate)) }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Age</div>
                        <div class="col-md-8 fw-semibold">{{ $patient->age }} years old</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Sex</div>
                        <div class="col-md-8 fw-semibold">{{ $patient->sex }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Address</div>
                        <div class="col-md-8 fw-semibold">{{ $patient->address ?? 'Not provided' }}</div>
                    </div>
                </div>
            </div>

            <div class="card clinic-card mt-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Guardian Information</h5>

                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Guardian Name</div>
                        <div class="col-md-8 fw-semibold">{{ $patient->guardian_name }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Relationship</div>
                        <div class="col-md-8 fw-semibold">{{ $patient->guardian_relationship ?? 'Not provided' }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Contact Number</div>
                        <div class="col-md-8 fw-semibold">{{ $patient->contact_number }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 text-muted">Emergency Contact</div>
                        <div class="col-md-8 fw-semibold">{{ $patient->emergency_contact ?? 'Not provided' }}</div>
                    </div>
                </div>
            </div>

            <div class="card clinic-card mt-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Medical Records</h5>

                    <div class="alert alert-info mb-0">
                        Consultation records, prescription records, and immunization records will be added in the next phase.
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection