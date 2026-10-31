<h3 class="h5">
    Services &amp; payments</h3>
<p class="small text-muted">Saved consultations and vaccinations given at this clinic appear automatically. Partial payments apply to the oldest added items
    first.</p>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Service / fee</th>
                <th>Date</th>
                <th class="text-end">Fee</th>
                <th class="text-end">Remaining</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cartCharges as $charge)
                <tr>
                    <td>{{ $charge->description }}</td>
                    <td>{{ \Carbon\Carbon::parse($charge->charged_on)->format('d M Y') }}</td>
                    <td class="text-end">PHP {{ number_format($charge->amount_centavos / 100, 2) }}</td>
                    <td class="text-end">PHP {{ number_format($charge->balance_centavos / 100, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-muted py-4">No priced services awaiting payment.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Total to pay</th>
                <th class="text-end">PHP {{ number_format($cartCharges->sum('balance_centavos') / 100, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>
@if ($cartCharges->isNotEmpty())

    <form
        method="POST"
        action="{{ route('billing.checkout', $patient) }}"
        class="record-form mt-3"
    >
        @csrf
        <input
            type="hidden"
            name="request_key"
            value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}"
        >
        @foreach ($cartCharges as $charge)
            <input
                type="hidden"
                name="charge_ids[]"
                value="{{ $charge->id }}"
            >
        @endforeach
        <h3 class="h6">Payment</h3>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="checkout-amount" class="form-label">Payment amount (PHP)</label>
                <input
                    id="checkout-amount"
                    name="amount"
                    type="number"
                    min="0.01"
                    step="0.01"
                    max="{{ $cartCharges->sum('balance_centavos') / 100 }}"
                    value="{{ old('amount', number_format($cartCharges->sum('balance_centavos') / 100, 2, '.', '')) }}"
                    class="form-control"
                    required
                >
            </div>
            <div class="col-md-6">
                <label for="checkout-date" class="form-label">Payment date</label>
                <input
                    id="checkout-date"
                    name="paid_on"
                    type="date"
                    min="{{ $cartCharges->max('charged_on') }}"
                    max="{{ today()->toDateString() }}"
                    value="{{ old('paid_on', today()->toDateString()) }}"
                    class="form-control"
                    required
                >
            </div>
            <div class="col-md-6">
                <label for="checkout-method" class="form-label">Method</label>
                <select
                    id="checkout-method"
                    name="method"
                    class="form-select"
                >
                    @foreach (['Cash', 'GCash', 'Bank transfer', 'Card'] as $method)
                        <option @selected(old('method', 'Cash') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="checkout-reference" class="form-label">Reference (required for
                    non-cash)</label>
                <input
                    id="checkout-reference"
                    name="reference"
                    maxlength="150"
                    value="{{ old('reference') }}"
                    class="form-control"
                >
            </div>
        </div>
        <button class="btn btn-primary mt-3">Record payment</button>
    </form>
@endif
