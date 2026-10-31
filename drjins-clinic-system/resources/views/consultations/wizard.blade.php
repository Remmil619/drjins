<div id="consultation-wizard" class="modal" tabindex="-1" aria-labelledby="wizard-title" aria-hidden="true" data-auto-wizard="{{ ($errors->any() && old('consultation_workflow')) || isset($entry) ? 'true' : 'false' }}">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div><h2 id="wizard-title" class="h5 modal-title">Consultation</h2><p class="small text-muted mb-0">{{ $patient->full_name }}</p></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <p class="small text-muted" data-wizard-progress aria-live="polite">Step 1 of 3 � Consultation</p>
                @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <form id="consultation-workflow" method="POST" action="{{ isset($entry) ? route('consultation-workflow.booking.store', $entry) : route('consultation-workflow.store', $patient) }}" novalidate>
                    @csrf
                    <input type="hidden" name="consultation_workflow" value="1">
                    <input type="hidden" name="request_key" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">
                    <section class="card clinic-card p-4" data-wizard-step="0">
                        <h3 class="h5 mb-3">Consultation &amp; vitals</h3>
                        @if(isset($entry))
                            <p class="small text-muted">The patient record will be created when you save the consultation.</p>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6"><label class="form-label" for="booking-birthdate">Birthdate</label><input id="booking-birthdate" name="birthdate" type="date" class="form-control" required max="{{ today()->toDateString() }}" value="{{ old('birthdate', $booking->child_date_of_birth) }}"></div>
                                <div class="col-md-6"><label class="form-label" for="booking-sex">Sex</label><select id="booking-sex" name="sex" class="form-select" required><option value="">Select sex</option>@foreach(['Male', 'Female'] as $sex)<option @selected(old('sex', $booking->child_sex) === $sex)>{{ $sex }}</option>@endforeach</select></div>
                            </div>
                        @endif
                        @include('consultations.fields')
                    </section>
                    <section class="card clinic-card p-4" data-wizard-step="1" hidden>
                        <h3 class="h5 mb-3">Vaccination</h3>
                        <div class="form-check mb-3"><input type="checkbox" class="form-check-input" id="include-vaccination" name="include_vaccination" value="1" @checked(old('include_vaccination'))><label for="include-vaccination" class="form-check-label">Record a vaccination for this visit</label></div>
                        <p class="small text-muted">Leave this unchecked if no vaccination needs to be recorded.</p>
                        <fieldset id="workflow-vaccination" disabled hidden>
                            <div class="row g-3">
                                @foreach(['vaccine' => ['Vaccine name', 150], 'dose' => ['Dose / series label', 50], 'provider' => ['Administering provider / facility', 150], 'lot_number' => ['Lot number (optional)', 100]] as $field => [$label, $max])
                                    <div class="col-md-6"><label class="form-label" for="workflow-{{ $field }}">{{ $label }}</label>
                                        @if($field === 'vaccine')
                                            <select id="workflow-vaccine" name="vaccination[vaccine]" class="form-select" required><option value="">Select vaccine</option>@foreach($clinicVaccines->where('available', true) as $vaccine)<option value="{{ $vaccine->name }}" data-dose-count="{{ $vaccine->dose_count }}" data-interval="{{ $vaccine->dose_interval_months }}" @selected(old('vaccination.vaccine') === $vaccine->name)>{{ $vaccine->name }}</option>@endforeach</select>
                                        @elseif($field === 'dose')
                                            <select id="workflow-dose" name="vaccination[dose]" class="form-select" required><option value="">Select dose</option>@foreach(['1st', '2nd', '3rd', '4th'] as $dose)<option value="{{ $dose }}" @selected(old('vaccination.dose') === $dose)>{{ $dose }}</option>@endforeach</select>
                                        @elseif($field === 'provider')
                                            <select id="workflow-provider" name="vaccination[provider]" class="form-select" required><option value="">Select doctor</option>@foreach($doctors as $doctor)<option value="{{ $doctor->name }}" @selected(old('vaccination.provider') === $doctor->name)>{{ $doctor->name }}</option>@endforeach</select>
                                        @else
                                            <input id="workflow-{{ $field }}" name="vaccination[{{ $field }}]" class="form-control" maxlength="{{ $max }}" @required($field !== 'lot_number') value="{{ old('vaccination.'.$field) }}">
                                        @endif
                                    </div>
                                @endforeach
                                <div class="col-md-6"><label class="form-label" for="workflow-source">Record source</label><select class="form-select" id="workflow-source" name="vaccination[source]">@foreach(['clinic' => 'Given at this clinic', 'external_record' => 'External vaccination record', 'guardian_report' => 'Reported by guardian'] as $value => $label)<option value="{{ $value }}" @selected(old('vaccination.source', 'clinic') === $value)>{{ $label }}</option>@endforeach</select></div>
                                @foreach(['administered_on' => 'Date given', 'expiry_date' => 'Expiry date (optional)', 'next_due_on' => 'Next dose date (optional)'] as $field => $label)
                                    <div class="col-md-6" id="workflow-{{ $field }}-row" @if($field === 'next_due_on') hidden @endif><label class="form-label" for="workflow-{{ $field }}">{{ $label }}</label><input class="form-control" type="date" id="workflow-{{ $field }}" name="vaccination[{{ $field }}]" @required($field === 'administered_on') value="{{ old('vaccination.'.$field, $field === 'administered_on' ? today()->toDateString() : '') }}"></div>
                                @endforeach
                                <div class="col-12"><label class="form-label" for="workflow-vaccine-notes">Notes</label><textarea class="form-control" id="workflow-vaccine-notes" name="vaccination[notes]" maxlength="2000">{{ old('vaccination.notes') }}</textarea></div>
                            </div>
                        </fieldset>
                        <div id="workflow-vaccine-prices" class="d-none">@foreach($clinicVaccines->where('available', true) as $vaccine)<span data-name="{{ $vaccine->name }}" data-price="{{ $vaccine->price_centavos === null ? '' : number_format($vaccine->price_centavos / 100, 2, '.', '') }}"></span>@endforeach</div>
                    </section>
                    <section class="card clinic-card p-4" data-wizard-step="2" hidden>
                        <h3 class="h5 mb-3">Billing &amp; Fees</h3>
                        <p class="small text-muted">Review the fees for this visit. Payment can be recorded in the patient's billing details after saving.</p>
                        <div class="mb-3"><label class="form-label" for="workflow-consultation-fee">Consultation fee (PHP)</label><input id="workflow-consultation-fee" name="consultation_fee" class="form-control" type="number" step="0.01" min="0" max="1000000" required value="{{ old('consultation_fee', '500.00') }}"></div>
                        <div class="mb-3" id="workflow-vaccination-fee-row" hidden><label class="form-label" for="workflow-vaccination-fee">Vaccination fee (PHP)</label><input id="workflow-vaccination-fee" name="vaccination_fee" class="form-control" type="number" step="0.01" min="0" max="1000000" value="{{ old('vaccination_fee') }}"></div>
                        <p class="fw-semibold mb-0" data-workflow-total aria-live="polite"></p>
                    </section>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-primary" data-wizard-back>Back to overview</button>
                <button type="button" class="btn btn-primary" data-wizard-next>Next</button>
                <button type="submit" form="consultation-workflow" class="btn btn-primary" data-wizard-save hidden>Save consultation</button>
            </div>
        </div>
    </div>
</div>
<script>
(() => { const root=document.getElementById('consultation-wizard'); if (!root) return; const vaccine=root.querySelector('#workflow-vaccine'), dose=root.querySelector('#workflow-dose'), given=root.querySelector('#workflow-administered_on'), next=root.querySelector('#workflow-next_due_on'), row=root.querySelector('#workflow-next_due_on-row'); if (!vaccine||!dose||!given||!next||!row) return; const update=()=>{const option=vaccine.selectedOptions[0], n=parseInt(dose.value), max=parseInt(option?.dataset.doseCount||1), months=parseInt(option?.dataset.interval||0), show=max>1&&months&&n<max; row.hidden=!show; if(show&&given.value){const date=new Date(given.value+'T00:00:00'); date.setMonth(date.getMonth()+months); next.value=`${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;} else next.value=''; next.dispatchEvent(new Event('change', {bubbles:true}));}; [vaccine,dose,given].forEach(element=>element.addEventListener('change',update)); update(); })();
</script>
<script src="{{ asset('js/consultation-workflow.js') }}?v={{ filemtime(public_path('js/consultation-workflow.js')) }}" defer></script>
