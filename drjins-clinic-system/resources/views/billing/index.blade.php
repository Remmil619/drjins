@extends('layouts.app')
@section('content')

    <section class="page-header">
        <div class="container billing-page-heading">
            <span class="eyebrow">CLINIC ACCOUNTS</span>
            <h1>Billing & Fees</h1>
            <p class="mb-0">Track charges, partial payments, and outstanding balances in Philippine pesos.</p>
        </div>
    </section>
    <div class="container billing-page-shell">@include('components.feedback')
        <div class="card clinic-card">
            <div class="card-body p-4">

                <form data-auto-filter method="GET" class="auto-filter-bar row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="billing-search" class="form-label">Patient name or number</label>
                        <input
                            id="billing-search"
                            name="search"
                            value="{{ $search }}"
                            class="form-control"
                        >
                    </div>
                    <div class="col-md-6">
                        <label for="billing-status" class="form-label">Account filter</label>
                        <select
                            id="billing-status"
                            class="form-select"
                            name="status"
                        >
                            @foreach (['unpaid' => 'Outstanding balance', 'paid' => 'Paid', 'void' => 'Voided', 'all' => 'All charges'] as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                </form>
                @forelse($charges as $charge)
                    @include('billing.charge', ['showPatientName' => true])
                @empty
                    <p class="text-center text-muted py-5">No charges match this filter.</p>
                @endforelse
                {{ $charges->links() }}
            </div>
        </div>
    </div>
@endsection
