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
        @if ($error)
            <div class="alert alert-warning" role="alert">{{ $error }}</div>
        @else
            <div class="inquiry-toolbar">
                <h2 class="h6 mb-0">Inbox <span class="inquiry-count">{{ count($inquiries) }}</span></h2>
                <p class="small text-muted mb-0">Contact the sender by phone to respond.</p>
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
                                    <span class="small text-muted">{{ $inquiry['contact_number'] }}</span>
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
                            @if ($phone)
                                <a class="btn btn-primary btn-sm" href="tel:{{ $phone }}" aria-label="Call {{ $inquiry['name'] }}">Call sender</a>
                            @endif
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
@endsection
