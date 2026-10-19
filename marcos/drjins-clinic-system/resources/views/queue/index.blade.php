@extends('layouts.app')
@section('content')
<section class="page-header"><div class="container"><span class="eyebrow">FRONT DESK</span><h1>Today's clinic queue</h1><p class="mb-0">Check in online bookings and walk-in patients, then open their care record.</p></div></section>
<div class="container">@include('components.feedback')
<div class="row g-4"><div class="col-lg-4"><div class="card clinic-card"><div class="card-body p-4"><h2 class="h5">Check in a patient</h2>
<form method="GET" class="d-flex gap-2 mb-3"><label for="queue-search" class="visually-hidden">Find registered patient</label><input id="queue-search" class="form-control" name="search" value="{{ $search }}" placeholder="Name, patient no., or contact"><button class="btn btn-outline-primary">Find</button></form>
<p class="small text-muted">Showing up to 50 matching active patients. <a href="{{ route('patients.create') }}">Register a new patient</a> if needed.</p>
<form method="POST" action="{{ route('queue.store') }}">@csrf
<label for="queue-patient" class="form-label">Patient</label><select id="queue-patient" name="patient_id" class="form-select mb-3" required><option value="">Select verified patient</option>@foreach($patients as $patient)<option value="{{ $patient->id }}" @selected(old('patient_id')==$patient->id)>{{ $patient->full_name }} · {{ $patient->patient_no }} · {{ $patient->birthdate }}</option>@endforeach</select>
<label for="queue-booking" class="form-label">Online booking (optional)</label><select id="queue-booking" name="online_appointment_id" class="form-select mb-2"><option value="">Walk-in / no online booking</option>@foreach($appointments as $booking)<option value="{{ $booking->id }}" @selected(old('online_appointment_id')==$booking->id)>{{ $booking->appointment_time }} · {{ $booking->child_name }} · {{ $booking->appointment_no }}</option>@endforeach</select><p class="small text-muted">Confirm the child and guardian match before linking. Only approved bookings for today are listed.</p>
<label for="queue-reason" class="form-label">Reason for visit</label><textarea id="queue-reason" name="reason" class="form-control mb-3" required maxlength="1000">{{ old('reason') }}</textarea><button class="btn btn-primary w-100">Check in for today</button></form>
</div></div></div>
<div class="col-lg-8"><div class="card clinic-card"><div class="card-body p-4"><div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><h2 class="h5">Visit list</h2><form method="GET" class="d-flex gap-2"><label for="queue-date" class="visually-hidden">Visit date</label><input id="queue-date" type="date" class="form-control" name="date" value="{{ $date }}"><button class="btn btn-outline-primary">View</button></form></div>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Queue</th><th>Patient</th><th>Visit</th><th>Action</th></tr></thead><tbody>@forelse($entries as $entry)
<tr><td>#{{ $entry->id }}<small class="d-block text-muted">{{ $entry->created_at->format('g:i A') }}</small></td><td><a class="fw-semibold" href="{{ route('patients.show',$entry->patient) }}">{{ $entry->patient->full_name }}</a><small class="d-block">{{ $entry->patient->patient_no }}</small><small class="d-block text-muted">{{ $entry->appointment?->appointment_no ?? 'Walk-in' }}</small></td><td><span class="badge text-bg-light">{{ str_replace('_',' ',$entry->status) }}</span><p class="small mb-0">{{ $entry->reason }}</p></td><td>
@if($date===today()->toDateString())
@foreach(match($entry->status){'waiting'=>['in_consultation'=>'Start consultation','left'=>'Mark left'],'in_consultation'=>['waiting'=>'Return to waiting','left'=>'Mark left'],'left'=>['waiting'=>'Reopen check-in'],default=>[]} as $status=>$label)
<form method="POST" action="{{ route('queue.update',$entry) }}" class="mb-1">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $status }}"><button class="btn btn-sm btn-outline-primary">{{ $label }}</button></form>
@endforeach
@endif
<a class="small" href="{{ route('patients.show',$entry->patient) }}">Open record &rarr;</a></td></tr>
@empty<tr><td colspan="4" class="text-center py-5 text-muted">No patients checked in for this date.</td></tr>@endforelse</tbody></table></div>
<p class="text-muted small mb-0">Queue changes stay inside the clinic. Saving a consultation completes the queue entry and updates a linked online booking.</p>
</div></div></div></div></div>
@endsection
