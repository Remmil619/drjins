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
        DB::transaction(function () use ($entry, $data) {
            $entry = QueueEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $allowed = [
                'waiting' => ['in_consultation', 'left'],
                'in_consultation' => ['waiting', 'left'],
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
                $appointment = $entry->appointment;
                if ($appointment && (! in_array($appointment->status, ['Approved', 'In Queue'], true) || $appointment->appointment_date !== $entry->visit_date)) {
                    throw ValidationException::withMessages([
                        'status' => 'The linked booking is no longer approved for this visit date. Review the appointment first.',
                    ]);
                }
            }
            $entry->update($data + [
                'started_at' => $data['status'] === 'in_consultation' ? now() : $entry->started_at,
            ]);
        });

        return back()->with('success', 'Queue updated. Save the consultation on the patient profile to complete the visit.');
    }
}
