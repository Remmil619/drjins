@extends('layouts.app')
@section('content')
<section class="page-header"><div class="container"><span class="eyebrow">PATIENT CARE</span><h1>Consultation history</h1><p class="mb-0">Review consultation records across all patients.</p></div></section>
<div class="container consultation-directory">
    @include('components.feedback')
    @if($selectedPatient)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div><h2 class="h5 mb-1">{{ $selectedPatient->full_name }}</h2><p class="small text-muted mb-0">{{ $selectedPatient->patient_no }} &middot; All consultation records</p></div>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('consultations.index') }}">All patients' consultations</a>
        </div>
    @endif
    <form data-auto-filter method="GET" action="{{ route('consultations.index') }}" class="history-search mb-4">
        <label for="consultation-search" class="visually-hidden">Search by patient name or number</label>
        <input id="consultation-search" type="search" name="search" class="form-control" value="{{ $search }}" maxlength="100" placeholder="Search by patient name or patient number" autocomplete="off">
    </form>
    <div class="card clinic-card">
        <div class="history-list-heading"><h2 class="h6 mb-0">Patient consultations <span class="history-count">{{ $records->total() }}</span></h2><span class="small text-muted">Most recent first</span></div>
        <div class="history-row history-column-labels" aria-hidden="true"><span>Patient</span><span>Visit date</span><span>Reason for visit</span><span>Status</span><span></span></div>
        @forelse($records as $visit)
            <details class="history-list-record" @if($errors->any() && old('history_record_id') == $visit->id) open @endif>
                <summary class="history-row">
                    <span class="history-patient"><strong>{{ $visit->patient->full_name }}</strong><small>{{ $visit->patient->patient_no }}</small></span>
                    <span class="history-date">{{ \Carbon\Carbon::parse($visit->visit_date)->format('d M Y') }}</span>
                    <span class="history-reason">{{ \Illuminate\Support\Str::limit($visit->complaint, 100) }}</span>
                    <span><span class="badge {{ $visit->voided_at ? 'text-bg-secondary' : 'text-bg-light' }}">{{ $visit->voided_at ? 'Voided' : 'Active' }}</span></span>
                    <span class="history-expand"><span class="history-view-label">View details</span><span class="history-hide-label">Hide details</span> <span aria-hidden="true">&#8964;</span></span>
                </summary>
                <div class="history-record-content">
                    <p class="small text-muted mb-3">Recorded by {{ $visit->author?->name ?? 'Clinic staff' }}</p>
                    <dl class="row mb-0">
                        @foreach(['complaint' => 'Reason for visit', 'assessment' => 'Assessment', 'plan' => 'Care plan', 'prescription' => 'Prescription notes', 'follow_up_date' => 'Follow-up date'] as $field => $label)
                            @if(filled($visit->$field))<div class="col-md-6 mb-3"><dt class="small text-muted">{{ $label }}</dt><dd class="preserve-lines mb-0">{{ $visit->$field }}</dd></div>@endif
                        @endforeach
                    </dl>
                    @if($visit->follow_up_closed_at)<p class="small">Follow-up closed: {{ $visit->follow_up_outcome }}</p>@endif
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('consultations.index', ['patient' => $visit->patient_id]) }}">Patient history</a>
                        @if(!$visit->voided_at)
                            <a class="btn btn-light btn-sm" href="{{ route('consultations.print', [$visit->patient, $visit]) }}" target="_blank" rel="noopener">Print consultation</a>
                            @if(filled($visit->prescription))<a class="btn btn-light btn-sm" href="{{ route('prescriptions.print', [$visit->patient, $visit]) }}" target="_blank" rel="noopener">Print prescription</a>@endif
                        @endif
                    </div>
                    @include('components.void-record', ['patient' => $visit->patient, 'record' => $visit, 'type' => 'consultation'])
                </div>
            </details>
        @empty
            <div class="p-5 text-center"><h2 class="h5">No consultation records found</h2><p class="text-muted mb-0">Search for another patient or clear the search to see all consultations.</p></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
</div>
@endsection
