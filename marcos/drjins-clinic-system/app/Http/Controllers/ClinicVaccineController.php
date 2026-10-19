<?php

namespace App\Http\Controllers;

use App\Models\ClinicVaccine;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicVaccineController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $request->merge(['vaccine_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('vaccine_name')))]);
        $data = $request->validate([
            'vaccine_name' => ['required', 'string', 'max:150', Rule::unique('clinic_vaccines', 'name')],
            'available' => ['required', 'boolean'],
        ]);
        if (ClinicVaccine::whereRaw('LOWER(name) = ?', [mb_strtolower($data['vaccine_name'])])->exists()) {
            return back()->withErrors(['vaccine_name' => 'This vaccine is already in the clinic list.'])->withInput();
        }
        ClinicVaccine::create(['name' => $data['vaccine_name'], 'available' => $data['available']]);
        return redirect()->route('patients.show', [$patient, 'tab' => 'immunizations'])->with('success', 'Vaccine added to the clinic list.');
    }

    public function update(Request $request, Patient $patient, ClinicVaccine $vaccine)
    {
        $data = $request->validate(['available' => ['required', 'boolean']]);
        $vaccine->update($data);
        return redirect()->route('patients.show', [$patient, 'tab' => 'immunizations'])->with('success', 'Vaccine availability updated.');
    }
}
