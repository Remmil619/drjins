@extends('layouts.app')
@section('content')
    <style>
        @media print {
            @page { size: A4 portrait; margin: 12mm; }
            .sales-report { width: 100% !important; max-width: none !important; }
            .sales-summary { display: grid !important; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
            .sales-breakdown { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
            .sales-summary > *, .sales-breakdown > * { width: auto !important; margin: 0 !important; }
            .sales-report .clinic-card { height: auto !important; overflow: visible !important; box-shadow: none !important; break-inside: avoid; page-break-inside: avoid; }
            .sales-report .clinic-card > .card-body { padding: 12px !important; }
            .sales-summary .h3 { font-size: 16pt; }
            .sales-details { margin-top: 14px; }
            .sales-details thead { display: table-header-group; }
            .sales-details tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
    <section class="page-header">
        <div class="container">
            <span class="eyebrow">CLINIC FINANCE</span>
            <h1>Sales</h1>
            <p class="mb-0">Track payments received by the clinic for a selected period.</p>
        </div>
    </section>
    <div class="container sales-report">
        @include('components.feedback')
        <form data-auto-filter class="auto-filter-bar d-flex flex-wrap gap-3 align-items-end mb-4" method="GET">
            <div>
                <label for="sales-from" class="form-label">From</label>
                <input id="sales-from" type="date" name="from" class="form-control" value="{{ $from }}">
            </div>
            <div>
                <label for="sales-to" class="form-label">Through</label>
                <input id="sales-to" type="date" name="to" class="form-control" value="{{ $to }}">
            </div>
            
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">Print</button>
        </form>
        <p class="small text-muted">Showing {{ $from }} through {{ $to }}. Voided payments and payments for voided charges are excluded. Income is counted on the date payment was received.</p>

        <div class="row g-3 mb-4 sales-summary">
            <div class="col-md-4">
                <div class="card clinic-card h-100"><div class="card-body p-4">
                    <p class="small text-muted mb-2">Total collected</p>
                    <strong class="h3">PHP {{ number_format($totalSales / 100, 2) }}</strong>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card clinic-card h-100"><div class="card-body p-4">
                    <p class="small text-muted mb-2">Payments received</p>
                    <strong class="h3">{{ $payments->count() }}</strong>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card clinic-card h-100"><div class="card-body p-4">
                    <p class="small text-muted mb-2">Daily average</p>
                    <strong class="h3">PHP {{ number_format($totalSales / max(1, \Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1) / 100, 2) }}</strong>
                </div></div>
            </div>
        </div>

        <div class="row g-4 mb-4 sales-breakdown">
            <div class="col-lg-6">
                <div class="card clinic-card h-100"><div class="card-body p-4">
                    <h2 class="h5">Sales by day</h2>
                    @forelse($salesByDay as $day => $amount)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>{{ \Carbon\Carbon::parse($day)->format('d M Y') }}</span>
                            <strong>PHP {{ number_format($amount / 100, 2) }}</strong>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No payments in this period.</p>
                    @endforelse
                </div></div>
            </div>
            <div class="col-lg-6">
                <div class="card clinic-card h-100"><div class="card-body p-4">
                    <h2 class="h5">Sales by payment method</h2>
                    @forelse($salesByMethod as $method => $amount)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>{{ $method }}</span>
                            <strong>PHP {{ number_format($amount / 100, 2) }}</strong>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No payments in this period.</p>
                    @endforelse
                </div></div>
            </div>
        </div>

        <div class="card clinic-card sales-details"><div class="card-body p-4">
            <h2 class="h5 mb-3">Payment details</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Date</th><th>Patient</th><th>Service</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($payment->paid_on)->format('d M Y') }}</td>
                                <td>
                                    @if($payment->charge->patient)
                                        <a href="{{ route('patients.show', [$payment->charge->patient, 'tab' => 'billing']) }}">{{ $payment->charge->patient->full_name }}</a>
                                    @else
                                        Patient record unavailable
                                    @endif
                                </td>
                                <td>{{ $payment->charge->description }}</td>
                                <td>{{ $payment->method }}</td>
                                <td>{{ $payment->reference ?: '—' }}</td>
                                <td class="text-end">PHP {{ number_format($payment->amount_centavos / 100, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">No sales recorded for this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if($payments->isNotEmpty())
                        <tfoot><tr><th colspan="5" class="text-end">Total</th><th class="text-end">PHP {{ number_format($totalSales / 100, 2) }}</th></tr></tfoot>
                    @endif
                </table>
            </div>
        </div></div>
    </div>
@endsection
