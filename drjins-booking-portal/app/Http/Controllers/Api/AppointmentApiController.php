<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentApiController extends Controller
{
    private function authorizeRequest(Request $request)
    {
        if ($request->header('X-Booking-Token') !== env('BOOKING_API_TOKEN')) {
            abort(403, 'Unauthorized API request.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeRequest($request);

        $appointments = Appointment::latest()->get();

        return response()->json([
            'appointments' => $appointments,
        ]);
    }

    public function updateStatus(Request $request, $appointment_no)
    {
        $this->authorizeRequest($request);

        $validated = $request->validate([
            'status' => 'required|in:Pending,Approved,Declined,Completed,Cancelled,Rescheduled',
            'staff_response' => 'nullable|string|max:1000',
        ]);

        $appointment = Appointment::where('appointment_no', $appointment_no)->firstOrFail();

        $appointment->update([
            'status' => $validated['status'],
            'staff_response' => $validated['staff_response'] ?? null,
            'responded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Appointment status updated successfully.',
            'appointment' => $appointment,
        ]);
    }
}