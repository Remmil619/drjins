<link rel="stylesheet" href="{{ asset('css/consultation-history.css') }}?v={{ filemtime(public_path('css/consultation-history.css')) }}">
@php
    $historyFields = [
        'complaint' => 'Reason for visit',
        'assessment' => 'Assessment',
        'plan' => 'Care plan',
        'prescription' => 'Prescription notes',
        'follow_up_date' => 'Follow-up date',
    ];

    $consultationFields = [
        'complaint' => 'Reason for visit',
        'assessment' => 'Assessment',
        'prescription' => 'Prescription notes',
    ];
@endphp

<div class="card clinic-card">
    <div class="card-body p-4">
        <button type="button" class="btn w-100 text-start p-3 border rounded d-flex align-items-center gap-3" data-bs-toggle="modal" data-bs-target="#panel-history" aria-haspopup="dialog" aria-controls="panel-history">
            <span class="flex-grow-1">
                <span class="d-block fw-semibold">Consultation history</span>
                <span class="d-block small text-muted">{{ $records->total() }} consultation record(s)
                    @if($records->first()) &middot; {{ $records->first()->visit_date }} &middot; {{ \Illuminate\Support\Str::limit($records->first()->complaint, 90) }} @endif
                </span>
            </span>
            <span class="badge text-bg-light">View history</span>
        </button>
        <div id="panel-history" class="modal" data-patient-panel data-section="history" data-page-key="consultations_page" data-auto-open="{{ request()->has('consultations_page') ? 'true' : 'false' }}" tabindex="-1" aria-labelledby="history-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <div><h2 id="history-title" class="modal-title h5">Consultation history</h2><p class="small text-muted mb-0">{{ $patient->full_name }}</p></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                            <span class="small text-muted">{{ $records->total() }} consultation record(s)</span>
                            <span class="small text-muted">Most recent first</span>
                        </div>
                        @forelse($records as $visit)
                            <article class="consultation-history-card {{ $visit->voided_at ? 'is-voided' : '' }}">
                                <header class="consultation-history-header">
                                    <div>
                                        <h3 class="h6 mb-1">{{ \Carbon\Carbon::parse($visit->visit_date)->format('F j, Y') }}</h3>
                                        <p class="small text-muted mb-0">Recorded by {{ $visit->author?->name ?? 'Clinic staff' }}</p>
                                    </div>
                                    <span class="badge {{ $visit->voided_at ? 'text-bg-secondary' : 'text-bg-light' }}">{{ $visit->voided_at ? 'Voided' : 'Consultation' }}</span>
                                </header>
                                <dl class="consultation-history-notes">
                                    @foreach($historyFields as $field => $label)
                                        @if(filled($visit->$field))
                                            <div class="consultation-history-note {{ in_array($field, ['prescription', 'plan']) ? 'full-width' : '' }}">
                                                <dt>{{ $label }}</dt>
                                                <dd class="preserve-lines">{{ $visit->$field }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                                @if($visit->follow_up_closed_at)
                                    <p class="small px-4">Follow-up closed: {{ $visit->follow_up_outcome }}</p>
                                @endif
                                <footer class="consultation-history-actions">
                                    @if(!$visit->voided_at)
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-primary btn-sm" href="{{ route('consultations.print', [$patient, $visit]) }}" target="_blank" rel="noopener">Print consultation</a>
                                            @if(filled($visit->prescription))
                                                <a class="btn btn-outline-primary btn-sm" href="{{ route('prescriptions.print', [$patient, $visit]) }}" target="_blank" rel="noopener">Print prescription</a>
                                            @endif
                                        </div>
                                    @endif
                                    @include('components.void-record', ['record' => $visit, 'type' => 'consultation'])
                                </footer>
                            </article>
                        @empty
                            <div class="text-center border rounded bg-white p-5">
                                <h3 class="h6">No consultations yet</h3>
                                <p class="small text-muted mb-0">Saved consultation notes will appear here.</p>
                            </div>
                        @endforelse

                        {{ $records->links() }}
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>
        <button type="button" class="btn w-100 text-start p-3 mt-3 border rounded d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#panel-consultations" aria-haspopup="dialog" aria-controls="panel-consultations">
            <span><strong class="d-block">Consultation &amp; vitals</strong><small class="text-muted">Record the check-up and patient measurements</small></span>
            <span class="badge text-bg-light">Open form</span>
        </button>
        <div id="panel-consultations" class="modal" data-patient-panel data-section="consultations" data-auto-open="{{ $errors->any() && !old('measurement_id') ? 'true' : 'false' }}" tabindex="-1" aria-labelledby="consultation-form-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down"><div class="modal-content">
                <div class="modal-header"><div><h2 id="consultation-form-title" class="modal-title h5">Consultation &amp; vitals</h2><p class="small text-muted mb-0">{{ $patient->full_name }}</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
            <form
                method="POST"
                action="{{ route('consultations.store', $patient) }}"
                class="mt-3"
            >
                @csrf
                <input
                    type="hidden"
                    name="request_key"
                    value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}"
                >
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="visit_date" class="form-label">Visit date</label>
                        <input
                            id="visit_date"
                            name="visit_date"
                            type="date"
                            class="form-control"
                            required
                            min="{{ $patient->birthdate }}"
                            max="{{ today()->toDateString() }}"
                            value="{{ old('visit_date', today()->toDateString()) }}"
                        >
                    </div>
                    <div class="col-md-6">
                        <label for="follow_up_date" class="form-label">Follow-up date
                            (optional)</label>
                        <input
                            id="follow_up_date"
                            name="follow_up_date"
                            type="date"
                            class="form-control"
                            value="{{ old('follow_up_date') }}"
                        >
                    </div>
                    <div class="col-12"><h3 class="h6 mb-0">Growth &amp; vitals</h3><small class="text-muted">Measurements are saved with the visit and excluded from the consultation printout.</small></div>
                    @foreach(['height_cm' => ['Height (cm)', 300], 'weight_kg' => ['Weight (kg)', 500], 'temperature_c' => ['Temperature (°C)', 45]] as $key => [$label, $max])
                        <div class="col-md-4">
                            <label for="consultation-{{ $key }}" class="form-label">{{ $label }}</label>
                            <input id="consultation-{{ $key }}" name="{{ $key }}" type="number" class="form-control" step="0.01" min="{{ $key === 'temperature_c' ? 25 : 0.01 }}" max="{{ $max }}"
                                value="{{ old($key, $latestVitals?->measured_on === old('visit_date', today()->toDateString()) ? $latestVitals->$key : null) }}">
                        </div>
                    @endforeach
                    @foreach ($consultationFields as $field => $label)
                        <div class="col-12">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <textarea
                                id="{{ $field }}"
                                name="{{ $field }}"
                                class="form-control"
                                rows="3"
                                maxlength="5000"
                                @required(in_array($field, ['complaint', 'assessment']))
                            >{{ old($field) }}</textarea>
                        </div>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-primary mt-3">Save consultation &amp; vitals</button>
            </form>
                </div><div class="modal-footer"><button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button></div></div></div>
        </div>
    </div>
</div>
