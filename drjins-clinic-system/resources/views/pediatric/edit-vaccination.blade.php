@if(!$record->voided_at)
<details class="mt-2" @if(old('immunization_id') == $record->id) open @endif>
    <summary class="btn btn-outline-primary btn-sm">Edit vaccination</summary>
    <form method="POST" action="{{ route('immunizations.update', [$patient, $record]) }}" class="row g-3 mt-1">
        @csrf @method('PATCH')
        <input type="hidden" name="immunization_id" value="{{ $record->id }}">
        @php($editing = old('immunization_id') == $record->id)
        @foreach(['vaccine' => ['Vaccine name', 150], 'dose' => ['Dose', 50], 'provider' => ['Administered by / Facility', 150], 'lot_number' => ['Lot number (optional)', 100]] as $field => [$label, $max])
            <div class="col-md-6">
                <label for="edit-{{ $field }}-{{ $record->id }}" class="form-label">{{ $label }}</label>
                @if($field === 'vaccine')
                    <select id="edit-vaccine-{{ $record->id }}" name="vaccine" class="form-select" required><option value="">Select vaccine</option>@foreach($clinicVaccines->where('available', true) as $vaccine)<option value="{{ $vaccine->name }}" @selected(($editing ? old('vaccine') : $record->vaccine) === $vaccine->name)>{{ $vaccine->name }}</option>@endforeach</select>
                @elseif($field === 'dose')
                    <select id="edit-dose-{{ $record->id }}" name="dose" class="form-select" required><option value="">Select dose</option>@foreach(['1st', '2nd', '3rd', '4th'] as $dose)<option value="{{ $dose }}" @selected(($editing ? old('dose') : $record->dose) === $dose)>{{ $dose }}</option>@endforeach</select>
                @elseif($field === 'provider')
                    <select id="edit-provider-{{ $record->id }}" name="provider" class="form-select" required><option value="">Select doctor</option>@foreach($doctors as $doctor)<option value="{{ $doctor->name }}" @selected(($editing ? old('provider') : $record->provider) === $doctor->name)>{{ $doctor->name }}</option>@endforeach</select>
                @else
                    <input id="edit-{{ $field }}-{{ $record->id }}" name="{{ $field }}" class="form-control" maxlength="{{ $max }}" value="{{ $editing ? old($field) : $record->$field }}">
                @endif
            </div>
        @endforeach
        <div class="col-md-6">
            <label for="edit-source-{{ $record->id }}" class="form-label">Record source</label>
            <select id="edit-source-{{ $record->id }}" name="source" class="form-select" required>
                @foreach(['clinic' => 'Given at this clinic', 'external_record' => 'External vaccination record', 'guardian_report' => 'Reported by guardian'] as $value => $label)
                    <option value="{{ $value }}" @selected(($editing ? old('source') : $record->source) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @foreach(['administered_on' => 'Date given', 'expiry_date' => 'Expiry date (optional)', 'next_due_on' => 'Next dose due (optional)'] as $field => $label)
            <div class="col-md-6">
                <label for="edit-{{ $field }}-{{ $record->id }}" class="form-label">{{ $label }}</label>
                <input id="edit-{{ $field }}-{{ $record->id }}" type="date" name="{{ $field }}" class="form-control" value="{{ $editing ? old($field) : $record->$field }}" @required($field === 'administered_on')>
            </div>
        @endforeach
        <div class="col-12"><label for="edit-notes-{{ $record->id }}" class="form-label">Notes</label><textarea id="edit-notes-{{ $record->id }}" name="notes" class="form-control" maxlength="2000">{{ $editing ? old('notes') : $record->notes }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary">Save changes</button></div>
    </form>
</details>
@else
    <small class="text-muted">Voided record: {{ $record->void_reason }}</small>
@endif
