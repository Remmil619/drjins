<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\GrowthRecord;
use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Services\BookingSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'patient' => 'nullable|integer|exists:patients,id',
        ]);
        $selectedPatient = !empty($filters['patient']) ? Patient::findOrFail($filters['patient']) : null;
        $search = $filters['search'] ?? '';
        $records = Consultation::with(['patient', 'author'])
            ->when($selectedPatient, fn ($query) => $query->where('patient_id', $selectedPatient->id))
            ->when($search, function ($query) use ($search) {
                $query->whereHas('patient', function ($patient) use ($search) {
                    $patient->where(function ($match) use ($search) {
                        $match->where('patient_no', 'like', '%'.$search.'%')
                            ->orWhere(function ($name) use ($search) {
                                foreach (preg_split('/\s+/', trim($search)) as $word) {
                                    $name->where(fn ($part) => $part->where('first_name', 'like', '%'.$word.'%')
                                        ->orWhere('middle_name', 'like', '%'.$word.'%')
                                        ->orWhere('last_name', 'like', '%'.$word.'%')
                                        ->orWhere('suffix', 'like', '%'.$word.'%'));
                                }
                            });
                    });
                });
            })
            ->orderByDesc('visit_date')->orderByDesc('id')->paginate(15)->appends(array_filter(['search' => $search, 'patient' => $selectedPatient?->id]));
        return view('consultations.index', compact('records', 'search', 'selectedPatient'));
    }

    public function print(Patient $patient, Consultation $consultation)
    {
        abort_unless($consultation->patient_id === $patient->id && ! $consultation->voided_at, 404);
        return response()->view('consultations.prescription', [
            'patient' => $patient, 'consultation' => $consultation, 'printConsultation' => true,
        ])->header('Cache-Control', 'private, no-store');
    }
    public function prescription(Patient $patient, Consultation $consultation)
    {
        abort_unless(
            $consultation->patient_id === $patient->id
                && ! $consultation->voided_at
                && filled($consultation->prescription),
            404,
        );

        return response()->view('consultations.prescription', compact('patient', 'consultation'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, Patient $patient)
    {
        $vitals = $request->validate([
            'height_cm' => 'nullable|required_with:weight_kg,temperature_c|numeric|gt:0|max:300',
            'weight_kg' => 'nullable|required_with:height_cm,temperature_c|numeric|gt:0|max:500',
            'temperature_c' => 'nullable|numeric|between:25,45',
        ]);
        $values = $request->validate([

            'visit_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$patient->birthdate],

            'complaint' => 'required|string|max:5000',

            'assessment' => 'required|string|max:5000',

            'plan' => 'nullable|string|max:5000',

            'prescription' => 'nullable|string|max:5000',

            'follow_up_date' => 'nullable|date_format:Y-m-d|after:visit_date',

            'request_key' => 'nullable|uuid',

        ]);
        DB::transaction(function () use ($patient, $values, $vitals, $request) {
            Patient::whereKey($patient->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! empty($values['request_key'])) {
                $existing = Consultation::where('request_key', $values['request_key'])->first();
                if ($existing) {
                    $same = $existing->patient_id === $patient->id && ! $existing->voided_at;
                    foreach (['visit_date', 'complaint', 'assessment', 'plan', 'prescription', 'follow_up_date'] as $field) {
                        $same = $same && $existing->$field === ($values[$field] ?? null);
                    }
                    if (filled($vitals['height_cm'] ?? null)) {
                        $measurement = GrowthRecord::where('patient_id', $patient->id)->where('measured_on', $values['visit_date'])
                            ->whereNull('voided_at')->latest('id')->first();
                        foreach (['height_cm', 'weight_kg', 'temperature_c'] as $field) {
                            $same = $same && $measurement && ($measurement->$field === null ? null : (float) $measurement->$field)
                                === (isset($vitals[$field]) ? (float) $vitals[$field] : null);
                        }
                    }
                    if (! $same) {
                        throw ValidationException::withMessages([
                            'request_key' => 'This form was already submitted with different details or was voided. Reload the patient record before adding a new consultation.',
                        ]);
                    }

                    return null;
                }
            }
            $queue = QueueEntry::where('patient_id', $patient->id)
                ->whereDate('visit_date', $values['visit_date'])
                ->whereIn('status', ['waiting', 'in_consultation'])
                ->lockForUpdate()
                ->first();

            if (filled($vitals['height_cm'] ?? null) && filled($vitals['weight_kg'] ?? null)) {
                $latest = GrowthRecord::where('patient_id', $patient->id)->where('measured_on', $values['visit_date'])
                    ->whereNull('voided_at')->latest('id')->first();
                $same = $latest && (float) $latest->height_cm === (float) $vitals['height_cm']
                    && (float) $latest->weight_kg === (float) $vitals['weight_kg']
                    && ($latest->temperature_c === null ? null : (float) $latest->temperature_c)
                        === (isset($vitals['temperature_c']) ? (float) $vitals['temperature_c'] : null);
                if (! $same) {
                    GrowthRecord::create($vitals + [
                        'patient_id' => $patient->id, 'user_id' => $request->user()->id,
                        'measured_on' => $values['visit_date'], 'notes' => 'Recorded with consultation.',
                    ]);
                }
            }
            $visit = $patient->consultations()->create($values + [
                'user_id' => $request->user()->id,
                'queue_entry_id' => $queue?->id,
            ]);
            app(\App\Services\ServiceBilling::class)->consultation($visit);
        });
        $message = 'Consultation saved.';

        return redirect()
            ->route('patients.show', [
                $patient,
                'tab' => 'consultations',
            ])
            ->with('success', $message);
    }
}
