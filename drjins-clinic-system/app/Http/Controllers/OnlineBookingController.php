<?php

namespace App\Http\Controllers;

use App\Models\OnlineAppointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OnlineBookingController extends Controller
{
    public function index(Request $request)
    {
        $selectedFilter = $request->input('filter', 'upcoming');

        $this->syncBookingsFromPortal();

        return view('online-bookings.index', compact('selectedFilter'));
    }

    public function live(Request $request)
    {
        $selectedFilter = $request->input('filter', 'upcoming');
        $allowedFilters = ['all', 'today', 'upcoming', 'past'];

        if (! in_array($selectedFilter, $allowedFilters, true)) {
            $selectedFilter = 'upcoming';
        }

        $connected = $this->syncBookingsFromPortal();

        $appointmentsQuery = OnlineAppointment::query();

        if ($selectedFilter === 'today') {
            $appointmentsQuery->whereDate('appointment_date', now()->toDateString());
        } elseif ($selectedFilter === 'upcoming') {
            $appointmentsQuery->whereDate('appointment_date', '>=', now()->toDateString());
        } elseif ($selectedFilter === 'past') {
            $appointmentsQuery->whereDate('appointment_date', '<', now()->toDateString());
        }

        $appointments = $appointmentsQuery
            ->orderBy('appointment_date', $selectedFilter === 'past' ? 'desc' : 'asc')
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
                'past' => 'Past Appointments',
                default => 'Upcoming Appointments',
            },
            'last_checked' => now()->format('h:i:s A'),
        ]);
    }

    public function savePatientDetails(Request $request)
    {
        $validated = $request->validate([
            'appointment_no' => ['required', 'string', 'max:255'],
            'child_name' => ['required', 'string', 'max:255'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20'],
            'birthdate' => ['required', 'date', 'before_or_equal:today'],
            'sex' => ['required', 'in:Male,Female'],
            'height' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'appointment_status' => ['nullable', 'in:done,follow_up'],
        ]);

        $appointment = OnlineAppointment::where('appointment_no', $validated['appointment_no'])->first();

        if (!$appointment) {
            return back()
                ->withInput()
                ->withErrors([
                    'appointment_no' => 'Appointment reference number not found.',
                ]);
        }

        $nameParts = $this->splitPatientName($validated['child_name']);

        $existingPatientNo = Patient::where('online_appointment_no', $appointment->appointment_no)
            ->value('patient_no');

        $patient = Patient::updateOrCreate(
            [
                'online_appointment_no' => $appointment->appointment_no,
            ],
            [
                'patient_no' => $existingPatientNo ?? $this->generatePatientNo(),
                'first_name' => $nameParts['first_name'],
                'middle_name' => $nameParts['middle_name'],
                'last_name' => $nameParts['last_name'],
                'birthdate' => $validated['birthdate'],
                'sex' => $validated['sex'],
                'height' => $validated['height'] ?? null,
                'weight' => $validated['weight'] ?? null,
                'address' => $validated['address'] ?? null,
                'guardian_name' => $validated['guardian_name'],
                'contact_number' => $validated['contact_number'],
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'status' => 'Active',
            ]
        );

        $appointmentAction = $validated['appointment_status'] ?? 'done';

        if ($appointmentAction === 'follow_up') {
            $status = 'Rescheduled';
            $staffResponse = 'Follow-up consultation is suggested.';
            $successMessage = 'Patient details saved successfully. Appointment marked as For Follow-up.';
        } else {
            $status = 'Completed';
            $staffResponse = 'Patient details have been saved and consultation was marked as done.';
            $successMessage = 'Patient details saved successfully. Appointment marked as Done.';
        }

        $appointment->update([
            'child_name' => $validated['child_name'],
            'guardian_name' => $validated['guardian_name'],
            'contact_number' => $validated['contact_number'],
            'status' => $status,
            'staff_response' => $staffResponse,
            'responded_at' => now(),
        ]);

        $portalUpdated = $this->updatePortalStatus(
            $appointment->appointment_no,
            $status,
            $staffResponse
        );

        if (!$portalUpdated) {
            $successMessage .= ' Local system was updated, but the online booking portal was not updated because it is offline.';
        }

        return redirect()
            ->route('appointments.index')
            ->with('success', $successMessage);
    }

    public function updateByReference(Request $request)
    {
        $validated = $request->validate([
            'appointment_no' => ['required', 'string', 'max:255'],
            'action' => ['required', 'in:done,follow_up'],
        ]);

        $this->syncBookingsFromPortal();

        $appointment = OnlineAppointment::where('appointment_no', $validated['appointment_no'])->first();

        if (!$appointment) {
            return back()
                ->withInput()
                ->withErrors([
                    'appointment_no' => 'Reference number not found. Please check the appointment reference number.',
                ]);
        }

        $patient = $this->createPatientFromAppointment($appointment);

        if ($validated['action'] === 'done') {
            $status = 'Completed';
            $staffResponse = 'Patient consultation has been completed.';
            $successMessage = 'Appointment marked as Done. The patient was added to the Patients page.';
        } else {
            $status = 'Rescheduled';
            $staffResponse = 'Follow-up consultation is suggested.';
            $successMessage = 'Appointment marked as For Follow-up. The patient was added to the Patients page.';
        }

        $appointment->update([
            'status' => $status,
            'staff_response' => $staffResponse,
            'responded_at' => now(),
        ]);

        $portalUpdated = $this->updatePortalStatus(
            $appointment->appointment_no,
            $status,
            $staffResponse
        );

        if (!$portalUpdated) {
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

    private function createPatientFromAppointment(OnlineAppointment $appointment): Patient
    {
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
                'birthdate' => now()->toDateString(),
                'sex' => 'Male',
                'height' => null,
                'weight' => null,
                'address' => null,
                'guardian_name' => $appointment->guardian_name,
                'contact_number' => $appointment->contact_number,
                'emergency_contact' => null,
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
            $patientNo = 'PAT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Patient::where('patient_no', $patientNo)->exists());

        return $patientNo;
    }

    private function syncBookingsFromPortal()
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-Booking-Token' => config('services.booking_api.token'),
                ])
                ->get(config('services.booking_api.url') . '/appointments');
        } catch (\Throwable $e) {
            return false;
        }

        if (!$response->successful()) {
            return false;
        }

        foreach ($response->json('appointments', []) as $appointment) {
            $existing = OnlineAppointment::where('appointment_no', $appointment['appointment_no'])->first();

            $lockedStatuses = ['Completed', 'Rescheduled', 'Cancelled'];

            $status = $appointment['status'] ?? 'Approved';
            $staffResponse = $appointment['staff_response'] ?? null;
            $respondedAt = $appointment['responded_at'] ?? null;

            if ($existing && in_array($existing->status, $lockedStatuses)) {
                $status = $existing->status;
                $staffResponse = $existing->staff_response;
                $respondedAt = $existing->responded_at;
            }

            OnlineAppointment::updateOrCreate(
                [
                    'appointment_no' => $appointment['appointment_no'],
                ],
                [
                    'guardian_name' => $appointment['guardian_name'],
                    'child_name' => $appointment['child_name'],
                    'contact_number' => $appointment['contact_number'],
                    'appointment_date' => $appointment['appointment_date'],
                    'appointment_time' => $appointment['appointment_time'],
                    'reason_for_visit' => $appointment['reason_for_visit'] ?? null,
                    'status' => $status,
                    'staff_response' => $staffResponse,
                    'responded_at' => $respondedAt,
                    'synced_at' => now(),
                ]
            );
        }

        return true;
    }

    private function updatePortalStatus($appointmentNo, $status, $staffResponse)
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-Booking-Token' => config('services.booking_api.token'),
                ])
                ->patch(config('services.booking_api.url') . '/appointments/' . $appointmentNo . '/status', [
                    'status' => $status,
                    'staff_response' => $staffResponse,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
