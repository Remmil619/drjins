@extends('layouts.app')
@section('content')

    <section class="page-header">
        <div class="container">
            <span class="eyebrow">CLINIC ACCOUNTS</span>
            <h1>Billing & Fees</h1>
            <p class="mb-0">Track charges, partial payments, and outstanding balances in Philippine pesos.</p>
        </div>
    </section>
    <div class="container">@include('components.feedback')
        <div class="card clinic-card">
            <div class="card-body p-4">

                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="billing-search" class="form-label">Patient name or number</label>
                        <input
                            id="billing-search"
                            name="search"
                            value="{{ $search }}"
                            class="form-control"
                        >
                    </div>
                    <div class="col-md-4">
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
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
                <p class="small text-muted">Add charges from a <a href="{{ route('patients.index') }}">patient's Billing
                        tab</a>. Corrections preserve the original record; voiding a payment here does not transfer or
                    refund money.</p>
                @forelse($charges as $charge)
                    <div class="billing-patient-header">
                        <a href="{{ route('patients.show', [$charge->patient, 'tab' => 'billing']) }}">{{ $charge->patient->full_name }}
                            &middot; {{ $charge->patient->patient_no }}</a>
                    </div>@include('billing.charge')
                @empty
                    <p class="text-center text-muted py-5">No charges match this filter.</p>
                @endforelse
                {{ $charges->links() }}
            </div>
        </div>
    </div>
@endsection
