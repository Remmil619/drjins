@extends('layouts.app')

@section('content')

<div class="page-header">
    <div class="container">
        <h1 class="fw-bold">Patient Records</h1>
        <p class="mb-0">Manage clinic patient information and guardian details.</p>
    </div>
</div>

<div class="container">

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card clinic-card">
        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="fw-bold mb-0">Patients</h4>
                    <small class="text-muted">Search and view registered patients.</small>
                </div>

                <a href="{{ route('patients.create') }}" class="btn btn-clinic">
                    Add New Patient
                </a>
            </div>

            <form action="{{ route('patients.index') }}" method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by patient no., name, guardian, or contact number" value="{{ $search }}">
                    <button class="btn btn-primary" type="submit">Search</button>
                    <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Patient No.</th>
                            <th>Patient Name</th>
                            <th>Age</th>
                            <th>Sex</th>
                            <th>Guardian</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patients as $patient)
                            <tr>
                                <td>{{ $patient->patient_no }}</td>
                                <td>{{ $patient->full_name }}</td>
                                <td>{{ $patient->age_label }}</td>
                                <td>{{ $patient->sex }}</td>
                                <td>{{ $patient->guardian_name }}</td>
                                <td>{{ $patient->contact_number }}</td>
                                <td>
                                    @if ($patient->status == 'Active')
                                        <span class="status-badge status-approved">Active</span>
                                    @else
                                        <span class="status-badge status-declined">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('patients.show', $patient->id) }}" class="btn btn-sm btn-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No patient records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $patients->links() }}
            </div>

        </div>
    </div>
</div>

@endsection
