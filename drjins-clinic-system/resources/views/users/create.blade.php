@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div class="container">
            <h1 class="fw-bold">Register User</h1>
            <p class="mb-0">Create a clinic account for an administrator, doctor, or staff member.</p>
        </div>
    </div>
    <div class="container">
        <div class="card clinic-card mx-auto" style="max-width: 680px">
            <div class="card-body p-4">
                @if (session('success'))
                    <div class="alert alert-success" role="status">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Full name</label>
                        <input id="name" name="name" class="form-control" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" maxlength="255" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                            <option value="doctor" @selected(old('role') === 'doctor')>Doctor</option>
                            <option value="admin" @selected(old('role') === 'admin')>Administrator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" name="password" type="password" class="form-control" minlength="8" autocomplete="new-password" required>
                        <div class="form-text">Use at least 8 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-clinic">Register User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
