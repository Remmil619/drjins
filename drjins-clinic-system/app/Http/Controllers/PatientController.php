<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\ClinicVaccine;
use App\Models\GrowthRecord;
use App\Models\Immunization;
use App\Models\MedicalAlert;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);
        $search = $filters['search'] ?? '';

        $patients = Patient::query()
            ->when($search, function ($query, $search) {
                $query->where('patient_no', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('middle_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('guardian_name', 'like', '%'.$search.'%')
                    ->orWhere('contact_number', 'like', '%'.$search.'%');
            })
            ->latest()
            ->paginate(10)->withQueryString();

        return view('patients.index', compact('patients', 'search'));
    }

    public function create()
    {
        return view('patients.create');
    }

    public function edit(Patient $patient)
    {
        return view('patients.create', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        return $this->store($request, $patient);
    }

    public function store(Request $request, ?Patient $patient = null)
    {
        $birthDate = $request->input('birthdate');
        if (is_string($birthDate) && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $birthDate, $parts)) {
            $request->merge(['birthdate' => sprintf('%04d-%02d-%02d', (int) $parts[3], (int) $parts[2], (int) $parts[1])]);
        }
        if ($request->hasAny(['guardian_first_name', 'guardian_middle_name', 'guardian_surname'])) {
            $names = $request->validate([
                'guardian_first_name' => 'required|string|max:80',
                'guardian_middle_name' => 'nullable|string|max:80',
                'guardian_surname' => 'required|string|max:80',
            ]);
            $request->merge(['guardian_name' => implode(' ', array_filter(array_map(
                fn ($key) => trim($names[$key] ?? ''),
                ['guardian_first_name', 'guardian_middle_name', 'guardian_surname']
            )))]);
        }
        $validated = $request->validate([

            'first_name' => ['required', 'string', 'max:255'],

            'middle_name' => ['nullable', 'string', 'max:255'],

            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:20'],

            'birthdate' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],

            'sex' => ['required', 'in:Male,Female'],

            'height' => ['nullable', 'required_with:weight,temperature', 'numeric', 'gt:0', 'max:300'],

            'weight' => ['nullable', 'required_with:height,temperature', 'numeric', 'gt:0', 'max:500'],

            'temperature' => ['nullable', 'numeric', 'between:25,45'],

            'barangay' => 'nullable|required_with:municipality,province|string|max:150',

            'municipality' => 'nullable|required_with:barangay,province|string|max:150',

            'province' => 'nullable|required_with:barangay,municipality|string|max:150',

            'address' => ['nullable', 'string', 'max:500'],

            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_first_name' => 'nullable|string|max:80',
            'guardian_middle_name' => 'nullable|string|max:80',
            'guardian_surname' => 'nullable|string|max:80',

            'guardian_relationship' => ['nullable', 'string', 'max:100'],

            'contact_number' => ['required', 'string', 'max:20'],

            'emergency_contact' => ['nullable', 'string', 'max:20'],

            'status' => ['required', 'in:Active,Inactive'],

        ]);

        if (! $patient) {
            $validated['patient_no'] = $this->generatePatientNo();
        }

        $patient = \Illuminate\Support\Facades\DB::transaction(function () use ($patient, $validated, $request) {
            $record = $patient ?? new Patient;
            $record->fill($validated);
            $recordVitals = ! $record->exists || $record->isDirty(['height', 'weight', 'temperature']);
            $record->save();
            if ($recordVitals && filled($validated['height'] ?? null) && filled($validated['weight'] ?? null)) {
                GrowthRecord::create([
                    'patient_id' => $record->id, 'user_id' => $request->user()->id,
                    'measured_on' => today()->toDateString(),
                    'height_cm' => $validated['height'], 'weight_kg' => $validated['weight'],
                    'temperature_c' => $validated['temperature'] ?? null,
                    'notes' => 'Recorded by staff in the patient registration form.',
                ]);
            }
            return $record;
        });

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'Patient record saved successfully.');
    }

    public function show(Request $request, Patient $patient)
    {
        $request->validate([
            'tab' => 'nullable|in:consultations,growth,immunizations,alerts,billing,history',
        ]);
        $tab = $request->input('tab') ?: 'consultations';
        $patient->load('appointment');
        $latestVitals = GrowthRecord::where('patient_id', $patient->id)->whereNull('voided_at')
            ->orderByDesc('measured_on')->orderByDesc('id')->first();
        $latestVaccination = Immunization::where('patient_id', $patient->id)->whereNull('voided_at')
            ->orderByDesc('administered_on')->orderByDesc('id')->first();
        $bookingAllergies = \App\Models\OnlineAppointment::where(function ($query) use ($patient) {
            $query->where('patient_id', $patient->id);
            if ($patient->online_appointment_no) {
                $query->orWhere('appointment_no', $patient->online_appointment_no);
            }
        })->whereNotNull('allergies')->orderByDesc('appointment_date')->orderByDesc('id')->get()
            ->filter(fn ($booking) => filled(trim($booking->allergies)))
            ->unique(fn ($booking) => mb_strtolower(trim($booking->allergies)));
        $alerts = MedicalAlert::where('patient_id', $patient->id)->whereNull('voided_at')->get();
        $activeQueue = QueueEntry::where('patient_id', $patient->id)->whereDate('visit_date', today())->whereIn('status', ['waiting', 'in_consultation'])->first();
        if ($activeQueue) {
            $activeQueue->daily_number = QueueEntry::whereDate('visit_date', $activeQueue->visit_date)
                ->where('id', '<=', $activeQueue->id)->count();
        }
        $consultationRecords = $patient->consultations()->with('author')->paginate(10, ['*'], 'consultations_page')->withQueryString();
        $growthRecords = GrowthRecord::where('patient_id', $patient->id)->orderByDesc('measured_on')->orderByDesc('id')->paginate(15, ['*'], 'growth_page')->withQueryString();
        $immunizationRecords = Immunization::where('patient_id', $patient->id)->orderByDesc('administered_on')->orderByDesc('id')->paginate(15, ['*'], 'immunizations_page')->withQueryString();
        $alertRecords = MedicalAlert::where('patient_id', $patient->id)->latest()->paginate(15, ['*'], 'alerts_page')->withQueryString();
        $billingRecords = Charge::with('payments')->where('patient_id', $patient->id)->latest()->paginate(15, ['*'], 'billing_page')->withQueryString();
        $fees = Charge::where('patient_id', $patient->id)->whereNull('voided_at')->sum('amount_centavos');
        $paid = Payment::whereNull('voided_at')->whereHas('charge', fn ($query) => $query->where('patient_id', $patient->id)->whereNull('voided_at'))->sum('amount_centavos');
        $clinicVaccines = ClinicVaccine::orderBy('name')->get();
        $doctors = User::where('role', 'doctor')->orderBy('name')->get();
        $clinicVaccines = $clinicVaccines->filter(fn ($vaccine) => $vaccine->available || $immunizationRecords->getCollection()->contains('vaccine', $vaccine->name))->values();
        $cartCharges = Charge::with('payments')->where('patient_id', $patient->id)->whereNull('voided_at')->orderBy('id')->get()->filter(fn ($charge) => $charge->balance_centavos > 0);

        return view('patients.show', compact('patient', 'tab', 'alerts', 'bookingAllergies', 'activeQueue', 'consultationRecords', 'growthRecords', 'immunizationRecords', 'alertRecords', 'billingRecords', 'fees', 'paid', 'clinicVaccines', 'doctors', 'cartCharges', 'latestVitals', 'latestVaccination'));
    }

    private function generatePatientNo()
    {
        do {
            $patientNo = 'PAT-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (Patient::where('patient_no', $patientNo)->exists());

        return $patientNo;
    }
}
