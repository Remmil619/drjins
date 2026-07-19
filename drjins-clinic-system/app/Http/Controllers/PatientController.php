<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $patients = Patient::query()
            ->when($search, function ($query, $search) {
                $query->where('patient_no', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('middle_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('guardian_name', 'like', '%' . $search . '%')
                    ->orWhere('contact_number', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('patients.index', compact('patients', 'search'));
    }

    public function create()
    {
        return view('patients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],

            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'sex' => ['nullable', 'in:Male,Female'],
            'height' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],

            'guardian_name' => ['nullable', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
        ]);

        $validated['patient_no'] = $this->generatePatientNo();
        $validated['status'] = 'Active';

        Patient::create($validated);

        return redirect()
            ->route('patients.index')
            ->with('success', 'Patient record created successfully.');
    }

    public function show(Patient $patient)
    {
        return view('patients.show', compact('patient'));
    }

    private function generatePatientNo()
    {
        do {
            $patientNo = 'PAT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Patient::where('patient_no', $patientNo)->exists());

        return $patientNo;
    }
}