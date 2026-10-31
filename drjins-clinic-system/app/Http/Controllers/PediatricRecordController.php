<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\GrowthRecord;
use App\Models\Immunization;
use App\Models\ClinicVaccine;
use Carbon\Carbon;
use App\Models\MedicalAlert;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PediatricRecordController extends Controller
{
    public function updateGrowth(Request $request, Patient $patient, GrowthRecord $record)
    {
        abort_unless($record->patient_id === $patient->id && ! $record->voided_at, 404);
        $data = $request->validate([
            'measured_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$patient->birthdate],
            'weight_kg' => 'required|numeric|gt:0|max:500',
            'height_cm' => 'required|numeric|gt:0|max:300',
            'head_cm' => 'nullable|numeric|gt:0|max:100',
            'temperature_c' => 'nullable|numeric|between:25,45',
            'notes' => 'nullable|string|max:2000',
        ]);
        $schedule = ClinicVaccine::whereRaw('LOWER(name) = ?', [mb_strtolower($data['vaccine'])])->first();
        $doseNumber = (int) preg_replace('/\D+/', '', $data['dose']);
        if ($schedule && $schedule->dose_count <= 1) {
            $data['next_due_on'] = null;
        } elseif ($schedule && $schedule->dose_interval_months && $doseNumber > 0 && $doseNumber < $schedule->dose_count) {
            $data['next_due_on'] = Carbon::parse($data['administered_on'])->addMonths($schedule->dose_interval_months)->toDateString();
        }
        DB::transaction(function () use ($record, $patient, $data) {
            $record = GrowthRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_if($record->voided_at, 409);
            $record->update($data);
            $latest = GrowthRecord::where('patient_id', $patient->id)->whereNull('voided_at')
                ->orderByDesc('measured_on')->orderByDesc('id')->first();
            $patient->update(['height' => $latest->height_cm, 'weight' => $latest->weight_kg, 'temperature' => $latest->temperature_c]);
        });
        return back()->with('success', 'Measurements updated.');
    }

    public function growth(Request $request, Patient $patient)
    {
        $data = $request->validate([

            'measured_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$patient->birthdate],

            'weight_kg' => 'required|numeric|gt:0|max:500',

            'height_cm' => 'required|numeric|gt:0|max:300',

            'head_cm' => 'nullable|numeric|gt:0|max:100',

            'temperature_c' => 'nullable|numeric|between:25,45',

            'notes' => 'nullable|string|max:2000',

        ]);
        DB::transaction(fn () => GrowthRecord::create($data + [
            'patient_id' => $patient->id,
            'user_id' => $request->user()->id,
        ]));

        return back()->with('success', 'Growth measurements saved.');
    }

    public function immunization(Request $request, Patient $patient, ?Immunization $record = null)
    {
        if ($record) {
            abort_unless($record->patient_id === $patient->id && ! $record->voided_at, 404);
        }
        $data = $request->validate([

            'vaccine' => 'required|string|max:150',
            'dose' => 'required|string|max:50',

            'administered_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$patient->birthdate],

            'source' => 'required|in:clinic,external_record,guardian_report',

            'provider' => 'required|string|max:150',
            'lot_number' => 'nullable|string|max:100',

            'expiry_date' => 'nullable|date_format:Y-m-d|after_or_equal:administered_on',

            'next_due_on' => 'nullable|date_format:Y-m-d|after:administered_on',
            'notes' => 'nullable|string|max:2000',

        ]);
        DB::transaction(function () use ($data, $patient, $request, $record) {
            Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            if ($record) {
                $record = Immunization::whereKey($record->id)->lockForUpdate()->firstOrFail();
                abort_if($record->voided_at, 409);
            }
            if (Immunization::where('patient_id', $patient->id)->whereNull('voided_at')->when($record, fn ($query) => $query->where('id', '!=', $record->id))->where('vaccine', $data['vaccine'])->where('dose', $data['dose'])->whereDate('administered_on', $data['administered_on'])->exists()) {
                throw ValidationException::withMessages([
                    'vaccine' => 'This vaccine dose and date are already recorded.',
                ]);
            }
            if ($record) {
                if ($record->next_due_on !== ($data['next_due_on'] ?? null)) {
                    $data += ['reminder_closed_at' => null, 'reminder_outcome' => null];
                }
                $record->update($data);
                app(\App\Services\ServiceBilling::class)->vaccination($record);
                return;
            }
            $vaccination = Immunization::create($data + [
                'patient_id' => $patient->id,
                'user_id' => $request->user()->id,
            ]);
            app(\App\Services\ServiceBilling::class)->vaccination($vaccination);
        });

        return back()->with('success', $record ? 'Vaccination record updated.' : 'Vaccination record saved.');
    }

    public function alert(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'category' => 'required|in:Allergy,Medical condition,Other',
            'description' => 'required|string|max:500',
            'reaction' => 'nullable|string|max:500',
        ]);
        DB::transaction(fn () => MedicalAlert::create($data + [
            'patient_id' => $patient->id,
            'user_id' => $request->user()->id,
        ]));

        return back()->with('success', 'Patient alert recorded.');
    }

    public function void(Request $request, Patient $patient, string $type, int $record)
    {
        $data = $request->validate([
            'void_reason' => 'required|string|min:5|max:500',
        ]);
        $class = [
            'growth' => GrowthRecord::class,
            'immunization' => Immunization::class,
            'alert' => MedicalAlert::class,
            'consultation' => Consultation::class,
        ][$type] ?? null;
        abort_unless($class, 404);
        DB::transaction(function () use ($class, $patient, $record, $data) {
            Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            $entry = $class::where('patient_id', $patient->id)->whereKey($record)->lockForUpdate()->firstOrFail();
            if ($entry->voided_at) {
                throw ValidationException::withMessages([
                    'void_reason' => 'This record has already been voided.',
                ]);
            }
            $entry->update($data + [
                'voided_at' => now(),
            ]);
            if ($entry instanceof Consultation) app(\App\Services\ServiceBilling::class)->consultation($entry);
            if ($entry instanceof Immunization) app(\App\Services\ServiceBilling::class)->vaccination($entry);
        });

        return back()->with('success', 'Record voided. The original remains in the history; add a corrected entry if needed.');
    }

    public function closeReminder(Request $request, string $type, int $record)
    {
        $data = $request->validate([
            'outcome' => 'required|string|min:5|max:255',
        ]);
        $class = [
            'consultation' => Consultation::class,
            'immunization' => Immunization::class,
        ][$type] ?? null;
        abort_unless($class, 404);
        DB::transaction(function () use ($class, $record, $data, $type) {
            $entry = $class::whereKey($record)->whereNull('voided_at')->lockForUpdate()->firstOrFail();
            $prefix = $type === 'consultation' ? 'follow_up' : 'reminder';
            $dateField = $type === 'consultation' ? 'follow_up_date' : 'next_due_on';
            if (! $entry->$dateField || $entry->{$prefix.'_closed_at'}) {
                throw ValidationException::withMessages([
                    'outcome' => 'This reminder is no longer open.',
                ]);
            }
            $entry->update([
                $prefix.'_closed_at' => now(),
                $prefix.'_outcome' => $data['outcome'],
            ]);
        });

        return back()->with('success', 'Reminder closed with the recorded outcome.');
    }
}
