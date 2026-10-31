@extends('layouts.app')

@section('content')

    <section class="page-header">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1>Dashboard</h1>
                <p class="mb-0">{{ now()->format('l, d M Y') }}</p>
            </div>
            <a class="btn btn-primary" href="{{ route('patients.create') }}">Register patient</a>
        </div>
    </section>
    <div class="container">
        <div class="row g-3 mb-4">
            @foreach ([["Today's appointments", $todayAppointments], ['Patients in queue', $patientsInQueue], ['Registered patients', $registeredPatients]] as [$label, $value])
                <div class="col-12 col-sm-4">
                    <div class="card clinic-card metric-card h-100">
                        <div class="card-body p-4">
                            <p class="text-muted small mb-2">{{ $label }}</p>
                            <div class="metric-value">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="workspace-actions" aria-label="Daily tasks">
            <a class="workspace-action" href="{{ route('appointments.index') }}">
                <span class="workspace-action-icon" aria-hidden="true">&#9783;</span>
                <span>
                    <strong>Manage appointments</strong>
                    <small>Review bookings and
                        attendance</small>
                </span>
            </a>
            <a class="workspace-action" href="{{ route('patients.index') }}">
                <span class="workspace-action-icon" aria-hidden="true">&#43;</span>
                <span>
                    <strong>Find a patient</strong>
                    <small>Open records and
                        consultations</small>
                </span>
            </a>
            <a class="workspace-action" href="{{ route('queue.index') }}">
                <span class="workspace-action-icon" aria-hidden="true">&#8594;</span>
                <span>
                    <strong>Today's queue</strong>
                    <small>Check in and manage waiting
                        patients</small>
                </span>
            </a>
        </div>
        <div class="card clinic-card">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h5 mb-0">Today's appointments</h2>
                    <a href="{{ route('appointments.index') }}">View all appointments &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appointments as $appointment)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td>
                                    <td>
                                        <strong>{{ $appointment->child_name }}</strong>
                                        <small class="d-block text-muted">{{ $appointment->guardian_name }}</small>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light">{{ $appointment->status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">No appointments scheduled today.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
