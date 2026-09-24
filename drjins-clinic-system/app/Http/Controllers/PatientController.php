<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100']);
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
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'], 'suffix' => ['nullable','string','max:20'],

            'birthdate' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sex' => ['required', 'in:Male,Female'],
            'height' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'string', 'max:50'],
            'temperature' => ['nullable', 'numeric', 'between:25,45'],
            'address' => ['nullable', 'string', 'max:500'],

            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_relationship' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['required', 'string', 'max:20'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        if (! $patient) {
            $validated['patient_no'] = $this->generatePatientNo();
        }

        if ($patient) {
            $patient->update($validated);
        } else {
            $patient = Patient::create($validated);
        }

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'Patient record saved successfully.');
    }

    public function show(Request $request, Patient $patient)
    {
        $request->validate(['tab'=>'nullable|in:consultations,growth,immunizations,alerts,billing']);
        $tab=$request->input('tab') ?: 'consultations';
        $patient->load('appointment');
        $alerts=\App\Models\MedicalAlert::where('patient_id',$patient->id)->whereNull('voided_at')->get();
        $activeQueue=\App\Models\QueueEntry::where('patient_id',$patient->id)->whereDate('visit_date',today())->whereIn('status',['waiting','in_consultation'])->first();
        $records=match($tab) {
            'growth'=>\App\Models\GrowthRecord::where('patient_id',$patient->id)->orderByDesc('measured_on')->orderByDesc('id')->paginate(15)->withQueryString(),
            'immunizations'=>\App\Models\Immunization::where('patient_id',$patient->id)->orderByDesc('administered_on')->orderByDesc('id')->paginate(15)->withQueryString(),
            'alerts'=>\App\Models\MedicalAlert::where('patient_id',$patient->id)->latest()->paginate(15)->withQueryString(),
            'billing'=>\App\Models\Charge::with('payments')->where('patient_id',$patient->id)->latest()->paginate(15)->withQueryString(),
            default=>$patient->consultations()->with('author')->paginate(10)->withQueryString(),
        };
        return view('patients.show',compact('patient','tab','alerts','activeQueue','records'));
    }
    private function generatePatientNo()
    {
        do {
            $patientNo = 'PAT-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (Patient::where('patient_no', $patientNo)->exists());

        return $patientNo;
    }
}

