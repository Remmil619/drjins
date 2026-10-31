@extends('layouts.app')

@section('content')
<section class="page-header"><div class="container"><span class="eyebrow">SYSTEM SETTINGS</span><h1>Doctors availability</h1><p class="mb-0">Mark dates when the clinic cannot accept online bookings.</p></div></section>
<div class="container" style="max-width: 900px">
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="card clinic-card p-4 mb-4">
        <h2 class="h5">Block a clinic date</h2>
        <p class="text-muted">The date will be marked unavailable in the online booking calendar. Existing appointments on that date are not cancelled.</p>
        <form method="POST" action="{{ route('settings.availability.store') }}" class="row g-3 align-items-end">@csrf
            <div class="col-md-5"><label for="unavailable-date" class="form-label">Date <span class="text-muted fw-normal">(DD/MM/YYYY)</span></label><input id="unavailable-date" name="date" type="date" min="{{ today()->toDateString() }}" class="form-control" required value="{{ old('date') }}"></div>
            <div class="col-md-5"><label for="unavailable-reason" class="form-label">Reason (optional)</label><input id="unavailable-reason" name="reason" class="form-control" maxlength="255" placeholder="For example, clinic closed"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Save date</button></div>
        </form>
    </section>
    <section class="card clinic-card p-4">
        <h2 class="h5">Unavailable dates</h2>
        <p class="small text-muted">Removing a date opens it for online booking again.</p>
        <div class="list-group list-group-flush">
            @forelse($unavailableDates as $entry)
                <div class="list-group-item d-flex justify-content-between align-items-center gap-3 flex-wrap px-0">
                    <div><strong>{{ $entry->date->format('d M Y') }}</strong>@if($entry->reason)<span class="text-muted"> · {{ $entry->reason }}</span>@endif</div>
                    <form method="POST" action="{{ route('settings.availability.destroy', $entry) }}" data-confirm-message="Make this date available for online bookings again?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-secondary">Make available</button></form>
                </div>
            @empty
                <p class="text-muted mb-0">No upcoming unavailable dates.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
