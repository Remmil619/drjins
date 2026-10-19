<div class="card clinic-card"><div class="card-body p-4"><h2 class="h5">Vaccination history</h2><p class="small text-muted">Record administered doses or documented history. The clinician supplies the next due date.</p>
@forelse($records as $record)<article class="record-entry {{ $record->voided_at?'record-void':'' }}"><div class="d-flex flex-wrap justify-content-between"><strong>{{ $record->vaccine }} ï¿½ {{ $record->dose }}</strong><span>{{ $record->administered_on }}</span></div><p class="small text-muted mt-2 mb-1">Source: {{ str_replace('_',' ',$record->source) }} ï¿½ Provider: {{ $record->provider }}</p><p class="small mb-1">Lot: {{ $record->lot_number ?: 'Not recorded' }} ï¿½ Expiry: {{ $record->expiry_date ?: 'Not recorded' }}</p>@if($record->next_due_on)<p class="mb-1">Next dose due: <strong>{{ $record->next_due_on }}</strong></p>@endif
@if($record->reminder_closed_at)<p class="small">Reminder closed: {{ $record->reminder_outcome }}</p>@endif
<p class="preserve-lines small">{{ $record->notes }}</p>@include('components.void-record',['type'=>'immunization'])</article>
@empty<p class="text-muted py-4">No vaccination history recorded.</p>@endforelse
<details class="record-form mt-3" @if($errors->any()) open @endif><summary class="fw-semibold">+ Record vaccination</summary><form method="POST" action="{{ route('immunizations.store',$patient) }}" class="mt-3">@csrf<div class="row g-3">
@foreach(['vaccine'=>['Vaccine name',150],'dose'=>['Dose / series label',50],'provider'=>['Administering provider / facility',150],'lot_number'=>['Lot number (if known)',100]] as $field=>[$label,$max])<div class="col-md-6"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" @if($field==='vaccine') list="common-vaccines" @endif class="form-control" maxlength="{{ $max }}" value="{{ old($field) }}" @required($field!=='lot_number')></div>@endforeach
<div class="col-md-6"><label for="source" class="form-label">Record source</label><select id="source" name="source" class="form-select" required>@foreach(['clinic'=>'Given at this clinic','external_record'=>'External vaccination record','guardian_report'=>'Reported by guardian'] as $key=>$label)<option value="{{ $key }}" @selected(old('source')===$key)>{{ $label }}</option>@endforeach</select></div>
@foreach(['administered_on'=>'Date given','expiry_date'=>'Vaccine expiry date (optional)','next_due_on'=>'Next due date (optional)'] as $field=>$label)<div class="col-md-6"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input id="{{ $field }}" type="date" name="{{ $field }}" class="form-control" value="{{ old($field,$field==='administered_on'?today()->toDateString():'') }}" @required($field==='administered_on')></div>@endforeach
<div class="col-12"><label for="vaccine_notes" class="form-label">Notes</label><textarea id="vaccine_notes" name="notes" class="form-control" maxlength="2000">{{ old('notes') }}</textarea></div></div><button class="btn btn-primary mt-3">Save vaccination record</button></form></details>
</div></div>

<datalist id="common-vaccines">
    @foreach($clinicVaccines->where('available', true) as $vaccine)
        <option value="{{ $vaccine->name }}"></option>
    @endforeach
</datalist>
<p class="small text-muted mt-2">Vaccine name suggestions show only vaccines marked available at this clinic. You may type another name when recording a previous or external vaccination.</p>

<div class="card clinic-card mt-3"><div class="card-body p-4">
    <h3 class="h5">Available clinic vaccines</h3>
    <div class="d-flex flex-wrap gap-2 mb-3">
        @forelse($clinicVaccines->where('available', true) as $vaccine)
            <button type="button" class="btn btn-outline-primary btn-sm" data-vaccine-name="{{ $vaccine->name }}">{{ $vaccine->name }}</button>
        @empty
            <p class="small text-muted mb-0">No vaccines marked available. Open Manage clinic vaccines to confirm stock.</p>
        @endforelse
    </div>
    <details @if($errors->has('vaccine_name') || $errors->has('available')) open @endif>
        <summary class="fw-semibold">Manage clinic vaccines</summary>
        <p class="small text-muted mt-3">This list is shared across all patients. Mark a vaccine available after confirming clinic stock.</p>
        <ul class="list-group list-group-flush mb-3">
            @foreach($clinicVaccines as $vaccine)
                <li class="list-group-item px-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>{{ $vaccine->name }} <small class="text-muted">&mdash; {{ $vaccine->available ? 'Available' : 'Unavailable' }}</small></span>
                    <form method="POST" action="{{ route('clinic-vaccines.update', [$patient, $vaccine]) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="available" value="{{ $vaccine->available ? 0 : 1 }}">
                        <button class="btn btn-outline-secondary btn-sm" aria-label="{{ $vaccine->available ? 'Mark unavailable: ' : 'Mark available: ' }}{{ $vaccine->name }}">{{ $vaccine->available ? 'Mark unavailable' : 'Mark available' }}</button>
                    </form>
                </li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('clinic-vaccines.store', $patient) }}">
            @csrf
            <label for="vaccine_name" class="form-label">Add vaccine name</label>
            <input id="vaccine_name" name="vaccine_name" class="form-control" maxlength="150" required value="{{ old('vaccine_name') }}" placeholder="Enter vaccine name">
            @error('vaccine_name')<p class="text-danger small">{{ $message }}</p>@enderror
            <input type="hidden" name="available" value="0">
            <div class="form-check my-3">
                <input id="new_vaccine_available" name="available" type="checkbox" class="form-check-input" value="1" @checked(old('available'))>
                <label for="new_vaccine_available" class="form-check-label">Confirmed available at this clinic</label>
            </div>
            <button class="btn btn-primary">Add vaccine</button>
        </form>
    </details>
</div></div>
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