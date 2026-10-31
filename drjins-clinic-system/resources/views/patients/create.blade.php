@extends('layouts.app')

@section('content')

    <div class="page-header">
        <div class="container">
            <h1 class="fw-bold">{{ isset($patient) ? 'Edit Patient' : 'Add New Patient' }}</h1>
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

                <form action="{{ isset($patient) ? route('patients.update', $patient) : route('patients.store') }}"
                    method="POST"
                >
                    @csrf @if (isset($patient))
                        @method('PUT')
                    @endif

                    <p class="small text-muted mb-3"><span class="text-danger fw-bold" aria-hidden="true">*</span> Required fields</p>
                    <h5 class="fw-bold mb-3">Patient Information</h5>
                    <div class="mb-3">
                        <label for="patient-suffix" class="form-label">Suffix (optional)</label>
                        <input
                            id="patient-suffix"
                            name="suffix"
                            class="form-control"
                            maxlength="20"
                            value="{{ old('suffix', $patient->suffix ?? '') }}"
                            placeholder="Jr., III"
                        >
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name <span class="text-danger fw-bold" aria-hidden="true">*</span></label>
                            <input
                                type="text"
                                name="first_name"
                                class="form-control"
                                value="{{ old('first_name', $patient->first_name ?? null) }}"
                                required
                            >
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input
                                type="text"
                                name="middle_name"
                                class="form-control"
                                value="{{ old('middle_name', $patient->middle_name ?? null) }}"
                            >
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name <span class="text-danger fw-bold" aria-hidden="true">*</span></label>
                            <input
                                type="text"
                                name="last_name"
                                class="form-control"
                                value="{{ old('last_name', $patient->last_name ?? null) }}"
                                required
                            >
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Birthdate <span class="text-danger fw-bold" aria-hidden="true">*</span></label>
                            @php
                                $birthdateValue = old('birthdate', $patient->birthdate ?? '');
                                $birthdateIsValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdateValue)
                                    && checkdate((int) substr($birthdateValue, 5, 2), (int) substr($birthdateValue, 8, 2), (int) substr($birthdateValue, 0, 4));
                                $birthdateDisplayValue = $birthdateIsValid
                                    ? \Carbon\Carbon::createFromFormat('!Y-m-d', $birthdateValue)->format('d/m/Y')
                                    : (old('birthdate') !== null ? old('birthdate') : '');
                            @endphp
                            <input
                                type="text" placeholder="DD/MM/YYYY" inputmode="numeric" maxlength="10" pattern="[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}"
                                name="birthdate"
                                class="form-control"
                                value="{{ $birthdateDisplayValue }}"
                                required
                            >
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sex <span class="text-danger fw-bold" aria-hidden="true">*</span></label>
                            <select
                                name="sex"
                                class="form-select"
                                required
                            >
                                <option value="">Select Sex</option>
                                <option value="Male" {{ old('sex', $patient->sex ?? null) == 'Male' ? 'selected' : '' }}>
                                    Male</option>
                                <option value="Female"
                                    {{ old('sex', $patient->sex ?? null) == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Height (cm)</label>
                            <input
                                type="number"
                                name="height"
                                class="form-control @error('height') is-invalid @enderror"
                                value="{{ old('height', $patient->height ?? null) }}"
                                placeholder="Example: 100" min="0.01" max="300" step="0.01"
                            >

                            @error('height')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Weight (kg)</label>
                            <input
                                type="number"
                                name="weight"
                                class="form-control @error('weight') is-invalid @enderror"
                                value="{{ old('weight', $patient->weight ?? null) }}"
                                placeholder="Example: 20" min="0.01" max="500" step="0.01"
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
                            <label class="form-label">Status <span class="text-danger fw-bold" aria-hidden="true">*</span></label>
                            <select
                                name="status"
                                class="form-select"
                                required
                            >
                                <option value="Active"
                                    {{ old('status', $patient->status ?? null) == 'Active' ? 'selected' : '' }}
                                >Active
                                </option>
                                <option value="Inactive"
                                    {{ old('status', $patient->status ?? null) == 'Inactive' ? 'selected' : '' }}
                                >Inactive
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        @include('patients.registration-address')
                    </div>
                    <h5 class="fw-bold mb-3">Guardian Information</h5>
                    @if(isset($patient) && !$patient->guardian_first_name)
                        <p class="small text-muted">Previously recorded guardian: {{ $patient->guardian_name }}. Enter the name in separate fields to update it.</p>
                    @endif
                    <div class="row">
                        @foreach(['guardian_first_name' => 'First Name', 'guardian_middle_name' => 'Middle Name (optional)', 'guardian_surname' => 'Last Name'] as $field => $label)
                            <div class="col-md-4 mb-3">
                                <label for="{{ $field }}" class="form-label">{{ $label }}@if($field !== 'guardian_middle_name') <span class="text-danger fw-bold" aria-hidden="true">*</span>@endif</label>
                                <input id="{{ $field }}" name="{{ $field }}" class="form-control" maxlength="80" value="{{ old($field, $patient->$field ?? '') }}" @required($field !== 'guardian_middle_name')>
                            </div>
                        @endforeach
                        <div class="col-md-6 mb-3">
                            <label for="guardian_relationship" class="form-label">Relationship to Patient <span class="text-danger fw-bold" aria-hidden="true">*</span></label>
                            <select id="guardian_relationship" name="guardian_relationship" class="form-select" required>
                                <option value="">Choose one</option>
                                @php($relationships = ['Mother', 'Father', 'Legal guardian', 'Other relative', 'Other'])
                                @php($selectedRelationship = old('guardian_relationship', $patient->guardian_relationship ?? ''))
                                @if($selectedRelationship && !in_array($selectedRelationship, $relationships))
                                    <option selected value="{{ $selectedRelationship }}">{{ $selectedRelationship }}</option>
                                @endif
                                @foreach($relationships as $relationship)
                                    <option value="{{ $relationship }}" @selected($selectedRelationship === $relationship)>{{ $relationship }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_number" class="form-label">Contact Number <span class="text-danger fw-bold" aria-hidden="true">*</span></label>
                            <input id="contact_number" name="contact_number" type="tel" class="form-control" maxlength="20" value="{{ old('contact_number', $patient->contact_number ?? '') }}" required>
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
