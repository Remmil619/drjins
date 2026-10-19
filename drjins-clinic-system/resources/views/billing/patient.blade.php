<div class="card clinic-card">
    <div class="card-body p-4">
        <h2 class="h5">Billing & Fees</h2>
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
        @include('billing.cart')

        <details class="record-form mt-4" @if ($errors->any()) open @endif>
            <summary class="fw-semibold">+ Add item to cart</summary>

            <form
                method="POST"
                action="{{ route('charges.store', $patient) }}"
                class="mt-3"
            >
                @csrf
                <input
                    type="hidden"
                    name="request_key"
                    value="{{ (string) \Illuminate\Support\Str::uuid() }}"
                >
                <div class="row g-3">
                    <div class="col-12">
                        <label for="charge-description" class="form-label">Service / fee</label>
                        <select
                            id="charge-description"
                            name="description"
                            class="form-select"
                            required
                        >
                            <option value="">Select a service or vaccine</option>
                            <option value="CONSULTATION FEE" @selected(old('description') === 'CONSULTATION FEE')>CONSULTATION FEE</option>
                            @php($availableFeeVaccines = \App\Models\ClinicVaccine::where('available', true)->orderBy('name')->get())
                            @if ($availableFeeVaccines->isNotEmpty())
                                <optgroup label="VACCINATION FEE">
                                    @foreach ($availableFeeVaccines as $feeVaccine)
                                        @php($feeDescription = 'VACCINATION FEE - ' . mb_strtoupper($feeVaccine->name))
                                        <option value="{{ $feeDescription }}" @selected(old('description') === $feeDescription)>
                                            {{ mb_strtoupper($feeVaccine->name) }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <small class="text-muted">Only vaccines marked available in the Vaccinations tab are
                            listed.</small>
                    </div>
                    <div class="col-md-6">
                        <label for="charge-date" class="form-label">Charge date</label>
                        <input
                            id="charge-date"
                            name="charged_on"
                            type="date"
                            class="form-control"
                            required
                            value="{{ old('charged_on', today()->toDateString()) }}"
                            max="{{ today()->toDateString() }}"
                        >
                    </div>
                    <div class="col-md-6">
                        <label for="charge-amount" class="form-label">Amount (PHP)</label>
                        <input
                            id="charge-amount"
                            type="number"
                            name="amount"
                            step=".01"
                            min=".01"
                            max="9999999.99"
                            required
                            class="form-control"
                            value="{{ old('amount') }}"
                        >
                    </div>
                </div>
                <button class="btn btn-primary mt-3">Add to cart</button>
            </form>
        </details>
    </div>
</div>

<details class="mt-4">
    <summary class="fw-semibold">Billing history & corrections</summary>
    @forelse($records as $charge)
    @include('billing.charge', ['hidePaymentForm' => true]) @empty
        <p class="text-muted py-3">No billing history.</p>
    @endforelse
</details>
