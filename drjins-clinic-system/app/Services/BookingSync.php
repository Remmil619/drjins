<?php

namespace App\Services;

use App\Models\OnlineAppointment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class BookingSync
{
    private function client()
    {
        return Http::acceptJson()->connectTimeout(2)->timeout(5)
            ->withHeaders([
                'X-Booking-Token' => config('services.booking_api.token'),
            ]);
    }

    public function push(OnlineAppointment $appointment): bool
    {
        if (! config('services.booking_api.url') || ! config('services.booking_api.token')) {
            return false;
        }
        $payload = $appointment->only(['status', 'staff_response']);
        try {
            $response = $this->client()->patch(rtrim((string) config('services.booking_api.url'), '/').'/appointments/'.rawurlencode($appointment->appointment_no).'/status', $payload);
            if (! $response->successful() || ! is_array($response->json())) {
                return false;
            }
            // Do not acknowledge a newer local change made while this request was running.
            OnlineAppointment::whereKey($appointment->id)->where('status', $payload['status'])
                ->where('staff_response', $payload['staff_response'])->update([
                    'sync_pending' => false,
                ]);

            return true;
        } catch (ConnectionException $e) {
            return false;
        }
    }

    public function sync(): bool
    {
        if (! config('services.booking_api.url') || ! config('services.booking_api.token')) {
            return false;
        }
        try {
            // Send local changes first so an older portal status cannot overwrite them.
            $allUpdatesPushed = true;
            foreach (OnlineAppointment::where('sync_pending', true)->get() as $pending) {
                if (! $this->push($pending)) {
                    $allUpdatesPushed = false;
                }
            }
            $response = $this->client()->get(rtrim(config('services.booking_api.url'), '/').'/appointments');
            if (! $response->successful()) {
                return false;
            }
            $data = $response->json();
            $validator = Validator::make(is_array($data) ? $data : [], [

                'appointments' => 'present|array',

                'appointments.*.appointment_no' => 'required|string|max:255|distinct',

                'appointments.*.guardian_name' => 'required|string|max:255',

                'appointments.*.child_name' => 'required|string|max:255',

                'appointments.*.contact_number' => 'required|string|max:20',

                'appointments.*.appointment_date' => 'required|date_format:Y-m-d',

                'appointments.*.appointment_time' => 'required|date_format:H:i,H:i:s',

                'appointments.*.status' => 'required|in:Pending,Approved,Declined,Completed,Cancelled,Rescheduled,No Show,In Queue',

                'appointments.*.reason_for_visit' => 'nullable|string',

                'appointments.*.staff_response' => 'nullable|string',

                'appointments.*.responded_at' => 'nullable|date',

                'appointments.*.guardian_relationship' => 'nullable|string|max:100',

                'appointments.*.guardian_email' => 'nullable|email|max:255',

                'appointments.*.guardian_address' => 'nullable|string|max:500',

                'appointments.*.child_date_of_birth' => 'nullable|date_format:Y-m-d|before_or_equal:today',

                'appointments.*.child_sex' => 'nullable|in:Male,Female,Prefer not to say',

                'appointments.*.emergency_contact_name' => 'nullable|string|max:255',

                'appointments.*.emergency_contact_number' => 'nullable|string|max:20',

                'appointments.*.allergies' => 'nullable|string|max:2000',

                'appointments.*.current_medications' => 'nullable|string|max:2000',

                'appointments.*.medical_notes' => 'nullable|string|max:2000',

            ]);
            if ($validator->fails()) {
                return false;
            }
            foreach ($data['appointments'] as $row) {
                DB::transaction(function () use ($row) {
                    $appointment = OnlineAppointment::firstOrCreate([
                        'appointment_no' => $row['appointment_no'],
                    ], collect($row)->only(['guardian_name', 'child_name', 'contact_number', 'appointment_date', 'appointment_time', 'reason_for_visit', 'status'])->all());
                    $appointment = OnlineAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
                    $values = collect($row)->only(['guardian_name', 'child_name', 'contact_number', 'appointment_date', 'appointment_time', 'reason_for_visit', 'status', 'staff_response', 'responded_at'])->all();
                    $values += collect($row)->only(['guardian_relationship', 'guardian_email', 'guardian_address', 'child_date_of_birth', 'child_sex', 'emergency_contact_name', 'emergency_contact_number', 'allergies', 'current_medications', 'medical_notes'])->all();
                    if ($appointment->sync_pending) {
                        unset($values['status'], $values['staff_response'], $values['responded_at']);
                    }
                    $appointment->update($values + [
                        'synced_at' => now(),
                    ]);
                });
            }

            return $allUpdatesPushed;
        } catch (ConnectionException $e) {
            return false;
        }
    }
}
