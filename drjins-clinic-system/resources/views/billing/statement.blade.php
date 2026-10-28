<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Billing statement - {{ $patient->patient_no }}</title>
    <style>
        body { font: 14px Arial, sans-serif; color: #18383d; max-width: 900px; margin: 32px auto; padding: 0 20px; }
        header { border-bottom: 2px solid #237c80; padding-bottom: 16px; }
        h1 { font-size: 24px; margin-bottom: 8px; }
        h2 { font-size: 20px; }
        .details { margin: 24px 0; line-height: 1.7; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0 24px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #ccd7d9; text-align: left; overflow-wrap: anywhere; }
        .money { text-align: right; white-space: nowrap; }
        .totals { margin-left: auto; max-width: 340px; }
        .totals p { display: flex; justify-content: space-between; gap: 20px; }
        .balance { border-top: 2px solid #237c80; padding-top: 12px; font-weight: bold; }
        .actions { display: flex; gap: 16px; align-items: center; margin-bottom: 24px; }
        button { padding: 10px 18px; cursor: pointer; }
        footer { margin-top: 30px; font-size: 12px; }
        @page { size: A4; margin: 15mm; }
        @media print {
            body { margin: 0; padding: 0; max-width: none; color: #000; }
            .actions { display: none; }
            thead { display: table-header-group; }
            tr, .totals { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('patients.show', [$patient, 'tab' => 'billing']) }}">Back to billing</a>
    </div>
    <header>
        <h1>Dr. Jn's Children's Clinic</h1>
        <div>Abuyog, Leyte</div>
        <h2>Billing statement</h2>
    </header>
    <div class="details">
        <div><strong>Patient:</strong> {{ $patient->full_name }}</div>
        <div><strong>Patient number:</strong> {{ $patient->patient_no }}</div>
        <div><strong>Guardian:</strong> {{ $patient->guardian_name }}</div>
        <div><strong>Generated:</strong> {{ now()->format('F j, Y g:i A') }}</div>
    </div>
    <table>
        <thead><tr><th>Date</th><th>Service / fee</th><th class="money">Fee (PHP)</th><th class="money">Paid (PHP)</th><th class="money">Balance (PHP)</th></tr></thead>
        <tbody>
            @forelse ($charges as $charge)
                @php($collected = $charge->payments->sum('amount_centavos'))
                <tr>
                    <td>{{ $charge->charged_on }}</td><td>{{ $charge->description }}</td>
                    <td class="money">{{ $charge->price_pending ? 'Needs price' : number_format($charge->amount_centavos / 100, 2) }}</td>
                    <td class="money">{{ number_format($collected / 100, 2) }}</td>
                    <td class="money">{{ $charge->price_pending ? 'Pending' : number_format(($charge->amount_centavos - $collected) / 100, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No charges recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="totals">
        @if($charges->contains('price_pending', true))<p>Totals exclude services marked Needs price.</p>@endif
        <p><span>Total fees</span><strong>PHP {{ number_format($fees / 100, 2) }}</strong></p>
        <p><span>Total paid</span><strong>PHP {{ number_format($paid / 100, 2) }}</strong></p>
        <p class="balance"><span>Balance</span><span>PHP {{ number_format(($fees - $paid) / 100, 2) }}</span></p>
    </div>
    <h2>Payment history</h2>
    <table>
        <thead><tr><th>Date</th><th>Service / fee</th><th>Method / reference</th><th class="money">Paid (PHP)</th></tr></thead>
        <tbody>
            @forelse ($charges->flatMap(fn ($charge) => $charge->payments->map(fn ($payment) => ['charge' => $charge, 'payment' => $payment])) as $entry)
                <tr>
                    <td>{{ $entry['payment']->paid_on }}</td>
                    <td>{{ $entry['charge']->description }}</td>
                    <td>{{ $entry['payment']->method }} @if ($entry['payment']->reference)<br>{{ $entry['payment']->reference }}@endif</td>
                    <td class="money">{{ number_format($entry['payment']->amount_centavos / 100, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No payments recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    <footer>Internal billing statement. Payment records do not replace an official invoice or receipt. Voided entries are excluded.</footer>
</body>
</html>
