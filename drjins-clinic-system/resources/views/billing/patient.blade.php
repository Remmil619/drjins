<div class="card clinic-card">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Billing & Fees</h2>
            <a href="{{ route('billing.print', $patient) }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Print statement</a>
        </div>
        <div class="row g-3 my-3">
            @foreach (['Total fees' => $fees, 'Paid' => $paid, 'Balance' => $fees - $paid] as $label => $amount)
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="small text-muted">{{ $label }}</div>
                        <strong>PHP
                            {{ number_format($amount / 100, 2) }}</strong>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="small text-muted">Internal billing statement. Payment records do not replace an official invoice or
            receipt. Enter the clinic's actual agreed fees.</p>
        @include('billing.pending-prices')
        @include('billing.cart')


    </div>
</div>

<details class="mt-4">
    <summary class="fw-semibold">Billing history</summary>
    @forelse($records as $charge)
    @include('billing.charge', ['hidePaymentForm' => true]) @empty
        <p class="text-muted py-3">No billing history.</p>
    @endforelse
</details>
