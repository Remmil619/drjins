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
        $request->merge([
            'vaccine_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('vaccine_name'))),
        ]);
        $data = $request->validate([

            'vaccine_name' => ['required', 'string', 'max:150', Rule::unique('clinic_vaccines', 'name')],

            'available' => ['required', 'boolean'],
            'price' => ['nullable', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],

        ]);
        if (ClinicVaccine::whereRaw('LOWER(name) = ?', [mb_strtolower($data['vaccine_name'])])->exists()) {
            return back()->withErrors([
                'vaccine_name' => 'This vaccine is already in the clinic list.',
            ])->withInput();
        }
        $vaccine = ClinicVaccine::create([
            'name' => $data['vaccine_name'],
            'available' => $data['available'],
            'price_centavos' => $this->price($data['price'] ?? null),
        ]);
        $this->pricePending($vaccine);

        return redirect()->route('patients.show', [
            $patient,
            'tab' => 'immunizations',
        ])->with('keep_vaccine_manager', true)->with('success', 'Vaccine added to the clinic list.');
    }

    public function update(Request $request, Patient $patient, ClinicVaccine $vaccine)
    {
        $data = $request->validate([
            'available' => ['sometimes', 'required', 'boolean'],
            'price' => ['sometimes', 'required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
        ]);
        if (array_key_exists('price', $data)) {
            $data['price_centavos'] = $this->price($data['price']);
            unset($data['price']);
        }
        $vaccine->update($data);
        $this->pricePending($vaccine);

        return redirect()->route('patients.show', [
            $patient,
            'tab' => 'immunizations',
        ])->with('keep_vaccine_manager', true)->with('success', 'Vaccine settings saved.');
    }

    private function price(?string $value): ?int
    {
        if ($value === null || $value === '') return null;
        [$whole, $decimal] = array_pad(explode('.', $value, 2), 2, '');
        return (int) $whole * 100 + (int) str_pad($decimal, 2, '0');
    }

    private function pricePending(ClinicVaccine $vaccine): void
    {
        if ($vaccine->price_centavos === null) return;
        $ids = \App\Models\Immunization::whereRaw('LOWER(vaccine) = ?', [mb_strtolower($vaccine->name)])
            ->where('source', 'clinic')->whereNull('voided_at')->pluck('id');
        foreach ($ids as $id) {
            \App\Models\Charge::where('service_key', 'vaccination:'.$id)->where('price_pending', true)->whereNull('voided_at')
                ->update(['amount_centavos' => $vaccine->price_centavos, 'price_pending' => false]);
        }
    }
}
