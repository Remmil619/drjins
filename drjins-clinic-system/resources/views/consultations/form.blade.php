@php
    $consultationFields = [
        'complaint' => 'Reason for visit',
        'assessment' => 'Assessment',
        'prescription' => 'Prescription notes',
    ];
@endphp
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
