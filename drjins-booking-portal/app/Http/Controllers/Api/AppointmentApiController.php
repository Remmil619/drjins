<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AppointmentApiController extends Controller
{
    private function authorizeRequest(Request $request)
    {
        $token = config('services.booking_api_token');
        if (! is_string($token) || $token === '' || ! hash_equals($token, (string) $request->header('X-Booking-Token'))) {
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
            'status' => 'required|in:Pending,Approved,Declined,Completed,Cancelled,Rescheduled,No Show,In Queue',
            'staff_response' => 'nullable|string|max:1000',
        ]);

        $appointment = Appointment::where('appointment_no', $appointment_no)->firstOrFail();

        try {
            return Cache::lock('appointment-'.$appointment->appointment_date.'-'.Carbon::parse($appointment->appointment_time)->format('H:i'), 15)->block(5, function () use ($appointment, $validated) {
                $appointment->refresh();
                if ($appointment->status === 'Completed' && $validated['status'] !== 'Completed') {
                    return response()->json(['message' => 'A completed visit cannot be reopened. Please create a new booking.'], 409);
                }
                if (! in_array($validated['status'], ['Cancelled', 'Declined', 'No Show'], true)
                    && Appointment::whereDate('appointment_date', $appointment->appointment_date)
                        ->whereIn('appointment_time', [Carbon::parse($appointment->appointment_time)->format('H:i'), Carbon::parse($appointment->appointment_time)->format('H:i:s')])
                        ->where('id', '!=', $appointment->id)
                        ->whereNotIn('status', ['Cancelled', 'Declined', 'No Show'])->exists()) {
                    return response()->json(['message' => 'This slot is already occupied by another appointment.'], 409);
                }
                $appointment->update([
                    'status' => $validated['status'],
                    'staff_response' => $validated['staff_response'] ?? null,
                    'responded_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Appointment status updated successfully.',
                    'appointment' => $appointment,
                ]);
            });
        } catch (LockTimeoutException $e) {
            return response()->json(['message' => 'This appointment is being updated. Please retry.'], 409);
        }
    }
}
