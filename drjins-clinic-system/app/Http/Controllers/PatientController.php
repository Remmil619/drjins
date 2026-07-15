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
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'birthdate' => 'required|date|before_or_equal:today',
            'sex' => 'required|in:Male,Female',
            'address' => 'nullable|string|max:1000',
            'guardian_name' => 'required|string|max:255',
            'guardian_relationship' => 'nullable|string|max:255',
            'contact_number' => 'required|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['patient_no'] = 'PT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        Patient::create($validated);

        return redirect()
            ->route('patients.index')
            ->with('success', 'Patient record created successfully.');
    }

    public function show(Patient $patient)
    {
        return view('patients.show', compact('patient'));
    }
}