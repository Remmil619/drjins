@extends('layouts.app')
@section('content')
    <section class="page-header">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="eyebrow">PATIENT COMMUNICATION</span>
                <h1>Inquiries</h1>
                <p class="mb-0">Messages from families through the booking portal.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('inquiries.index', ['page' => $page]) }}">Refresh inbox</a>
        </div>
    </section>

    <div class="container inquiry-inbox">
        @include('components.feedback')
        @if ($error)
            <div class="alert alert-warning" role="alert">{{ $error }}</div>
        @else
            <div class="inquiry-toolbar">
                <h2 class="h6 mb-0">Inbox <span class="inquiry-count">{{ count($inquiries) }}</span></h2>
                <p class="small text-muted mb-0">Tap a mobile number to call the sender.</p>
            </div>
            @forelse($inquiries as $inquiry)
                @php
                    $received = null;
                    try {
                        $received = \Carbon\Carbon::parse($inquiry['created_at'], config('app.timezone'))
                            ->timezone(config('app.timezone'));
                    } catch (\Exception $exception) {
                        // Keep the message readable if the portal sends an invalid date.
                    }
                    $phone = preg_replace('/[^0-9+]/', '', $inquiry['contact_number']);
                @endphp

                <article class="card clinic-card inquiry-card mb-3">
                    <div class="card-body p-4">
                        <div class="inquiry-heading">
                            <div class="inquiry-sender">
                                <span class="inquiry-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($inquiry['name'], 0, 1)) }}</span>
                                <div>
                                    <h3 class="inquiry-name">{{ $inquiry['name'] }}</h3>
                                    @if ($phone)
                                        <button type="button" class="inquiry-phone" data-phone="{{ $phone }}" data-name="{{ $inquiry['name'] }}" aria-label="Show call card for {{ $inquiry['name'] }} at {{ $inquiry['contact_number'] }}"><span class="inquiry-phone-number">{{ $inquiry['contact_number'] }}</span><span class="inquiry-phone-hint" aria-hidden="true">View</span></button>
                                    @else
                                        <span class="small text-muted">{{ $inquiry['contact_number'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="inquiry-date">
                                {{ $received ? $received->format('M j, Y · g:i A') : 'Date unavailable' }}
                            </span>
                        </div>

                        <div class="inquiry-message">
                            <h2 class="h6">{{ $inquiry['topic'] }}</h2>
                            <p class="preserve-lines mb-0">{{ $inquiry['message'] }}</p>
                        </div>

                        <div class="inquiry-footer">
                            <span class="small text-muted">{{ $inquiry['reference'] }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card clinic-card text-center p-5">
                    <h2 class="h5">No inquiries found.</h2>
                    <p class="text-muted mb-0">Messages submitted through the booking portal will appear here.</p>
                </div>
            @endforelse
            <nav aria-label="Inquiry pages" class="d-flex gap-3 align-items-center justify-content-center py-3">
                @if ($page > 1)
                    <a class="btn btn-outline-primary"
                        href="{{ route('inquiries.index', ['page' => $page - 1]) }}">Previous</a>
                @endif
                <span>Page {{ $page }} of {{ $lastPage }}</span>
                @if ($page < $lastPage)
                    <a class="btn btn-outline-primary"
                        href="{{ route('inquiries.index', ['page' => $page + 1]) }}">Next</a>
                @endif
            </nav>
        @endif
    </div>
    <div class="call-card-backdrop" id="call-card" hidden aria-hidden="true">
        <section class="call-card" role="dialog" aria-modal="true" aria-labelledby="call-card-title">
            <button type="button" class="call-card-close" aria-label="Close">&times;</button>
            <span class="eyebrow">PATIENT COMMUNICATION</span>
            <div class="call-card-icon" aria-hidden="true">&#9742;</div>
            <p class="mb-1">Call sender</p>
            <h2 id="call-card-title" class="h4"></h2>
            <a class="call-card-number" id="call-card-number" href="#"></a>
            <p class="small text-muted mt-3 mb-4">Continue to your device's phone app to place the call.</p>
            <button type="button" class="btn btn-outline-primary w-100 mt-2 call-card-cancel">Cancel</button>
        </section>
    </div>
    <script>
        (() => {
            const modal = document.getElementById('call-card');
            const title = document.getElementById('call-card-title');
            const number = document.getElementById('call-card-number');
            let lastTrigger;

            const close = () => {
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
                lastTrigger?.focus();
            };

            document.querySelectorAll('.inquiry-phone').forEach(button => button.addEventListener('click', () => {
                lastTrigger = button;
                const phone = button.dataset.phone;
                title.textContent = button.dataset.name;
                number.textContent = phone;
                number.href = `tel:${phone}`;
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                modal.querySelector('.call-card-close').focus();
            }));

            modal.querySelectorAll('.call-card-close, .call-card-cancel').forEach(button => button.addEventListener('click', close));
            modal.addEventListener('click', event => { if (event.target === modal) close(); });
            document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) close(); });
        })();
    </script>
@endsection
