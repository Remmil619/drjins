@extends('layouts.app')
@section('content')
<section class="page-header"><div class="container"><span class="eyebrow">PATIENT CARE</span><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h1>{{ $patient->full_name }}</h1><p class="mb-0">{{ $patient->patient_no }} &middot; {{ $patient->age_label }} &middot; {{ $patient->sex }} &middot; {{ $patient->status }}</p></div><div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('patients.edit',$patient) }}">Edit details</a></div></div></div></section>
<div class="container">
@include('components.feedback')
<div class="row g-4">
<aside class="col-lg-3"><div class="card clinic-card mb-3"><div class="card-body p-4"><h2 class="h6">Reference Details</h2><dl class="profile-details mb-0"><dt>Patient number</dt><dd>{{ $patient->patient_no }}</dd><dt>Booking reference</dt><dd>{{ $patient->online_appointment_no ?: 'Registered at clinic' }}</dd>@if($patient->appointment)<dt>Appointment</dt><dd>{{ $patient->appointment->appointment_date }}<br>{{ $patient->appointment->appointment_time }}</dd>@endif</dl></div></div><div class="card clinic-card mb-3"><div class="card-body p-4"><h2 class="h6">Patient & guardian</h2><dl class="profile-details"><dt>Birthdate</dt><dd>{{ \Carbon\Carbon::parse($patient->birthdate)->format('F j, Y') }}</dd><dt>Guardian</dt><dd>{{ $patient->guardian_name }}<small class="d-block text-muted">{{ $patient->guardian_relationship }}</small></dd><dt>Contact</dt><dd>{{ $patient->contact_number }}</dd><dt>Emergency contact</dt><dd>{{ $patient->emergency_contact ?: 'Not recorded' }}</dd><dt>Address</dt><dd>{{ $patient->address ?: 'Not recorded' }}</dd></dl><a href="{{ route('patients.index') }}">All patients &rarr;</a></div></div>
<div class="card clinic-card"><div class="card-body p-4"><h2 class="h6">Allergies & alerts</h2>@forelse($alerts as $alert)<div class="alert alert-warning p-2 small"><strong>{{ $alert->category }}: {{ $alert->description }}</strong>@if($alert->reaction)<div>{{ $alert->reaction }}</div>@endif</div>@empty<p class="small text-muted mb-0">No alerts recorded. Allergy status has not been confirmed.</p>@endforelse</div></div>
</aside>
<div class="col-lg-9">
@if($activeQueue)<div class="alert alert-info">Queue #{{ $activeQueue->id }} &middot; {{ str_replace('_',' ',$activeQueue->status) }}. Saving today's consultation completes this visit.</div>@endif
<nav class="patient-tabs mb-3" aria-label="Patient records">@foreach(['consultations'=>'Consultations','growth'=>'Growth & vitals','immunizations'=>'Vaccinations','alerts'=>'Allergies & alerts','billing'=>'Billing & Fees'] as $key=>$label)<a class="{{ $tab===$key?'active':'' }}" @if($tab===$key) aria-current="page" @endif href="{{ route('patients.show',[$patient,'tab'=>$key]) }}">{{ $label }}</a>@endforeach</nav>
@if($tab==='consultations') @include('consultations.patient')
@elseif($tab==='billing') @include('billing.patient')
@else @include('pediatric.'.$tab)
@endif
<div class="mt-3">{{ $records->links() }}</div>
</div></div></div>
@endsection
