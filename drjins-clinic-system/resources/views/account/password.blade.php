@extends('layouts.app')

@section('content')
<section class="page-header"><div class="container"><span class="eyebrow">ACCOUNT</span><h1>Change password</h1><p class="mb-0">Update the password for your clinic account.</p></div></section>
<div class="container"><div class="card clinic-card mx-auto" style="max-width: 680px"><div class="card-body p-4">
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('password.update') }}">@csrf @method('PUT')
        <div class="mb-3"><label for="current_password" class="form-label">Current password</label><input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" required></div>
        <div class="mb-3"><label for="password" class="form-label">New password</label><input id="password" name="password" type="password" class="form-control" minlength="8" autocomplete="new-password" required><div class="form-text">Use at least 8 characters.</div></div>
        <div class="mb-4"><label for="password_confirmation" class="form-label">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="8" autocomplete="new-password" required></div>
        <button class="btn btn-primary">Update password</button>
    </form>
</div></div></div>
@endsection
