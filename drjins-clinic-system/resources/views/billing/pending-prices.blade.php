@php($pendingPrices = \App\Models\Charge::where('patient_id', $patient->id)->whereNull('voided_at')->where('price_pending', true)->orderBy('charged_on')->get())
@if($pendingPrices->isNotEmpty())
    <div class="alert alert-warning">{{ $pendingPrices->count() }} service(s) need a price. Totals include priced services only.</div>
    @foreach($pendingPrices as $pendingCharge)
        <div class="border rounded p-3 mb-3">
            <strong>{{ $pendingCharge->description }}</strong>
            <p class="small text-muted">{{ \Carbon\Carbon::parse($pendingCharge->charged_on)->format('d M Y') }} &middot; Needs price</p>
            <form method="POST" action="{{ route('charges.price', [$patient, $pendingCharge]) }}" class="d-flex align-items-end gap-2 flex-wrap">
                @csrf @method('PATCH')
                <div><label for="fee-{{ $pendingCharge->id }}" class="form-label">Fee (PHP)</label>
                <input id="fee-{{ $pendingCharge->id }}" name="amount" type="number" min="0.01" max="9999999.99" step="0.01" class="form-control" required></div>
                <button class="btn btn-primary">Save fee</button>
            </form>
        </div>
    @endforeach
@endif
