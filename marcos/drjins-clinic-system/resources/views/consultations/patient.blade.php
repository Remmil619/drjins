<div class="card clinic-card"><div class="card-body p-4">
<h2 class="h5">Consultation history</h2>
@forelse($records as $visit)
<article class="record-entry @if($visit->voided_at) record-void @endif"><div class="d-flex flex-wrap justify-content-between gap-2"><strong>{{ \Carbon\Carbon::parse($visit->visit_date)->format('F j, Y') }}</strong><span class="text-muted small">Recorded by {{ $visit->author->name }}</span></div>
<dl class="mt-3 mb-0">@foreach(['complaint'=>'Reason for visit','assessment'=>'Assessment','plan'=>'Care plan','prescription'=>'Prescription notes','follow_up_date'=>'Follow-up date'] as $field=>$label)@if($visit->$field)<dt>{{ $label }}</dt><dd class="preserve-lines">{{ $visit->$field }}</dd>@endif @endforeach</dl>
@if($visit->follow_up_closed_at)<p class="small">Follow-up closed: {{ $visit->follow_up_outcome }}</p>@endif
@if(!$visit->voided_at && filled($visit->prescription))<a class="btn btn-outline-primary btn-sm mt-3" href="{{ route('prescriptions.print', [$patient, $visit]) }}" target="_blank" rel="noopener">Print prescription</a>@endif
@include('components.void-record',['record'=>$visit,'type'=>'consultation'])
</article>
@empty<p class="text-muted py-3">No consultation notes recorded yet.</p>@endforelse
<details class="mt-4 record-form" @if($errors->any()) open @endif><summary class="fw-semibold">+ Record consultation</summary>
<form method="POST" action="{{ route('consultations.store',$patient) }}" class="mt-3">@csrf
<input type="hidden" name="request_key" value="{{ old('request_key',(string)\Illuminate\Support\Str::uuid()) }}">
<div class="row g-3"><div class="col-md-6"><label for="visit_date" class="form-label">Visit date</label><input id="visit_date" name="visit_date" type="date" class="form-control" required min="{{ $patient->birthdate }}" max="{{ today()->toDateString() }}" value="{{ old('visit_date',today()->toDateString()) }}"></div><div class="col-md-6"><label for="follow_up_date" class="form-label">Follow-up date (optional)</label><input id="follow_up_date" name="follow_up_date" type="date" class="form-control" value="{{ old('follow_up_date') }}"></div>
@foreach(['complaint'=>'Reason for visit','assessment'=>'Assessment','prescription'=>'Prescription notes'] as $field=>$label)
<div class="col-12"><label for="{{ $field }}" class="form-label">{{ $label }}</label><textarea id="{{ $field }}" name="{{ $field }}" class="form-control" rows="3" maxlength="5000" @required(in_array($field,['complaint','assessment']))>{{ old($field) }}</textarea></div>
@endforeach
</div><button type="submit" class="btn btn-primary mt-3">Save consultation</button></form></details>
</div></div>
