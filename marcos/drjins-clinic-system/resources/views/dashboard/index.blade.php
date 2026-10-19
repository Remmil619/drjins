@extends('layouts.app')

@section('content')
<section class="page-header">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1>Dashboard</h1>
            <p class="mb-0">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('patients.create') }}">Register patient</a>
    </div>
</section>

<div class="container">
    <div class="row g-3 mb-4">
        @foreach([["Today's appointments", $todayAppointments], ['Pending review', $pendingAppointments], ['Registered patients', $registeredPatients]] as [$label, $value])
            <div class="col-12 col-sm-4">
                <div class="card clinic-card h-100">
                    <div class="card-body p-4">
                        <p class="text-muted small mb-2">{{ $label }}</p>
                        <div class="metric-value">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
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
                        <tr><th>Time</th><th>Patient</th><th>Reference</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td>
                                <td>
                                    <strong>{{ $appointment->child_name }}</strong>
                                    <small class="d-block text-muted">{{ $appointment->guardian_name }}</small>
                                </td>
                                <td>{{ $appointment->appointment_no }}</td>
                                <td><span class="badge text-bg-light">{{ $appointment->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-5 text-muted">No appointments scheduled today.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
