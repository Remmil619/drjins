<?php

namespace App\Http\Controllers;

use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Services\BookingSync;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OnlineBookingController extends Controller
{
    public function index(Request $request)
    {
        $selectedFilter = $request->input('filter', 'today');
        if (!in_array($selectedFilter, ['all', 'today', 'upcoming'], true)) $selectedFilter = 'today';

        $this->syncBookingsFromPortal();

        return view('online-bookings.index', compact('selectedFilter'));
    }

    public function live(Request $request)
    {
        $selectedFilter = $request->input('filter', 'today');
        $allowedFilters = ['all', 'today', 'upcoming'];

        if (! in_array($selectedFilter, $allowedFilters, true)) {
            $selectedFilter = 'today';
        }

        $connected = $this->syncBookingsFromPortal();

        $appointmentsQuery = OnlineAppointment::with('queueEntry');

        if ($selectedFilter === 'today') {
            $appointmentsQuery->whereDate('appointment_date', now()->toDateString());
        } elseif ($selectedFilter === 'upcoming') {
            $appointmentsQuery->whereDate('appointment_date', '>=', now()->toDateString());
        }

        $appointments = $appointmentsQuery
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->map(function ($appointment) {
                return [
                    'appointment_no' => $appointment->appointment_no,
                    'guardian_name' => $appointment->guardian_name,
                    'child_name' => $appointment->child_name,
                    'contact_number' => $appointment->contact_number,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'reason_for_visit' => $appointment->reason_for_visit,
                    'status' => $appointment->status,
                    'patient_url' => $appointment->patient_id ? route('patients.show', $appointment->patient_id) : null,
                    'status_locked' => $appointment->status === 'Completed' || ($appointment->queueEntry !== null && in_array($appointment->queueEntry->status, ['waiting', 'in_consultation', 'completed'], true)),
                    'guardian_relationship' => $appointment->guardian_relationship,
                    'guardian_email' => $appointment->guardian_email,
                    'guardian_address' => $appointment->guardian_address,
                    'child_date_of_birth' => $appointment->child_date_of_birth,
                    'child_sex' => $appointment->child_sex,
                    'emergency_contact_name' => $appointment->emergency_contact_name,
                    'emergency_contact_number' => $appointment->emergency_contact_number,
                    'allergies' => $appointment->allergies,
                    'current_medications' => $appointment->current_medications,
                    'medical_notes' => $appointment->medical_notes,
                ];
            });

        return response()->json([
            'connected' => $connected,
            'appointments' => $appointments,
            'total' => $appointments->count(),
            'filter' => $selectedFilter,
            'filter_label' => match ($selectedFilter) {
                'all' => 'All Appointments',
                'today' => "Today's Appointments",
                default => 'Upcoming Appointments',
            },
            'last_checked' => now()->format('h:i:s A'),
        ]);
    }

    public function savePatientDetails(Request $request)
    {
        $validated=$request->validate([
            'appointment_no'=>'required|string|max:255',
            'first_name'=>'required|string|max:255','middle_name'=>'nullable|string|max:255',
            'last_name'=>'required|string|max:255','suffix'=>'nullable|string|max:20',
            'guardian_name'=>'required|string|max:255','contact_number'=>'required|string|max:20',
            'birthdate'=>'required|date_format:Y-m-d|before_or_equal:today','sex'=>'required|in:Male,Female',
            'height'=>'nullable|string|max:50','weight'=>'nullable|string|max:50',
            'barangay' => 'nullable|required_with:municipality,province|string|max:150',
            'municipality' => 'nullable|required_with:barangay,province|string|max:150',
            'province' => 'nullable|required_with:barangay,municipality|string|max:150',
            'temperature'=>'nullable|numeric|between:25,45','address'=>'nullable|string|max:500',
            'emergency_contact'=>'nullable|string|max:20','appointment_status'=>'nullable|in:register,done,follow_up',
        ]);
        [$patient,$appointment,$action]=\Illuminate\Support\Facades\DB::transaction(function() use($validated) {
            $appointment=OnlineAppointment::where('appointment_no',$validated['appointment_no'])->lockForUpdate()->first();
            if (!$appointment) throw \Illuminate\Validation\ValidationException::withMessages(['appointment_no'=>'Appointment reference number not found.']);
            $patient=$appointment->patient_id ? Patient::findOrFail($appointment->patient_id) : Patient::where('online_appointment_no',$appointment->appointment_no)->first();
            $attributes=collect($validated)->except(['appointment_no','appointment_status'])->all();
            if ($patient) $patient->update($attributes);
            else $patient=Patient::create($attributes+['online_appointment_no'=>$appointment->appointment_no,'patient_no'=>$this->generatePatientNo(),'status'=>'Active']);
            $appointment->update(['patient_id'=>$patient->id]);
            $action=$validated['appointment_status'] ?? 'register';
            if ($action !== 'register') {
                $appointment->assertStatusChangeAllowed($action === 'done' ? 'Completed' : 'Rescheduled', 'appointment_status');
                $appointment->update([
                    'status'=>$action==='done'?'Completed':'Rescheduled',
                    'staff_response'=>$action==='done'?'The clinic marked this visit as completed.':'Please contact the clinic to arrange a follow-up visit.',
                    'responded_at'=>now(),'sync_pending'=>true,
                ]);
            }
            return [$patient,$appointment,$action];
        });
        $message='Patient details saved. The patient profile is ready for the clinic visit.';
        if ($action !== 'register' && !app(BookingSync::class)->push($appointment)) $message.=' Portal status is queued for retry.';
        return redirect()->route('patients.show',$patient)->with('success',$message);
    }
    public function updateByReference(Request $request)
    {
        $validated = $request->validate([
            'appointment_no' => ['required', 'string', 'max:255'],
            'action' => ['required', 'in:done,follow_up'], 'birthdate' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'sex' => ['required', 'in:Male,Female'],
        ]);

        $this->syncBookingsFromPortal();

        $appointment = OnlineAppointment::where('appointment_no', $validated['appointment_no'])->first();

        if (! $appointment) {
            return back()
                ->withInput()
                ->withErrors([
                    'appointment_no' => 'Reference number not found. Please check the appointment reference number.',
                ]);
        }

        if ($validated['action'] === 'done') {
            $status = 'Completed';
            $staffResponse = 'Patient consultation has been completed.';
            $successMessage = 'Appointment marked as Done. The patient was added to the Patients page.';
        } else {
            $status = 'Rescheduled';
            $staffResponse = 'Follow-up consultation is suggested.';
            $successMessage = 'Appointment marked as For Follow-up. The patient was added to the Patients page.';
        }

        [$patient, $appointment] = DB::transaction(function () use ($appointment, $validated, $status, $staffResponse) {
            $appointment = OnlineAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $appointment->assertStatusChangeAllowed($status, 'appointment_no');
            $patient = $this->createPatientFromAppointment($appointment, $validated);
            $appointment->update([
                'status' => $status, 'patient_id' => $patient->id,
                'staff_response' => $staffResponse,
                'responded_at' => now(), 'sync_pending' => true,
            ]);
            return [$patient, $appointment];
        });

        $portalUpdated = $this->updatePortalStatus(
            $appointment->appointment_no,
            $status,
            $staffResponse
        );

        if (! $portalUpdated) {
            $successMessage .= ' Local system was updated, but the online booking portal was not updated because it is offline.';
        }

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', $successMessage);
    }

    public function sync()
    {
        $connected = $this->syncBookingsFromPortal();

        if ($connected) {
            return redirect()
                ->route('appointments.index')
                ->with('success', 'Appointments synced successfully.');
        }

        return redirect()
            ->route('appointments.index')
            ->withErrors([
                'sync' => 'Unable to connect to the online booking portal.',
            ]);
    }

    public function redirectToAppointments()
    {
        return redirect()->route('appointments.index');
    }

    private function createPatientFromAppointment(OnlineAppointment $appointment, array $details): Patient
    {
        if ($appointment->patient_id) return Patient::findOrFail($appointment->patient_id);
        $nameParts = $this->splitPatientName($appointment->child_name);

        return Patient::firstOrCreate(
            [
                'online_appointment_no' => $appointment->appointment_no,
            ],
            [
                'patient_no' => $this->generatePatientNo(),
                'first_name' => $nameParts['first_name'],
                'middle_name' => $nameParts['middle_name'],
                'last_name' => $nameParts['last_name'],
                'birthdate' => $details['birthdate'],
                'sex' => $details['sex'],
                'height' => null,
                'weight' => null,
                'address' => $appointment->guardian_address,
                'guardian_name' => $appointment->guardian_name,
                'contact_number' => $appointment->contact_number,
                'emergency_contact' => $appointment->emergency_contact_number,
                'status' => 'Active',
            ]
        );
    }

    private function splitPatientName(string $fullName): array
    {
        $fullName = trim($fullName);
        $nameParts = preg_split('/\s+/', $fullName);

        $firstName = array_shift($nameParts) ?: $fullName;
        $lastName = count($nameParts) > 0 ? array_pop($nameParts) : 'For Update';
        $middleName = count($nameParts) > 0 ? implode(' ', $nameParts) : null;

        return [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
        ];
    }

    private function generatePatientNo()
    {
        do {
            $patientNo = 'PAT-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (Patient::where('patient_no', $patientNo)->exists());

        return $patientNo;
    }

    private function syncBookingsFromPortal()
    {
        return app(BookingSync::class)->sync();
    }

    private function updatePortalStatus($appointmentNo, $status, $staffResponse)
    {
        return app(BookingSync::class)->push(OnlineAppointment::where('appointment_no', $appointmentNo)->firstOrFail());
    }

    public function done(Request $request)
    {
        $validated = $request->validate(['appointment_no' => 'required|string|max:255']);
        [$patient, $appointment] = DB::transaction(function () use ($validated) {
            $appointment = OnlineAppointment::where('appointment_no', $validated['appointment_no'])->lockForUpdate()->firstOrFail();
            $appointment->assertStatusChangeAllowed('Completed');
            $patient = $appointment->patient_id
                ? Patient::findOrFail($appointment->patient_id)
                : Patient::where('online_appointment_no', $appointment->appointment_no)->first();
            if (!$patient) {
                $details = \Illuminate\Support\Facades\Validator::make([
                    'birthdate' => $appointment->child_date_of_birth,
                    'sex' => $appointment->child_sex,
                ], [
                    'birthdate' => 'required|date_format:Y-m-d|before_or_equal:today',
                    'sex' => 'required|in:Male,Female',
                ])->validate();
                $patient = $this->createPatientFromAppointment($appointment, $details);
            }
            $appointment->update([
                'patient_id' => $patient->id, 'status' => 'Completed',
                'staff_response' => 'The clinic marked this visit as completed.',
                'responded_at' => $appointment->responded_at ?? now(), 'sync_pending' => true,
            ]);
            return [$patient, $appointment];
        });
        $portalUpdated = app(BookingSync::class)->push($appointment);
        return response()->json([
            'success' => true, 'patient_url' => route('patients.show', $patient),
            'portal_updated' => $portalUpdated,
            'message' => $portalUpdated ? 'Done. Patient added to Patient Records.' : 'Done. Patient added to Patient Records. Portal update is queued for retry.',
        ]);
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'appointment_no' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Pending,Approved,Declined,Cancelled,Rescheduled'],
        ]);

        $appointment = OnlineAppointment::where('appointment_no', $validated['appointment_no'])->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found.',
            ], 404);
        }

        $staffResponse = match ($validated['status']) {
            'Cancelled' => 'Appointment cancelled by clinic staff.',
            'Rescheduled' => 'Please contact the clinic to arrange a follow-up schedule.',
            'Approved' => 'Your appointment has been approved.',
            'Declined' => 'Please contact the clinic for an alternative appointment.',
            default => 'Your appointment is awaiting review.',
        };
        DB::transaction(function () use ($appointment, $validated, $staffResponse) {
            $appointment = OnlineAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $appointment->assertStatusChangeAllowed($validated['status']);
            $appointment->update([
                'status' => $validated['status'],
                'staff_response' => $staffResponse,
                'responded_at' => now(), 'sync_pending' => true,
            ]);
        });

        $portalUpdated = $this->updatePortalStatus(
            $appointment->appointment_no,
            $validated['status'],
            $staffResponse
        );

        return response()->json([
            'success' => true,
            'message' => $portalUpdated ? 'Appointment status updated successfully.' : 'Saved locally. Portal update is pending and will retry.', 'portal_updated' => $portalUpdated,
        ]);
    }
}

