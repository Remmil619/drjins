<link rel="stylesheet" href="{{ asset('css/consultation-history.css') }}?v={{ filemtime(public_path('css/consultation-history.css')) }}">
@php
    $historyFields = [
        'complaint' => 'Reason for visit',
        'assessment' => 'Assessment',
        'plan' => 'Care plan',
        'prescription' => 'Prescription notes',
        'follow_up_date' => 'Follow-up date',
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
        <div id="panel-history" class="modal" data-patient-panel data-section="history" data-page-key="consultations_page" data-auto-open="{{ (request()->hasAny(['consultations_page', 'growth_page']) || old('measurement_id')) ? 'true' : 'false' }}" tabindex="-1" aria-labelledby="history-title" aria-hidden="true">
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
                                        <h3 class="h6 mb-1">{{ \Carbon\Carbon::parse($visit->visit_date)->format('d M Y') }}</h3>
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
                        <details class="card clinic-card p-3 mt-3" @if(request()->has('growth_page') || old('measurement_id')) open @endif>
                            <summary class="fw-semibold">Measurement history</summary>
                            <div class="table-responsive mt-3"><table class="table"><thead><tr><th>Date</th><th>Height</th><th>Weight</th><th>Temperature</th></tr></thead><tbody>
                            @forelse($growthRecords as $measurement)
                                <tr class="{{ $measurement->voided_at ? 'record-void' : '' }}"><td>{{ \Carbon\Carbon::parse($measurement->measured_on)->format('d M Y') }}</td><td>{{ $measurement->height_cm }} cm</td><td>{{ $measurement->weight_kg }} kg</td><td>{{ $measurement->temperature_c ?? 'Not recorded' }}</td></tr>
                                <tr><td colspan="4">@include('pediatric.edit-measurement')</td></tr>
                            @empty
                                <tr><td colspan="4">No measurements recorded.</td></tr>
                            @endforelse
                            </tbody></table></div>
                            {{ $growthRecords->links() }}
                        </details>

                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>
    </div>
</div>
