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
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('patients.edit', $patient) }}">Edit
                        details</a>
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
                                <dt>Measurements ({{ $latestVitals->measured_on }})</dt>
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
                        <h2 class="h6">Patient & guardian</h2>
                        <dl class="profile-details">
                            <dt>Birthdate</dt>
                            <dd>{{ \Carbon\Carbon::parse($patient->birthdate)->format('F j, Y') }}</dd>
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
                <div class="card clinic-card">
                    <div class="card-body p-4">
                        <h2 class="h6">Allergies & alerts</h2>
                        @foreach($bookingAllergies as $bookingAllergy)
                            <div class="alert alert-warning p-2 small">
                                <strong>Reported allergies: {{ $bookingAllergy->allergies }}</strong>
                                <small class="d-block">Reported in booking portal &middot; {{ $bookingAllergy->appointment_date }}</small>
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
                    <h2 id="consultation-vitals-heading" class="h5 mb-3">Consultation &amp; vitals</h2>
                    @include('consultations.patient', ['records' => $consultationRecords])

                    <details class="card clinic-card p-3 mt-3" @if(request()->has('growth_page') || old('measurement_id')) open @endif>
                        <summary class="fw-semibold">Measurement history</summary>
                        <div class="table-responsive mt-3">
                            <table class="table"><thead><tr><th>Date</th><th>Height</th><th>Weight</th><th>Temperature</th></tr></thead><tbody>
                                @forelse($growthRecords as $measurement)
                                    <tr class="{{ $measurement->voided_at ? 'record-void' : '' }}">
                                        <td>{{ $measurement->measured_on }}</td><td>{{ $measurement->height_cm }} cm</td><td>{{ $measurement->weight_kg }} kg</td><td>{{ $measurement->temperature_c ?? 'Not recorded' }}</td>
                                    </tr>
                                    <tr><td colspan="4">@include('pediatric.edit-measurement')</td></tr>
                                @empty
                                    <tr><td colspan="4">No measurements recorded.</td></tr>
                                @endforelse
                            </tbody></table>
                        </div>
                        {{ $growthRecords->fragment('consultation-vitals-heading')->links() }}
                    </details>
                </section>
                @foreach(['immunizations' => ['Vaccinations', $immunizationRecords, 'pediatric.immunizations'], 'alerts' => ['Allergies & alerts', $alertRecords, 'pediatric.alerts'], 'billing' => ['Billing & Fees', $billingRecords, 'billing.patient']] as $section => [$label, $sectionRecords, $partial])
                    @php($sectionOpen = $tab === $section || request()->has($section . '_page') || $errors->any())
                    <div id="section-{{ $section }}" class="card clinic-card mb-3">
                        <button type="button" class="btn w-100 text-start p-4 d-flex align-items-center gap-3"
                            data-bs-toggle="modal" data-bs-target="#panel-{{ $section }}"
                            aria-haspopup="dialog" aria-controls="panel-{{ $section }}">
                            <span class="flex-grow-1" style="min-width: 0;">
                                <span class="d-block fw-semibold mb-1">{{ $label }}</span>
                                <span class="d-block small text-muted" style="overflow-wrap: anywhere;">
                                    @if($section === 'growth')
                                        @if($latestVitals)
                                            {{ $latestVitals->measured_on }} &middot; Height: {{ $latestVitals->height_cm }} cm &middot;
                                            Weight: {{ $latestVitals->weight_kg }} kg &middot;
                                            Temperature: {{ $latestVitals->temperature_c !== null ? $latestVitals->temperature_c . ' °C' : 'Not recorded' }}
                                        @else
                                            No measurements recorded yet.
                                        @endif
                                    @elseif($section === 'immunizations')
                                        @if($latestVaccination)
                                            {{ $latestVaccination->vaccine }} &middot; {{ $latestVaccination->dose }} &middot; {{ $latestVaccination->administered_on }}
                                            @if($latestVaccination->next_due_on) &middot; Next dose: {{ $latestVaccination->next_due_on }} @endif
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
                @endforeach
            </div>
        </div>
    </div>
    <script src="{{ asset('js/patient-panels.js') }}?v={{ filemtime(public_path('js/patient-panels.js')) }}" data-close-after-save="{{ session()->has('success') && !session('keep_vaccine_manager') && !$errors->any() ? 'true' : 'false' }}" defer></script>
@endsection
