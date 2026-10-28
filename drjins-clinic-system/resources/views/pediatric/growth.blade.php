<div class="card clinic-card">
    <div class="card-body p-4">
        <h2 class="h5">Growth & vital measurements</h2>
        <p class="text-muted small">Record measurements from each visit. Values are recorded observations; growth
            interpretation belongs to the clinician.</p>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Weight</th>
                        <th>Length/height</th>
                        <th>Head</th>
                        <th>Temperature</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr class="{{ $record->voided_at ? 'record-void' : '' }}">
                            <td>{{ $record->measured_on }}</td>
                            <td>{{ $record->weight_kg }} kg</td>
                            <td>{{ $record->height_cm }} cm</td>
                            <td>{{ $record->head_cm ? $record->head_cm . ' cm' : '�' }}</td>
                            <td>{{ $record->temperature_c ? $record->temperature_c . ' �C' : '�' }}</td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                @if ($record->notes)
                                    <p class="small preserve-lines">{{ $record->notes }}</p>
                                @endif @include('components.void-record', ['type' => 'growth'])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted py-4">No dated measurements recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (!$latestVitals && ($patient->height || $patient->weight || $patient->temperature))
            <p class="small text-muted">Legacy profile values (measurement date not recorded): height
                {{ $patient->height ?: '�' }}, weight {{ $patient->weight ?: '�' }}, temperature
                {{ $patient->temperature ?: '�' }}. These are not included in dated growth history.</p>
        @endif

        <details class="record-form mt-3" @if ($errors->any() || $latestVitals?->measured_on === today()->toDateString()) open @endif>
            <summary class="fw-semibold">+ Record measurements</summary>

            <form
                method="POST"
                action="{{ route('growth.store', $patient) }}"
                class="mt-3"
            >
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="measured_on" class="form-label">Measurement date</label>
                        <input
                            id="measured_on"
                            type="date"
                            name="measured_on"
                            value="{{ old('measured_on', today()->toDateString()) }}"
                            class="form-control"
                            required
                            min="{{ $patient->birthdate }}"
                            max="{{ today()->toDateString() }}"
                        >
                    </div>
                    @foreach (['weight_kg' => ['Weight (kg)', 500], 'height_cm' => ['Length / height (cm)', 300], 'head_cm' => ['Head circumference (cm)', 100], 'temperature_c' => ['Temperature (�C)', 45]] as $key => [$label, $max])
                        <div class="col-md-6">
                            <label for="{{ $key }}" class="form-label">{{ $label }}</label>
                            <input
                                type="number"
                                step="{{ $key === 'temperature_c' ? '0.1' : '0.01' }}"
                                min="{{ $key === 'temperature_c' ? '25' : '0.01' }}"
                                max="{{ $max }}"
                                id="{{ $key }}"
                                name="{{ $key }}"
                                class="form-control"
                                value="{{ old($key, $latestVitals?->measured_on === today()->toDateString() ? $latestVitals->$key : null) }}"
                                @required(in_array($key, ['weight_kg', 'height_cm']))
                            >
                        </div>
                    @endforeach
                    <div class="col-12">
                        <label for="growth_notes" class="form-label">Measurement notes</label>
                        <textarea
                            id="growth_notes"
                            name="notes"
                            class="form-control"
                            maxlength="2000"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>
                <button class="btn btn-primary mt-3">Save measurements</button>
            </form>
        </details>
    </div>
</div>
