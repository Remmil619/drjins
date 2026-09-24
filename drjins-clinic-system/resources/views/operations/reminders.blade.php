@extends('layouts.app')
@section('content')
<section class="page-header"><div class="container"><span class="eyebrow">CONTINUITY OF CARE</span><h1>Follow-ups & vaccine reminders</h1><p class="mb-0">Staff worklist for recorded due dates. Contact families and record the outcome.</p></div></section>
<div class="container">@include('components.feedback')
<form method="GET" class="d-flex flex-wrap gap-3 align-items-end mb-4"><div><label for="through" class="form-label">Show open reminders due on or before</label><input id="through" class="form-control" type="date" name="through" value="{{ $through }}"></div><button class="btn btn-primary">Update list</button></form>
<p class="small text-muted">This worklist does not send SMS or reserve appointment slots. Closing a reminder records an administrative outcome; it does not record a consultation or vaccine dose.</p>
@foreach(['consultation'=>['Consultation follow-ups',$consultations,'follow_up_date'],'immunization'=>['Vaccination reminders',$vaccines,'next_due_on']] as $type=>[$title,$items,$dateField])
<div class="card clinic-card mb-4"><div class="card-body p-4"><h2 class="h5">{{ $title }}</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Due date</th><th>Patient / guardian</th><th>Details</th><th>Outcome</th></tr></thead><tbody>
@forelse($items as $entry)<tr><td><span class="{{ $entry->$dateField<today()->toDateString()?'text-danger fw-bold':'' }}">{{ $entry->$dateField }}</span></td><td><a href="{{ route('patients.show',[$entry->patient,'tab'=>$type==='consultation'?'consultations':'immunizations']) }}">{{ $entry->patient->full_name }}</a><small class="d-block">{{ $entry->patient->guardian_name }}</small><small>{{ $entry->patient->contact_number }}</small></td><td>{{ $type==='consultation'?'Review after '.$entry->visit_date:$entry->vaccine.' · next dose after '.$entry->dose }}</td><td><form method="POST" action="{{ route('reminders.close',[$type,$entry->id]) }}">@csrf<label for="outcome-{{ $type }}-{{ $entry->id }}" class="visually-hidden">Contact or follow-up outcome</label><input id="outcome-{{ $type }}-{{ $entry->id }}" name="outcome" class="form-control mb-2" required minlength="5" maxlength="255" placeholder="Record outcome"><button class="btn btn-sm btn-outline-primary">Close reminder</button></form></td></tr>
@empty<tr><td colspan="4" class="text-center text-muted py-4">No open reminders in this period.</td></tr>@endforelse
</tbody></table></div>{{ $items->links() }}</div></div>@endforeach
</div>
@endsection
