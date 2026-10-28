@if ($record->voided_at)
    <div class="small text-danger mt-2">Voided: {{ $record->void_reason }}</div>
@else

    <details class="mt-3 correction-action">
        <summary class="small text-danger">Void this record</summary>
        <p class="small text-muted mt-2 mb-0">Marks this record as invalid and keeps it in the history. This does not edit its contents. To correct it, void this record and add a new entry.</p>

        <form
            method="POST"
            action="{{ route('records.void', [$patient, $type, $record->id]) }}"
            class="d-flex flex-wrap gap-2 mt-2"
        >
            @csrf
            <label class="visually-hidden" for="void-{{ $type }}-{{ $record->id }}">Reason for voiding</label>
            <input
                id="void-{{ $type }}-{{ $record->id }}"
                name="void_reason"
                class="form-control"
                placeholder="Reason for voiding (original stays in history)"
                required
                minlength="5"
                maxlength="500"
            >
            <button class="btn btn-outline-danger btn-sm">Void record</button>
        </form>
    </details>
@endif
