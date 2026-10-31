<details class="billing-charge-card {{ $charge->voided_at ? 'record-void' : '' }}"
    @if((string) old('billing_charge_id') === (string) $charge->id || (string) old('billing_void_charge_id') === (string) $charge->id || (string) old('billing_void_payment_charge_id') === (string) $charge->id) open @endif>
    <summary class="billing-charge-summary">
        <span class="billing-charge-main">
            @if($showPatientName ?? false)
                <a class="billing-charge-patient" href="{{ route('patients.show', [$charge->patient, 'tab' => 'billing']) }}">{{ $charge->patient->full_name }} &middot; {{ $charge->patient->patient_no }}</a>
            @endif
            <strong class="billing-charge-description">CHG-{{ $charge->id }} &middot; {{ $charge->description }}</strong>
            <small class="billing-charge-date">{{ \Carbon\Carbon::parse($charge->charged_on)->format('d M Y') }}</small>
        </span>
        <span class="billing-charge-amount-block">
            <strong class="billing-charge-amount">{{ $charge->price_pending ? 'Needs price' : 'PHP ' . number_format($charge->amount_centavos / 100, 2) }}</strong>
            <small class="billing-charge-balance">{{ $charge->price_pending ? 'Set the vaccine price before collecting payment.' : 'Balance: PHP ' . number_format($charge->balance_centavos / 100, 2) }}</small>
        </span>
        <span class="billing-charge-toggle">Details</span>
    </summary>

    <div class="billing-charge-details">
        @if ($charge->voided_at)
            <p class="text-danger small mt-2">Voided: {{ $charge->void_reason }}</p>
        @endif

        @foreach ($charge->payments as $payment)
            <div class="payment-line small">
                <span>PAY-{{ $payment->id }} &middot; {{ \Carbon\Carbon::parse($payment->paid_on)->format('d M Y') }} &middot; {{ $payment->method }}
                    &middot; PHP {{ number_format($payment->amount_centavos / 100, 2) }} @if ($payment->reference)
                        &middot; {{ $payment->reference }}
                    @endif
                </span>
                @if ($payment->voided_at)
                    <span class="text-danger d-block">Voided: {{ $payment->void_reason }}</span>
                @else
                    <details class="correction-action" @if((string) old('billing_void_payment_id') === (string) $payment->id) open @endif>
                        <summary>Void payment record</summary>
                        <p class="small text-muted mt-2">Marks this payment as invalid and keeps the original in history. To correct the payment, void it and record a new payment. This does not issue a refund.</p>
                        <form class="d-flex flex-wrap gap-2 mt-2" method="POST" action="{{ route('billing.void', ['payment', $payment->id]) }}">
                            @csrf
                            <input type="hidden" name="billing_void_payment_charge_id" value="{{ $charge->id }}">
                            <input type="hidden" name="billing_void_payment_id" value="{{ $payment->id }}">
                            <label class="visually-hidden" for="void-payment-{{ $payment->id }}">Reason for voiding</label>
                            <input id="void-payment-{{ $payment->id }}" class="form-control" name="void_reason" minlength="5" maxlength="500" required value="{{ old('billing_void_payment_id') == $payment->id ? old('void_reason') : '' }}" placeholder="Reason for voiding payment">
                            <button class="btn btn-sm btn-outline-danger">Void payment record</button>
                        </form>
                    </details>
                @endif
            </div>
        @endforeach

        @if (!($hidePaymentForm ?? false) && !$charge->voided_at && $charge->balance_centavos > 0)
            <details class="record-form mt-3" @if((string) old('billing_charge_id') === (string) $charge->id) open @endif>
                <summary class="fw-semibold">Record payment</summary>
                <form method="POST" action="{{ route('payments.store', $charge) }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="billing_charge_id" value="{{ $charge->id }}">
                    <input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="amount-{{ $charge->id }}">Amount (PHP)</label>
                            <input id="amount-{{ $charge->id }}" type="number" step=".01" min=".01" max="{{ $charge->balance_centavos / 100 }}" name="amount" class="form-control" required value="{{ old('billing_charge_id') == $charge->id ? old('amount') : '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="paid-{{ $charge->id }}">Payment date</label>
                            <input id="paid-{{ $charge->id }}" type="date" name="paid_on" class="form-control" value="{{ old('billing_charge_id') == $charge->id ? old('paid_on', today()->toDateString()) : today()->toDateString() }}" min="{{ $charge->charged_on }}" max="{{ today()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="method-{{ $charge->id }}">Method</label>
                            <select id="method-{{ $charge->id }}" class="form-select" name="method">
                                @foreach (['Cash', 'GCash', 'Bank transfer', 'Card'] as $method)
                                    <option @selected(old('billing_charge_id') == $charge->id && old('method') === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="reference-{{ $charge->id }}">Reference (required for non-cash)</label>
                            <input id="reference-{{ $charge->id }}" class="form-control" name="reference" maxlength="150" value="{{ old('billing_charge_id') == $charge->id ? old('reference') : '' }}">
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3">Record payment</button>
                </form>
            </details>
        @endif

        @if (!$charge->voided_at && $charge->paid_centavos === 0)
            <details class="correction-action mt-3" @if((string) old('billing_void_charge_id') === (string) $charge->id) open @endif>
                <summary>Void charge</summary>
                <p class="small text-muted mt-2">Marks this charge as invalid and keeps the original in history. To correct it, void this charge and add a new charge.</p>
                <form method="POST" action="{{ route('billing.void', ['charge', $charge->id]) }}" class="d-flex flex-wrap gap-2 mt-2">
                    @csrf
                    <input type="hidden" name="billing_void_charge_id" value="{{ $charge->id }}">
                    <label class="visually-hidden" for="void-charge-{{ $charge->id }}">Reason</label>
                    <input id="void-charge-{{ $charge->id }}" name="void_reason" class="form-control" minlength="5" maxlength="500" required value="{{ old('billing_void_charge_id') == $charge->id ? old('void_reason') : '' }}" placeholder="Reason for voiding charge">
                    <button class="btn btn-sm btn-outline-danger">Void charge</button>
                </form>
            </details>
        @endif
    </div>
</details>
