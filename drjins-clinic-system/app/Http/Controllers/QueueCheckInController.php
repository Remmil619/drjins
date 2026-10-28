<?php

namespace App\Http\Controllers;

use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Services\BookingSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QueueCheckInController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        if (! $request->expectsJson()) {
            app(BookingSync::class)->sync();
        }
        $date = $data['date'] ?? today()->toDateString();
        $entries = QueueEntry::with(['patient', 'appointment'])
            ->whereDate('visit_date', $date)->orderBy('id')->get()
            ->each(fn ($entry, $index) => $entry->setAttribute('daily_number', $index + 1))
            ->whereIn('status', ['waiting', 'in_consultation']);

        if ($request->expectsJson()) {
            return response()->json(['entries' => $entries->map(fn ($entry) => [
                'id' => $entry->id, 'status' => $entry->status,
            ])->values()]);
        }

        return view('queue.index', compact('entries', 'date'));
    }

    public function search(Request $request)
    {
        $data = $request->validate(['search' => 'nullable|string|max:100']);
        $search = trim($data['search'] ?? '');
        if ($search === '') {
            return response()->json(['results' => []]);
        }

        $queuedPatients = QueueEntry::whereDate('visit_date', today())->whereNotNull('patient_id')->pluck('patient_id');
        $bookings = OnlineAppointment::whereDate('appointment_date', today())
            ->where('status', 'Approved')->whereDoesntHave('queueEntry')
            ->where('child_name', 'like', "%$search%")
            ->where(fn ($q) => $q->whereNull('patient_id')->orWhereNotIn('patient_id', $queuedPatients))
            ->where(fn ($q) => $q->whereNull('patient_id')->orWhereHas('patient', fn ($p) => $p->where('status', 'Active')))
            ->orderBy('appointment_time')->limit(20)->get();
        $patients = Patient::where('status', 'Active')
            ->whereNotIn('id', $queuedPatients)->whereNotIn('id', $bookings->pluck('patient_id')->filter())
            ->where(function ($query) use ($search) {
                foreach (preg_split('/\s+/', $search) as $word) {
                    $query->where(fn ($q) => $q->where('first_name', 'like', "%$word%")
                        ->orWhere('middle_name', 'like', "%$word%")
                        ->orWhere('last_name', 'like', "%$word%"));
                }
            })->orderBy('last_name')->limit(20)->get();

        return response()->json(['results' => $bookings->map(fn ($b) => [
            'online_appointment_id' => $b->id, 'name' => $b->child_name,
            'detail' => 'Online appointment · '.$b->appointment_time.' · '.$b->guardian_name,
        ])->concat($patients->map(fn ($p) => [
            'patient_id' => $p->id, 'name' => $p->full_name,
            'detail' => 'Registered patient · '.$p->birthdate.' · '.$p->guardian_name,
        ]))->values()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'nullable|required_without:online_appointment_id|integer|exists:patients,id',
            'online_appointment_id' => 'nullable|integer|exists:online_appointments,id',
            'reason' => 'nullable|string|max:1000',
        ]);
        $booking = DB::transaction(function () use ($data) {
            $booking = ! empty($data['online_appointment_id'])
                ? OnlineAppointment::whereKey($data['online_appointment_id'])->lockForUpdate()->firstOrFail() : null;
            if ($booking && ($booking->status !== 'Approved' || $booking->appointment_date !== today()->toDateString() || $booking->queueEntry()->exists())) {
                throw ValidationException::withMessages(['online_appointment_id' => 'This appointment is no longer available for check-in.']);
            }
            if ($booking?->patient_id && ! empty($data['patient_id']) && $booking->patient_id != $data['patient_id']) {
                throw ValidationException::withMessages(['patient_id' => 'The booking belongs to another patient.']);
            }
            $patientId = $booking?->patient_id ?? ($data['patient_id'] ?? null);
            if ($patientId) {
                $patient = Patient::whereKey($patientId)->lockForUpdate()->firstOrFail();
                if ($patient->status !== 'Active' || QueueEntry::where('patient_id', $patientId)->whereDate('visit_date', today())->exists()) {
                    throw ValidationException::withMessages(['patient_id' => 'This patient is inactive or already checked in today.']);
                }
            }
            // Keep online visitors in the queue without registering them prematurely.
            QueueEntry::create([
                'patient_id' => $patientId, 'online_appointment_id' => $booking?->id,
                'visit_date' => today()->toDateString(), 'status' => 'waiting',
                'reason' => $data['reason'] ?? $booking?->reason_for_visit ?? 'Clinic visit',
            ]);
            if ($booking) {
                $booking->update([
                    'patient_id' => $patientId, 'status' => 'In Queue',
                    'staff_response' => 'The patient has arrived and is in the clinic queue.',
                    'responded_at' => now(), 'sync_pending' => true,
                ]);
            }

            return $booking;
        });

        return $this->respond($booking, 'Patient added to the queue.');
    }

    public function done(Request $request, QueueEntry $entry)
    {
        $input = $request->validate([
            'birthdate' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'sex' => 'nullable|in:Male,Female',
        ]);
        $booking = DB::transaction(function () use ($entry, $input) {
            $entry = QueueEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            if ($entry->visit_date !== today()->toDateString() || ! in_array($entry->status, ['waiting', 'in_consultation'], true)) {
                throw ValidationException::withMessages(['status' => 'This visit is already finished or is not for today.']);
            }
            $booking = $entry->online_appointment_id
                ? OnlineAppointment::whereKey($entry->online_appointment_id)->lockForUpdate()->firstOrFail() : null;
            if ($booking && ! in_array($booking->status, ['Approved', 'In Queue'], true)) {
                throw ValidationException::withMessages(['status' => 'Review the booking status before completing this visit.']);
            }
            app(\App\Services\QueuePatientRegistration::class)->attach($entry, $booking, $input);
            $entry->fill(['status' => 'completed', 'completed_at' => now()])->save();
            if ($booking) {
                $booking->update([
                    'patient_id' => $entry->patient_id, 'status' => 'Completed',
                    'staff_response' => 'The clinic marked this visit as completed.',
                    'responded_at' => now(), 'sync_pending' => true,
                ]);
            }

            return $booking;
        });

        return $this->respond($booking, 'Visit completed. The patient is available in Patient Records.');
    }

    private function respond(?OnlineAppointment $booking, string $message)
    {
        if ($booking && ! app(BookingSync::class)->push($booking)) {
            $message .= ' Portal update is queued for retry.';
        }

        return redirect()->route('queue.index')->with('success', $message);
    }
}
