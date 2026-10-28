@extends('layouts.app')
@section('content')
    <section class="page-header">
        <div class="container">
            <span class="eyebrow">FRONT DESK</span>
            <h1>Today's clinic queue</h1>
            <p>Check in a child here. The doctor starts the check-up, then completes the visit when finished.</p>
        </div>
    </section>

    <div class="container">
        @include('components.feedback')
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card clinic-card"><div class="card-body p-4">
                    <h2 class="h5">Check in a patient</h2>
                    <label for="queue-search" class="form-label">Child's name</label>
                    <input id="queue-search" name="search" type="search" class="form-control" autocomplete="off" placeholder="Type the child's name" aria-describedby="queue-search-help">
                    <p id="queue-search-help" class="small text-muted mt-2">Select a result to add the child to today's queue. Online appointments must be booked for today.</p>
                    <p id="queue-search-status" class="small" role="status" aria-live="polite"></p>
                    <div id="queue-results" class="d-grid gap-2"></div>
                    <a href="{{ route('patients.create') }}" class="btn btn-outline-primary mt-3">Register a new patient</a>
                </div></div>
            </div>
            <div class="col-lg-8">
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Queue</th><th>Child</th><th>Visit</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse($entries as $entry)
                                <tr data-queue-id="{{ $entry->id }}">
                                    <td>#{{ $entry->daily_number }}<small class="d-block text-muted">{{ $entry->created_at->format('g:i A') }}</small></td>
                                    <td>
                                        <strong>{{ $entry->patient?->full_name ?? $entry->appointment?->child_name }}</strong>
                                        <small class="d-block text-muted">{{ $entry->appointment ? 'Online appointment' : 'Registered patient' }}</small>

                                    </td>
                                    <td><span class="badge text-bg-light" data-queue-status>{{ $entry->status === 'in_consultation' ? 'In check up' : 'Waiting' }}</span><p class="small mb-0">{{ $entry->reason }}</p></td>
                                    <td>
                                        @if($date === today()->toDateString() && in_array(auth()->user()->role, ['doctor', 'admin'], true))
                                            @if(in_array($entry->status, ['waiting', 'in_consultation'], true))
                                                <form method="POST" action="{{ route('queue.update', $entry) }}" data-start-checkup>
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="in_consultation">
                                                @if(!$entry->patient_id && !$entry->appointment?->child_date_of_birth)
                                                    <label for="birthdate-{{ $entry->id }}" class="form-label">Birthdate</label>
                                                    <input id="birthdate-{{ $entry->id }}" name="birthdate" type="date" max="{{ today()->toDateString() }}" class="form-control mb-2" required>
                                                @endif
                                                @if(!$entry->patient_id && !in_array($entry->appointment?->child_sex, ['Male', 'Female'], true))
                                                    <label for="sex-{{ $entry->id }}" class="form-label">Sex</label>
                                                    <select id="sex-{{ $entry->id }}" name="sex" class="form-select mb-2" required>
                                                        <option value="">Select sex</option><option>Male</option><option>Female</option>
                                                    </select>
                                                @endif
                                                    <button class="btn btn-primary btn-sm">{{ $entry->status === 'waiting' ? 'Start check-up' : 'Continue check-up' }}</button>
                                                </form>
                                            @endif

                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-5">No patients waiting for this date.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                    <p class="small text-muted">Queue numbers restart at 1 each day. Start check-up opens the patient record and shows In check up to staff. Online patients are registered when the check-up starts. Complete the visit from the patient record when the check-up is finished.</p>
            </div>
        </div>
    </div>

    <script>
        const search = document.getElementById('queue-search');
        const results = document.getElementById('queue-results');
        const notice = document.getElementById('queue-search-status');
        let timer;
        let activeRequest;
        let submitting = false;

        // Update the shared status without interrupting patient searches or forms.
        async function refreshQueueStatus() {
            try {
                if (!document.hidden) {
                    const response = await fetch(@json(route('queue.index', ['date' => $date])), {
                        headers: {Accept: 'application/json'}
                    });
                    if (response.ok) {
                        const data = await response.json();
                        document.querySelectorAll('[data-queue-id]').forEach(row => {
                            const entry = data.entries.find(item => String(item.id) === row.dataset.queueId);
                            if (!entry) { row.remove(); return; }
                            row.querySelector('[data-queue-status]').textContent = entry.status === 'in_consultation' ? 'In check up' : 'Waiting';
                            if (entry.status !== 'waiting') { const button = row.querySelector('[data-start-checkup] button'); if (button) button.textContent = 'Continue check-up'; }
                        });
                    }
                }
            } catch (_) { /* Retry on the next refresh when the connection returns. */ }
            setTimeout(refreshQueueStatus, 5000);
        }
        setTimeout(refreshQueueStatus, 5000);

        search.addEventListener('input', () => {
            clearTimeout(timer);
            activeRequest?.abort();
            results.replaceChildren();
            notice.textContent = '';
            const term = search.value.trim();
            if (!term || submitting) return;
            timer = setTimeout(async () => {
                const controller = new AbortController();
                activeRequest = controller;
                notice.textContent = 'Searching…';
                try {
                    const response = await fetch(@json(route('queue.search')) + '?search=' + encodeURIComponent(term), {
                        signal: controller.signal, headers: {Accept: 'application/json'}
                    });
                    if (!response.ok) throw new Error('Search is unavailable. Please try again.');
                    const data = await response.json();
                    if (activeRequest !== controller || submitting) return;
                    notice.textContent = data.results.length ? 'Select a child to add them to the queue.' : 'No eligible match. Register the child first if they have no patient record or online appointment.';
                    data.results.forEach(item => {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = @json(route('queue.store'));
                        const fields = {_token: @json(csrf_token())};
                        if (item.patient_id) fields.patient_id = item.patient_id;
                        if (item.online_appointment_id) fields.online_appointment_id = item.online_appointment_id;
                        Object.entries(fields).forEach(([name, value]) => {
                            const input = document.createElement('input');
                            input.type = 'hidden'; input.name = name; input.value = value;
                            form.append(input);
                        });
                        const button = document.createElement('button');
                        button.className = 'btn btn-light w-100 text-start d-block';
                        const name = document.createElement('strong');
                        name.className = 'd-block'; name.textContent = item.name;
                        const detail = document.createElement('small');
                        detail.className = 'd-block text-muted'; detail.textContent = item.detail;
                        button.append(name, detail); form.append(button);
                        form.addEventListener('submit', event => {
                            if (submitting) { event.preventDefault(); return; }
                            submitting = true; activeRequest?.abort();
                            results.querySelectorAll('button').forEach(b => b.disabled = true);
                            notice.textContent = 'Adding patient to the queue…';
                        });
                        results.append(form);
                    });
                } catch (error) {
                    if (error.name !== 'AbortError') notice.textContent = error.message;
                }
            }, 250);
        });
    </script>
@endsection
