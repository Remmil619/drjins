@php($documentTitle = ($printConsultation ?? false) ? 'Consultation' : 'Prescription')
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle }} — {{ $patient->patient_no }}</title>
    <link rel="stylesheet" href="{{ asset('css/clinical-print.css') }}?v={{ filemtime(public_path('css/clinical-print.css')) }}">
</head>
<body>
    <nav class="actions" aria-label="Document actions">
        <a class="document-button secondary" href="{{ route('patients.show', $patient) }}">&larr; Back to patient</a>
        <button class="document-button" type="button" onclick="window.print()">Print {{ strtolower($documentTitle) }}</button>
    </nav>
    <main class="document">
        <header class="document-header">
            <div><p class="eyebrow">PATIENT CARE</p><h1>Dr. Jn's Clinic</h1><p class="document-type">{{ $documentTitle }}</p></div>
            <div class="visit-date"><span>Date of visit</span><strong>{{ \Carbon\Carbon::parse($consultation->visit_date)->format('F j, Y') }}</strong></div>
        </header>
        <dl class="patient-details">
            @foreach(['Patient' => $patient->full_name, 'Patient number' => $patient->patient_no, 'Birthdate' => \Carbon\Carbon::parse($patient->birthdate)->format('F j, Y'), 'Sex' => $patient->sex, 'Guardian' => $patient->guardian_name, 'Address' => ($patient->address ?: 'Not recorded')] as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>
        @if($printConsultation ?? false)
            @foreach(['complaint' => 'Reason for visit', 'assessment' => 'Assessment', 'plan' => 'Care plan', 'prescription' => 'Prescription notes', 'follow_up_date' => 'Follow-up date'] as $field => $label)
                @if(filled($consultation->$field))
                    <section class="note-section"><h2>{{ $label }}</h2><div class="note-text">{{ $field === 'follow_up_date' ? \Carbon\Carbon::parse($consultation->$field)->format('F j, Y') : $consultation->$field }}</div></section>
                @endif
            @endforeach
        @else
            <section class="note-section prescription-section"><h2 class="rx" aria-label="Prescription">&#8478;</h2><div class="note-text">{{ $consultation->prescription }}</div></section>
        @endif
        <footer class="signature">
            <div class="signature-line">{{ ($printConsultation ?? false) ? 'Attending physician / Signature' : 'Prescribing physician / Signature' }}</div>
            <p>License no.: <span class="blank-line"></span></p>
            <p>PTR no.: <span class="blank-line"></span></p>
        </footer>
    </main>
</body>
</html>