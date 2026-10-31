<div class="card clinic-card">
    <div class="card-body p-4">
        <h2 class="h5">Vaccination history</h2>
        <p class="small text-muted">Record administered doses or documented history. The clinician supplies the next due
            date.</p>
        @forelse($records as $record)

            <article class="record-entry {{ $record->voided_at ? 'record-void' : '' }}">
                <div class="d-flex flex-wrap justify-content-between">
                    <strong>{{ $record->vaccine }} &middot;
                        {{ $record->dose }}</strong>
                    <span>{{ \Carbon\Carbon::parse($record->administered_on)->format('d M Y') }}</span>
                </div>
                <p class="small text-muted mt-2 mb-1">Source: {{ ['clinic' => 'Given at this clinic', 'external_record' => 'External vaccination record', 'guardian_report' => 'Reported by guardian'][$record->source] ?? $record->source }} &middot; Administered by / Facility:
                    {{ $record->provider ?: 'Not recorded' }}</p>
                <p class="small mb-1">Lot number: {{ $record->lot_number ?: 'Not recorded' }} &middot; Expiry date:
                    {{ $record->expiry_date ? \Carbon\Carbon::parse($record->expiry_date)->format('d M Y') : 'Not recorded' }}</p>
                @if ($record->next_due_on)
                    <p class="mb-1">Next dose due: <strong>{{ \Carbon\Carbon::parse($record->next_due_on)->format('d M Y') }}</strong>
                    </p>
                @endif
                @if ($record->reminder_closed_at)
                    <p class="small">Reminder closed: {{ $record->reminder_outcome }}</p>
                @endif
                <p class="preserve-lines small">{{ $record->notes }}</p>@include('pediatric.edit-vaccination')
            </article>
        @empty
            <p class="text-muted py-4">No vaccination history recorded.</p>
        @endforelse

        <details class="record-form mt-3" @if ($errors->any()) open @endif>
            <summary class="fw-semibold">+ Record vaccination</summary>

            <form
                method="POST"
                action="{{ route('immunizations.store', $patient) }}"
                class="mt-3"
            >
                @csrf
                <div class="row g-3">
                    @foreach (['vaccine' => ['Vaccine name', 150], 'dose' => ['Dose / series label', 50], 'provider' => ['Administering provider / facility', 150], 'lot_number' => ['Lot number (if known)', 100]] as $field => [$label, $max])
                        <div class="col-md-6">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            @if ($field === 'vaccine')
                                <select id="vaccine" name="vaccine" class="form-select" required>
                                    <option value="">Select vaccine</option>
                                    @foreach ($clinicVaccines->where('available', true) as $vaccine)
                                        <option value="{{ $vaccine->name }}" data-dose-count="{{ $vaccine->dose_count }}" data-interval="{{ $vaccine->dose_interval_months }}" @selected(old('vaccine') === $vaccine->name)>{{ $vaccine->name }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field === 'dose')
                                <select id="dose" name="dose" class="form-select" required>
                                    <option value="">Select dose</option>
                                    @foreach (['1st', '2nd', '3rd', '4th'] as $dose)
                                        <option value="{{ $dose }}" @selected(old('dose') === $dose)>{{ $dose }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field === 'provider')
                                <select id="provider" name="provider" class="form-select" required>
                                    <option value="">Select doctor</option>
                                    @foreach ($doctors as $doctor)
                                        <option value="{{ $doctor->name }}" @selected(old('provider') === $doctor->name)>{{ $doctor->name }}</option>
                                    @endforeach
                                </select>
                            @else
                            <input
                                id="{{ $field }}"
                                name="{{ $field }}"
                                @if ($field === 'vaccine') list="common-vaccines" @endif
                                class="form-control"
                                maxlength="{{ $max }}"
                                value="{{ old($field) }}"
                                @required($field !== 'lot_number')
                            >
                            @endif
                        </div>
                    @endforeach
                    <div class="col-md-6">
                        <label for="source" class="form-label">Record source</label>
                        <select
                            id="source"
                            name="source"
                            class="form-select"
                            required
                        >
                            @foreach (['clinic' => 'Given at this clinic', 'external_record' => 'External vaccination record', 'guardian_report' => 'Reported by guardian'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('source') === $key)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @foreach (['administered_on' => 'Date given', 'expiry_date' => 'Vaccine expiry date (optional)', 'next_due_on' => 'Next due date (optional)'] as $field => $label)
                        <div class="col-md-6">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <input
                                id="{{ $field }}"
                                type="date"
                                name="{{ $field }}"
                                class="form-control"
                                value="{{ old($field, $field === 'administered_on' ? today()->toDateString() : '') }}"
                                @required($field === 'administered_on')
                            >
                        </div>
                    @endforeach
                    <div class="col-12">
                        <label for="vaccine_notes" class="form-label">Notes</label>
                        <textarea
                            id="vaccine_notes"
                            name="notes"
                            class="form-control"
                            maxlength="2000"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>
                <button class="btn btn-primary mt-3">Save vaccination record</button>
            </form>
        </details>
    </div>
</div>
<script>
(() => { const vaccine=document.getElementById('vaccine'), dose=document.getElementById('dose'), given=document.getElementById('administered_on'), next=document.getElementById('next_due_on'); if (!vaccine||!dose||!given||!next) return; const update=()=>{const o=vaccine.selectedOptions[0], n=parseInt(dose.value), max=parseInt(o?.dataset.doseCount||1), m=parseInt(o?.dataset.interval||0); if(max>1&&m&&n<max&&given.value){const d=new Date(given.value+'T00:00:00'); d.setMonth(d.getMonth()+m); next.value=`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;} else next.value=''; next.dispatchEvent(new Event('change', {bubbles:true}));}; [vaccine,dose,given].forEach(e=>e.addEventListener('change',update)); update(); })();
</script>
<p class="small text-muted mt-2">Only vaccines marked available and doctor accounts registered in Accounts are listed here.</p>
<div class="card clinic-card mt-3">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h3 class="h5 mb-0">Available clinic vaccines</h3>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('vaccines.index') }}">Vaccine management</a>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            @forelse($clinicVaccines->where('available', true) as $vaccine)
                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    data-vaccine-name="{{ $vaccine->name }}"
                >{{ $vaccine->name }}</button>
            @empty
                <p class="small text-muted mb-0">No vaccines marked available. Open Manage clinic vaccines to confirm
                    stock.</p>
            @endforelse
        </div>

        <details id="vaccine-manager" data-restore="{{ session('keep_vaccine_manager') ? 'true' : 'false' }}" @if(session('keep_vaccine_manager') || old('vaccine_manager') || $errors->has('vaccine_name') || $errors->has('available') || $errors->has('price')) open @endif>
            <summary class="fw-semibold">Manage clinic vaccines</summary>@if(session('keep_vaccine_manager'))<div class="alert alert-success mt-3" role="status">{{ session('success') }}</div>@endif @if(old('vaccine_manager') && $errors->any())<div class="alert alert-danger mt-3" role="alert">{{ $errors->first() }}</div>@endif
            <p class="small text-muted mt-3">This list is shared across all patients. Mark a vaccine available after
                confirming clinic stock.</p>
            <ul class="vaccine-management-list">
                @foreach ($clinicVaccines as $vaccine)
                    <li class="vaccine-management-row">
                        <span>{{ $vaccine->name }} <small class="text-muted">&mdash;
                                {{ $vaccine->available ? 'Available' : 'Unavailable' }}</small>
                            <small class="d-block">{{ $vaccine->price_centavos === null ? 'Price not set' : 'PHP ' . number_format($vaccine->price_centavos / 100, 2) }}</small>
                        </span>
                        <form method="POST" action="{{ route('clinic-vaccines.update', [$patient, $vaccine]) }}" class="vaccine-price-form">
                            @csrf @method('PATCH') <input type="hidden" name="vaccine_manager" value="1">
                            <input type="hidden" name="vaccine_price_id" value="{{ $vaccine->id }}"><div><label for="vaccine-price-{{ $vaccine->id }}" class="form-label small">Price per dose (PHP)</label>
                            <input id="vaccine-price-{{ $vaccine->id }}" name="price" type="number" min="0" max="9999999.99" step="0.01" class="form-control" required value="{{ old('vaccine_price_id') == $vaccine->id ? old('price') : ($vaccine->price_centavos === null ? '' : number_format($vaccine->price_centavos / 100, 2, '.', '')) }}"></div>
                            <button class="btn btn-primary btn-sm">Save price</button>
                        </form>

                        <form method="POST" action="{{ route('clinic-vaccines.update', [$patient, $vaccine]) }}">
                            @csrf @method('PATCH') <input type="hidden" name="vaccine_manager" value="1">
                            <input
                                type="hidden"
                                name="available"
                                value="{{ $vaccine->available ? 0 : 1 }}"
                            >
                            <button class="btn btn-outline-secondary btn-sm"
                                aria-label="{{ $vaccine->available ? 'Mark unavailable: ' : 'Mark available: ' }}{{ $vaccine->name }}"
                            >{{ $vaccine->available ? 'Mark unavailable' : 'Mark available' }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('clinic-vaccines.store', $patient) }}" class="vaccine-add-form"><h4 class="h6">Add a clinic vaccine</h4><input type="hidden" name="vaccine_manager" value="1">
                @csrf
                <label for="vaccine_name" class="form-label">Add vaccine name</label>
                <input
                    id="vaccine_name"
                    name="vaccine_name"
                    class="form-control"
                    maxlength="150"
                    required
                    value="{{ old('vaccine_name') }}"
                    placeholder="Enter vaccine name"
                >
                @error('vaccine_name')
                    <p class="text-danger small">{{ $message }}</p>
                @enderror
                <input
                    type="hidden"
                    name="available"
                    value="0"
                >
                <div class="form-check my-3">
                    <input
                        id="new_vaccine_available"
                        name="available"
                        type="checkbox"
                        class="form-check-input"
                        value="1"
                        @checked(old('available'))
                    >
                    <label for="new_vaccine_available" class="form-check-label">Confirmed available at this
                        clinic</label>
                </div>

                <div class="mt-3"><label for="new-vaccine-price" class="form-label">Price per dose (PHP, optional)</label><input id="new-vaccine-price" name="price" type="number" min="0" max="9999999.99" step="0.01" class="form-control" value="{{ old('vaccine_price_id') ? '' : old('price') }}"></div><button class="btn btn-primary mt-3">Add vaccine</button>
            </form>
        </details>
    </div>
</div>
<script>
    document.querySelectorAll('[data-vaccine-name]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById('vaccine');
            input.closest('details').open = true;
            input.value = button.dataset.vaccineName;
            input.focus();
        });
    });
</script>
