@if(!$measurement->voided_at)
<details class="mt-2" @if(old('measurement_id') == $measurement->id) open @endif>
    <summary class="btn btn-outline-primary btn-sm">Edit measurements</summary>
    <form method="POST" action="{{ route('growth.update', [$patient, $measurement]) }}" class="row g-3 mt-1">
        @csrf @method('PATCH')
        <input type="hidden" name="measurement_id" value="{{ $measurement->id }}">
        @php($editing = old('measurement_id') == $measurement->id)
        <div class="col-md-6">
            <label for="measurement-date-{{ $measurement->id }}" class="form-label">Measurement date</label>
            <input id="measurement-date-{{ $measurement->id }}" type="date" name="measured_on" class="form-control" value="{{ $editing ? old('measured_on') : $measurement->measured_on }}" min="{{ $patient->birthdate }}" max="{{ today()->toDateString() }}" required>
        </div>
        @foreach(['height_cm' => ['Height (cm)', 300], 'weight_kg' => ['Weight (kg)', 500], 'temperature_c' => ['Temperature (°C)', 45], 'head_cm' => ['Head circumference (cm)', 100]] as $field => [$label, $max])
            <div class="col-md-6">
                <label for="{{ $field }}-{{ $measurement->id }}" class="form-label">{{ $label }}</label>
                <input id="{{ $field }}-{{ $measurement->id }}" name="{{ $field }}" type="number" step="0.01" min="{{ $field === 'temperature_c' ? 25 : 0.01 }}" max="{{ $max }}" class="form-control" value="{{ $editing ? old($field) : $measurement->$field }}" @required(in_array($field, ['height_cm', 'weight_kg']))>
            </div>
        @endforeach
        <div class="col-12"><label for="measurement-notes-{{ $measurement->id }}" class="form-label">Notes</label><textarea id="measurement-notes-{{ $measurement->id }}" name="notes" class="form-control" maxlength="2000">{{ $editing ? old('notes') : $measurement->notes }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary">Save changes</button></div>
    </form>
</details>
@else
    <small class="text-muted">Voided measurement: {{ $measurement->void_reason }}</small>
@endif
