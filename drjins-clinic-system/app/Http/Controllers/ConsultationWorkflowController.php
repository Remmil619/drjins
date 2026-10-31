<?php

namespace App\Http\Controllers;

use App\Models\{Charge, ClinicVaccine, Consultation, Patient, QueueEntry, OnlineAppointment, User};
use App\Services\QueuePatientRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationWorkflowController extends Controller
{
    public function booking(QueueEntry $entry)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'doctor'], true), 403);
        abort_unless($entry->online_appointment_id && in_array($entry->status, ['waiting', 'in_consultation'], true), 404);
        if ($entry->patient_id) return redirect()->route('patients.show', [$entry->patient_id, 'tab' => 'consultations']);
        $booking = $entry->appointment;
        $patient = new Patient(['first_name' => $booking->child_name, 'birthdate' => $booking->child_date_of_birth, 'sex' => $booking->child_sex]);
        return view('consultations.booking', [
            'entry' => $entry, 'booking' => $booking, 'patient' => $patient,
            'latestVitals' => null, 'clinicVaccines' => ClinicVaccine::orderBy('name')->get(),
            'doctors' => $this->doctors(),
        ]);
    }

    public function savePatient(Request $request, Patient $patient)
    {
        return $this->save($request, $patient);
    }

    public function saveBooking(Request $request, QueueEntry $entry)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'doctor'], true), 403);
        return $this->save($request, null, $entry);
    }

    private function doctors()
    {
        return User::where('role', 'doctor')->orderBy('name')->get();
    }

    private function save(Request $request, ?Patient $patient, ?QueueEntry $entry = null)
    {
        $data = $request->validate([
            'request_key' => 'required|uuid',
            'include_vaccination' => 'nullable|boolean',
            'consultation_fee' => 'required|decimal:0,2|min:0|max:1000000',
            'vaccination_fee' => 'nullable|required_if:include_vaccination,1|decimal:0,2|min:0|max:1000000',
        ]);
        $hash = hash('sha256', json_encode($request->only([
            'visit_date', 'complaint', 'assessment', 'prescription', 'follow_up_date',
            'height_cm', 'weight_kg', 'temperature_c', 'include_vaccination', 'vaccination',
            'consultation_fee', 'vaccination_fee', 'birthdate', 'sex',
        ])));
        $patient = DB::transaction(function () use ($request, $patient, $entry, $data, $hash) {
            if ($entry) {
                $entry = QueueEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
                if ($entry->visit_date !== today()->toDateString() || !in_array($entry->status, ['waiting', 'in_consultation'], true)) {
                    throw ValidationException::withMessages(['visit_date' => 'This queue entry is no longer available for consultation.']);
                }
                if ($request->input('visit_date') !== $entry->visit_date) {
                    throw ValidationException::withMessages(['visit_date' => 'Use the queue visit date for this consultation.']);
                }
                $booking = OnlineAppointment::whereKey($entry->online_appointment_id)->lockForUpdate()->firstOrFail();
                if (!in_array($booking->status, ['Approved', 'In Queue'], true)) {
                    throw ValidationException::withMessages(['visit_date' => 'This booking is no longer available for consultation.']);
                }
                app(QueuePatientRegistration::class)->attach($entry, $booking, $request->only('birthdate', 'sex'));
                $entry->save();
                $booking->update(['patient_id' => $entry->patient_id]);
                $patient = Patient::findOrFail($entry->patient_id);
            }
            $patient = Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            $existing = Consultation::where('request_key', $data['request_key'])->first();
            if ($existing) {
                if ($existing->patient_id !== $patient->id || $existing->voided_at || $existing->workflow_hash !== $hash) {
                    throw ValidationException::withMessages(['request_key' => 'This submission was already saved with different details. Reopen Add consultation to record a new visit.']);
                }
                return $patient;
            }
            app(ConsultationController::class)->store($request, $patient);
            $visit = Consultation::where('request_key', $data['request_key'])->firstOrFail();
            $visit->forceFill(['workflow_hash' => $hash])->save();
            $this->price('consultation:'.$patient->id.':'.$visit->visit_date, $data['consultation_fee']);
            if ($request->boolean('include_vaccination')) {
                $vaccineRequest = Request::create($request->url(), 'POST', (array) $request->input('vaccination', []));
                $vaccineRequest->setUserResolver(fn () => $request->user());
                try {
                    app(PediatricRecordController::class)->immunization($vaccineRequest, $patient);
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn ($errors, $key) => ['vaccination.'.$key => $errors])->all());
                }
                $vaccine = \App\Models\Immunization::where('patient_id', $patient->id)->latest('id')->firstOrFail();
                if ($vaccine->source === 'clinic') $this->price('vaccination:'.$vaccine->id, $data['vaccination_fee']);
            }
            return $patient;
        });
        return redirect()->route('patients.show', $patient)->with('success', 'Consultation, vaccination details and fees saved. The patient is available in Patients records.');
    }

    private function price(string $key, string $amount): void
    {
        $charge = Charge::where('service_key', $key)->lockForUpdate()->firstOrFail();
        $parts = explode('.', $amount);
        $centavos = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($charge->paid_centavos > 0 && $charge->amount_centavos !== $centavos) {
            throw ValidationException::withMessages(['consultation_fee' => 'This service already has payments. Keep its existing fee.']);
        }
        $charge->update(['amount_centavos' => $centavos, 'price_pending' => false]);
    }
}
