@extends('layouts.app')
@section('content')

    <section class="page-header">
        <div class="container">
            <span class="eyebrow">PATIENT CARE</span>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1>{{ $patient->full_name }}</h1>
                    <p class="mb-0">{{ $patient->patient_no }} &middot; {{ $patient->age_label }} &middot;
                        {{ $patient->sex }} &middot; {{ $patient->status }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('patients.edit', $patient) }}">Edit
                        details</a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#panel-consultations" aria-haspopup="dialog"
                        aria-controls="panel-consultations">Add consultation</button>
                </div>
            </div>
        </div>
    </section>
    <div class="container">
        @include('components.feedback')
        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="card clinic-card mb-3">
                    <div class="card-body p-4">
                        <h2 class="h6">Patient details</h2>
                        <dl class="profile-details mb-0">
                            <dt>Patient number</dt>
                            <dd>{{ $patient->patient_no }}</dd>
                            <dt>Full name</dt>
                            <dd>{{ $patient->full_name }}</dd>
                            @if($latestVitals)
                                <dt>Measurements ({{ \Carbon\Carbon::parse($latestVitals->measured_on)->format('d M Y') }})</dt>
                                <dd>Height: {{ $latestVitals->height_cm }} cm<br>
                                    Weight: {{ $latestVitals->weight_kg }} kg<br>
                                    Temperature: {{ $latestVitals->temperature_c ?? 'Not recorded' }}{{ $latestVitals->temperature_c !== null ? ' °C' : '' }}</dd>
                            @endif
                            @if ($patient->appointment)
                                <dt>Appointment</dt>
                                <dd>{{ $patient->appointment->appointment_date }}<br>{{ $patient->appointment->appointment_time }}
                                </dd>
                            @endif
                        </dl>
                    </div>
                </div>
                <div class="card clinic-card mb-3">
                    <div class="card-body p-4">
                        <h2 class="h6">Allergies & alerts</h2>
                        @foreach($bookingAllergies as $bookingAllergy)
                            <div class="alert alert-warning p-2 small">
                                <strong>Reported allergies: {{ $bookingAllergy->allergies }}</strong>
                                <small class="d-block">Reported in booking portal &middot; {{ \Carbon\Carbon::parse($bookingAllergy->appointment_date)->format('d M Y') }}</small>
                            </div>
                        @endforeach
                        @forelse($alerts as $alert)
                            <div class="alert alert-warning p-2 small">
                                <strong>{{ $alert->category }}:
                                    {{ $alert->description }}</strong>
                                @if ($alert->reaction)
                                    <div>{{ $alert->reaction }}</div>
                                @endif
                            </div>
                        @empty
                            @if($bookingAllergies->isEmpty())
                            <p class="small text-muted mb-0">No alerts recorded. Allergy status has not been
                                confirmed.</p>
                            @endif
                        @endforelse
                    </div>
                </div>
                <div class="card clinic-card mb-3">
                    <div class="card-body p-4">
                        <h2 class="h6">Patient & guardian</h2>
                        <dl class="profile-details">
                            <dt>Birthdate</dt>
                            <dd>{{ \Carbon\Carbon::parse($patient->birthdate)->format('d M Y') }}</dd>
                            <dt>Guardian</dt>
                            <dd>{{ $patient->guardian_name }}<small
                                    class="d-block text-muted">{{ $patient->guardian_relationship }}</small>
                            </dd>
                            <dt>Contact</dt>
                            <dd>{{ $patient->contact_number }}</dd>
                            <dt>Emergency contact</dt>
                            <dd>{{ $patient->emergency_contact ?: 'Not recorded' }}</dd>
                            <dt>Address</dt>
                            <dd>{{ $patient->address ?: 'Not recorded' }}</dd>
                        </dl>
                        <a href="{{ route('patients.index') }}">All patients &rarr;</a>
                    </div>
                </div>
            </aside>
            <div class="col-lg-9">
                @if ($activeQueue)
                    <div class="alert alert-info d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <span>Queue #{{ $activeQueue->daily_number }} &middot;
                            {{ $activeQueue->status === 'in_consultation' ? 'In check up' : 'Waiting' }}.
                            Save the consultation notes, then complete the visit when the check-up is finished.</span>
                        @if($activeQueue->status === 'in_consultation' && in_array(auth()->user()->role, ['doctor', 'admin'], true))
                            <form method="POST" action="{{ route('queue.done', $activeQueue) }}">
                                @csrf
                                <button class="btn btn-primary">Complete visit</button>
                            </form>
                        @endif
                    </div>
                @endif
                <section aria-labelledby="consultation-vitals-heading" class="mb-4">
                    <h2 id="consultation-vitals-heading" class="h5 mb-3">Consultation</h2>
                    @include('consultations.patient', ['records' => $consultationRecords])

                </section>
                @foreach(['alerts' => ['Allergies & alerts', $alertRecords, 'pediatric.alerts']] as $section => [$label, $sectionRecords, $partial])
                    @php($sectionOpen = $tab === $section || request()->has($section . '_page'))
                    <div id="section-{{ $section }}" class="patient-section">
                      <div class="card clinic-card mb-3">
                        <button type="button" class="btn w-100 text-start p-4 d-flex align-items-center gap-3"
                            data-bs-toggle="modal" data-bs-target="#panel-{{ $section }}"
                            aria-haspopup="dialog" aria-controls="panel-{{ $section }}">
                            <span class="flex-grow-1" style="min-width: 0;">
                                <span class="d-block fw-semibold mb-1">{{ $label }}</span>
                                <span class="d-block small text-muted" style="overflow-wrap: anywhere;">
                                    @if($section === 'growth')
                                        @if($latestVitals)
                                            {{ \Carbon\Carbon::parse($latestVitals->measured_on)->format('d M Y') }} &middot; Height: {{ $latestVitals->height_cm }} cm &middot;
                                            Weight: {{ $latestVitals->weight_kg }} kg &middot;
                                            Temperature: {{ $latestVitals->temperature_c !== null ? $latestVitals->temperature_c . ' °C' : 'Not recorded' }}
                                        @else
                                            No measurements recorded yet.
                                        @endif
                                    @elseif($section === 'immunizations')
                                        @if($latestVaccination)
                                            {{ $latestVaccination->vaccine }} &middot; {{ $latestVaccination->dose }} &middot; {{ \Carbon\Carbon::parse($latestVaccination->administered_on)->format('d M Y') }}
                                            @if($latestVaccination->next_due_on) &middot; Next dose: {{ \Carbon\Carbon::parse($latestVaccination->next_due_on)->format('d M Y') }} @endif
                                        @else
                                            No vaccination history recorded yet.
                                        @endif
                                    @elseif($section === 'alerts')
                                        @if($alerts->isNotEmpty() || $bookingAllergies->isNotEmpty())
                                            {{ \Illuminate\Support\Str::limit($alerts->map(fn ($alert) => $alert->category . ': ' . $alert->description)->concat($bookingAllergies->map(fn ($booking) => 'Portal-reported: ' . $booking->allergies))->implode(' · '), 200) }}
                                        @else
                                            No alerts recorded. Allergy status has not been confirmed.
                                        @endif
                                    @else
                                        Charges: PHP {{ number_format($fees / 100, 2) }} &middot;
                                        Paid: PHP {{ number_format($paid / 100, 2) }} &middot;
                                        Balance: PHP {{ number_format(($fees - $paid) / 100, 2) }}
                                    @endif
                                </span>
                            </span>
                            <span class="badge text-bg-light">View details</span>
                        </button>
                        <div id="panel-{{ $section }}" class="modal" data-patient-panel data-section="{{ $section }}" data-auto-open="{{ $sectionOpen ? 'true' : 'false' }}" tabindex="-1" aria-labelledby="panel-title-{{ $section }}" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                              <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h2 id="panel-title-{{ $section }}" class="modal-title h5">{{ $label }}</h2>
                                        <p class="small text-muted mb-0">{{ $patient->full_name }}</p>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                @include($partial, ['records' => $sectionRecords])
                                <div class="mt-3">{{ $sectionRecords->fragment('section-' . $section)->links() }}</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button>
                                </div>
                              </div>
                            </div>
                        </div>
                    </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div id="panel-consultations" class="modal" data-patient-panel data-section="consultations"
        tabindex="-1" aria-labelledby="add-consultation-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 id="add-consultation-title" class="modal-title h5">Add consultation</h2>
                        <p class="small text-muted mb-0">{{ $patient->full_name }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <button type="button" class="card clinic-card w-100 text-start p-4 mb-3" data-open-workflow="0">
                        <strong>Consultation &amp; vitals</strong>
                        <span class="small text-muted">{{ $consultationRecords->total() }} consultation record(s) &middot; Record the check-up and patient measurements</span>
                        <span class="badge text-bg-light mt-2">Open form</span>
                    </button>
                    @foreach(['immunizations' => ['Vaccinations', $immunizationRecords, 'pediatric.immunizations'], 'billing' => ['Billing & Fees', $billingRecords, 'billing.patient']] as $section => [$label, $sectionRecords, $partial])
                        <div id="section-{{ $section }}" class="card clinic-card mb-3">
                            <button type="button" class="btn w-100 text-start p-4 d-flex gap-3" data-open-workflow="{{ $section === 'immunizations' ? 1 : 2 }}">

                                <span class="flex-grow-1">
                                    <strong class="d-block">{{ $label }}</strong>
                                    <span class="d-block small text-muted">
                                        @if($section === 'immunizations')
                                            @if($latestVaccination)
                                                {{ $latestVaccination->vaccine }} &middot; {{ $latestVaccination->dose }} &middot; {{ \Carbon\Carbon::parse($latestVaccination->administered_on)->format('d M Y') }}
                                                @if($latestVaccination->next_due_on) &middot; Next dose: {{ \Carbon\Carbon::parse($latestVaccination->next_due_on)->format('d M Y') }} @endif
                                            @else
                                                No vaccination history recorded yet.
                                            @endif
                                        @else
                                            Charges: PHP {{ number_format($fees / 100, 2) }} &middot;
                                            Paid: PHP {{ number_format($paid / 100, 2) }} &middot;
                                            Balance: PHP {{ number_format(($fees - $paid) / 100, 2) }}
                                        @endif
                                    </span>
                                </span>
                                <span class="badge text-bg-light overview-open">View details</span>
                            </button>
                            <div class="px-4 pb-3"><button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#panel-{{ $section }}">Manage saved records</button></div>
                        </div>
                        <div id="panel-{{ $section }}" class="modal" data-patient-panel data-section="{{ $section }}" tabindex="-1" aria-labelledby="saved-{{ $section }}-title" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down"><div class="modal-content">
                                <div class="modal-header"><h2 class="h5 modal-title" id="saved-{{ $section }}-title">{{ $label }} records</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                <div class="modal-body">@include($partial, ['records' => $sectionRecords]){{ $sectionRecords->links() }}</div>
                                <div class="modal-footer"><button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#panel-consultations">Back to overview</button></div>
                            </div></div>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @include('consultations.wizard')
    <script src="{{ asset('js/patient-panels.js') }}?v={{ filemtime(public_path('js/patient-panels.js')) }}" data-open-consultation="{{ $errors->any() || request()->hasAny(['growth_page', 'immunizations_page', 'billing_page']) || in_array(request('tab'), ['consultations', 'immunizations', 'billing', 'growth']) ? 'true' : 'false' }}" data-close-after-save="{{ session()->has('success') && !session('keep_vaccine_manager') && !$errors->any() ? 'true' : 'false' }}" defer></script>
@endsection
