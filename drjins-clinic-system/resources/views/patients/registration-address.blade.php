<fieldset class="row g-3 mb-3">
    <legend class="h6">Address</legend>
    @foreach(['province' => 'Province', 'municipality' => 'Municipality / City', 'barangay' => 'Barangay'] as $field => $label)
        <div class="col-md-4">
            <label for="guardian_{{ $field }}" class="form-label">{{ $label }}</label>
            <select id="guardian_{{ $field }}" name="{{ $field }}" class="form-select" data-selected="{{ old($field, $patient->$field ?? '') }}" required @disabled($field !== 'province')>
                <option value="">{{ $field === 'province' ? 'Loading provinces…' : 'Select the address above first' }}</option>
            </select>
        </div>
    @endforeach
    <div class="col-12">
        <span id="addressStatus" class="form-text" role="status">Select province, municipality or city, then barangay.</span>
        <button id="retryAddress" type="button" class="btn btn-sm btn-outline-secondary" hidden>Retry loading addresses</button>
    </div>
    @if(isset($patient) && $patient->address)
        <p class="small text-muted">Previously recorded address: {{ $patient->address }}</p>
    @endif
</fieldset>
<script src="{{ asset('js/patient-address.js') }}" data-address-url="{{ asset('data/philippine-addresses.json') }}" defer></script>
