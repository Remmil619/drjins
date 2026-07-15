<?php

namespace App\Http\Controllers;

use App\Models\OnlineAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OnlineBookingController extends Controller
{
    public function redirectToAppointments()
    {
        return redirect()->route('appointments.index');
    }

    public function index(Request $request)
    {
        $selectedDate = $request->date ?? now()->toDateString();

        $this->syncBookingsFromPortal();

        return view('appointments.index', compact('selectedDate'));
    }

    public function live(Request $request)
    {
        $selectedDate = $request->date ?? now()->toDateString();

        $connected = $this->syncBookingsFromPortal();

        $appointments = OnlineAppointment::whereDate('appointment_date', $selectedDate)
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
            'date' => $selectedDate,
            'last_checked' => now()->format('h:i:s A'),
        ]);
    }

    public function sync()
    {
        if (!$this->syncBookingsFromPortal()) {
            return redirect()->route('dashboard')
                ->with('error', 'Unable to sync appointments. Please check the booking website connection.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Appointments synced successfully.');
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
                    'reason_for_visit' => $appointment['reason_for_visit'],
                    'status' => $appointment['status'],
                    'staff_response' => $appointment['staff_response'],
                    'responded_at' => $appointment['responded_at'],
                    'synced_at' => now(),
                ]
            );
        }

        return true;
    }
}
