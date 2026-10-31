<?php

namespace App\Http\Controllers;

use App\Models\ClinicVaccine;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicVaccineController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:150',
            'availability' => 'nullable|in:all,available,unavailable',
        ]);
        $search = $filters['search'] ?? '';
        $availability = $filters['availability'] ?? 'all';
        $vaccines = ClinicVaccine::query()
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($availability !== 'all', fn ($query) => $query->where('available', $availability === 'available'))
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('vaccines.index', compact('vaccines', 'search', 'availability'));
    }

    public function storeGlobal(Request $request)
    {
        return $this->store($request);
    }

    public function updateGlobal(Request $request, ClinicVaccine $vaccine)
    {
        return $this->update($request, null, $vaccine);
    }

    public function store(Request $request, ?Patient $patient = null)
    {
        $request->merge(['dose_count' => $request->input('dose_count', 1)]);
        $request->merge([
            'vaccine_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('vaccine_name'))),
        ]);
        $data = $request->validate([

            'vaccine_name' => ['required', 'string', 'max:150', Rule::unique('clinic_vaccines', 'name')],

            'available' => ['required', 'boolean'],
            'price' => ['nullable', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'dose_count' => ['required', 'integer', 'between:1,4'],
            'dose_interval_months' => ['nullable', 'integer', 'between:1,24'],

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
            'dose_count' => $data['dose_count'], 'dose_interval_months' => $data['dose_count'] > 1 ? $data['dose_interval_months'] : null,
        ]);
        $this->pricePending($vaccine);

        if (!$patient) return redirect()->route('vaccines.index')->with('success', 'Vaccine added to the clinic list.')->with('open_vaccine', $vaccine->id);

        return redirect()->route('patients.show', [
            $patient,
            'tab' => 'immunizations',
        ])->with('keep_vaccine_manager', true)->with('success', 'Vaccine added to the clinic list.');
    }

    public function update(Request $request, ?Patient $patient, ClinicVaccine $vaccine)
    {
        $data = $request->validate([
            'available' => ['sometimes', 'required', 'boolean'],
            'price' => ['sometimes', 'required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'dose_count' => ['sometimes', 'required', 'integer', 'between:1,4'],
            'dose_interval_months' => ['nullable', 'integer', 'between:1,24'],
        ]);
        if (array_key_exists('price', $data)) {
            $data['price_centavos'] = $this->price($data['price']);
            unset($data['price']);
        }
        if (array_key_exists('dose_count', $data) && $data['dose_count'] === 1) $data['dose_interval_months'] = null;
        $vaccine->update($data);
        $this->pricePending($vaccine);

        if (!$patient) return back()->with('success', 'Vaccine settings saved.')->with('open_vaccine', $vaccine->id);

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
