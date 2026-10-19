@extends('layouts.app')

@section('content')

<div class="page-header">
    <div class="container">
        <h1 class="fw-bold">{{ isset($patient) ? "Edit Patient" : "Add New Patient" }}</h1>
        <p class="mb-0">Register a child patient into the clinic records.</p>
    </div>
</div>

<div class="container">
    <div class="card clinic-card">
        <div class="card-body p-4">

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

            <form action="{{ isset($patient) ? route('patients.update', $patient) : route('patients.store') }}" method="POST">
                @csrf @if(isset($patient)) @method('PUT') @endif

                <h5 class="fw-bold mb-3">Patient Information</h5>
<div class="mb-3"><label for="patient-suffix" class="form-label">Suffix (optional)</label><input id="patient-suffix" name="suffix" class="form-control" maxlength="20" value="{{ old('suffix',$patient->suffix ?? '') }}" placeholder="Jr., III"></div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $patient->first_name ?? null) }}" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $patient->middle_name ?? null) }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $patient->last_name ?? null) }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Birthdate</label>
                        <input type="date" name="birthdate" class="form-control" value="{{ old('birthdate', $patient->birthdate ?? null) }}" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Sex</label>
                        <select name="sex" class="form-select" required>
                            <option value="">Select Sex</option>
                            <option value="Male" {{ old('sex', $patient->sex ?? null) == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('sex', $patient->sex ?? null) == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Height (cm)</label>
                        <input 
                            type="text" 
                            name="height" 
                            class="form-control @error('height') is-invalid @enderror" 
                            value="{{ old('height', $patient->height ?? null) }}"
                            placeholder="Example: 100 cm"
                        >

                        @error('height')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Weight (kg)</label>
                        <input 
                            type="text" 
                            name="weight" 
                            class="form-control @error('weight') is-invalid @enderror" 
                            value="{{ old('weight', $patient->weight ?? null) }}"
                            placeholder="Example: 20 kg"
                        >

                        @error('weight')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Temperature (&deg;C)</label>
                        <input
                            type="number"
                            name="temperature"
                            class="form-control @error('temperature') is-invalid @enderror"
                            value="{{ old('temperature', $patient->temperature ?? null) }}"
                            min="25"
                            max="45"
                            step="0.1"
                            placeholder="Example: 36.5"
                        >

                        @error('temperature')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="Active" {{ old('status', $patient->status ?? null) == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ old('status', $patient->status ?? null) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    @include('patients.address-fields')
                </div>

                <h5 class="fw-bold mb-3">Guardian Information</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Guardian Name</label>
                        <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $patient->guardian_name ?? null) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Relationship to Patient</label>
                        <input type="text" name="guardian_relationship" class="form-control" value="{{ old('guardian_relationship', $patient->guardian_relationship ?? null) }}" placeholder="Mother, Father, Aunt, Guardian">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $patient->contact_number ?? null) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $patient->emergency_contact ?? null) }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-clinic">
                        Save Patient Record
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection


