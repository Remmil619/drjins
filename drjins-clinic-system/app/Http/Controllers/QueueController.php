<?php

namespace App\Http\Controllers;

use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\QueueEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'search' => 'nullable|string|max:100',
        ]);
        $date = $filters['date'] ?? today()->toDateString();
        $search = $filters['search'] ?? '';
        $patients = Patient::where('status', 'Active')->when($search, fn ($q) => $q->where(function ($q) use ($search) {
            $q->where('patient_no', 'like', "%$search%")->orWhere('first_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%")->orWhere('contact_number', 'like', "%$search%");
        }))->orderBy('last_name')->limit(50)->get();
        $entries = QueueEntry::with(['patient', 'appointment'])->whereDate('visit_date', $date)->orderBy('id')->get();
        $appointments = OnlineAppointment::whereDate('appointment_date', today())->where('status', 'Approved')->whereDoesntHave('queueEntry')->orderBy('appointment_time')->get();

        return view('queue.index', compact('date', 'search', 'patients', 'entries', 'appointments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'online_appointment_id' => 'nullable|integer|exists:online_appointments,id',
            'reason' => 'required|string|max:1000',
        ]);
        DB::transaction(function () use ($data) {
            $patient = Patient::whereKey($data['patient_id'])->lockForUpdate()->firstOrFail();
            if ($patient->status !== 'Active') {
                throw ValidationException::withMessages([
                    'patient_id' => 'Activate this patient before check-in.',
                ]);
            }
            if (QueueEntry::where('patient_id', $patient->id)->whereDate('visit_date', today())->exists()) {
                throw ValidationException::withMessages([
                    'patient_id' => 'This patient already has a queue entry today. Open the existing entry.',
                ]);
            }
            if (! empty($data['online_appointment_id'])) {
                $appointment = OnlineAppointment::whereKey($data['online_appointment_id'])->lockForUpdate()->firstOrFail();
                if ($appointment->appointment_date !== today()->toDateString() || $appointment->status !== 'Approved' || $appointment->queueEntry()->exists()) {
                    throw ValidationException::withMessages([
                        'online_appointment_id' => 'Select an approved, unchecked appointment for today.',
                    ]);
                }
                if ($appointment->patient_id && $appointment->patient_id !== $patient->id) {
                    throw ValidationException::withMessages([
                        'patient_id' => 'This booking is already linked to another patient.',
                    ]);
                }
                $appointment->update([
                    'patient_id' => $patient->id,
                ]);
            }
            QueueEntry::create($data + [
                'visit_date' => today()->toDateString(),
                'status' => 'waiting',
            ]);
        });

        return redirect()->route('queue.index')->with('success', 'Patient checked in.');
    }

    public function update(Request $request, QueueEntry $entry)
    {
        $data = $request->validate([
            'status' => 'required|in:waiting,in_consultation,left',
        ]);
        $input = $request->validate([
            'birthdate' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'sex' => 'nullable|in:Male,Female',
        ]);
        if ($data['status'] === 'in_consultation') {
            abort_unless(in_array($request->user()->role, ['doctor', 'admin'], true), 403);
        }
        $patientId = DB::transaction(function () use ($entry, $data, $input) {
            $entry = QueueEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $allowed = [
                'waiting' => ['in_consultation', 'left'],
                'in_consultation' => ['waiting', 'left', 'in_consultation'],
                'left' => ['waiting'],
                'completed' => [],
            ];
            if ($entry->visit_date !== today()->toDateString() || ! in_array($data['status'], $allowed[$entry->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'This queue transition is not available. Refresh the queue.',
                ]);
            }
            if ($data['status'] !== 'left') {
                if ($entry->patient && $entry->patient->status !== 'Active') {
                    throw ValidationException::withMessages([
                        'status' => 'Activate this patient before continuing the visit.',
                    ]);
                }
                $appointment = $entry->online_appointment_id
                    ? OnlineAppointment::whereKey($entry->online_appointment_id)->lockForUpdate()->firstOrFail() : null;
                if ($appointment && (! in_array($appointment->status, ['Approved', 'In Queue'], true) || $appointment->appointment_date !== $entry->visit_date)) {
                    throw ValidationException::withMessages([
                        'status' => 'The linked booking is no longer approved for this visit date. Review the appointment first.',
                    ]);
                }
                if ($data['status'] === 'in_consultation') {
                    app(\App\Services\QueuePatientRegistration::class)->attach($entry, $appointment, $input);
                    if (! $entry->patient_id || $entry->patient()->value('status') !== 'Active') {
                        throw ValidationException::withMessages(['status' => 'An active patient record is required to start the check-up.']);
                    }
                    if ($appointment) {
                        $appointment->update(['patient_id' => $entry->patient_id]);
                    }
                }
            }
            $entry->update($data + [
                'started_at' => $data['status'] === 'in_consultation' ? ($entry->started_at ?? now()) : $entry->started_at,
            ]);
            return $entry->patient_id;
        });

        if ($data['status'] === 'in_consultation') {
            return redirect()->route('patients.show', ['patient' => $patientId, 'tab' => 'consultations'])
                ->with('success', 'Check-up started. Staff can now see In check up in the queue.');
        }

        return back()->with('success', 'Queue updated. The staff can now see the current check-up status.');
    }
}
