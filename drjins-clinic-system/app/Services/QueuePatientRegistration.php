<?php

namespace App\Services;

use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\QueueEntry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QueuePatientRegistration
{
    // Call inside a transaction with the queue entry and booking locked.
    public function attach(QueueEntry $entry, ?OnlineAppointment $booking, array $input = []): void
    {
            if (! $entry->patient_id && $booking) {
                $patient = $booking->patient ?? Patient::where('online_appointment_no', $booking->appointment_no)->first();
                if (! $patient) {
                    $details = Validator::make([
                        'birthdate' => $input['birthdate'] ?? $booking->child_date_of_birth,
                        'sex' => $input['sex'] ?? $booking->child_sex,
                    ], ['birthdate' => 'required|date_format:Y-m-d|before_or_equal:today', 'sex' => 'required|in:Male,Female'])->validate();
                    $parts = preg_split('/\s+/', trim($booking->child_name));
                    $first = array_shift($parts);
                    $last = count($parts) ? array_pop($parts) : 'For Update';
                    $patient = Patient::create([
                        'patient_no' => 'PAT-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                        'online_appointment_no' => $booking->appointment_no,
                        'first_name' => $first, 'last_name' => $last, 'middle_name' => implode(' ', $parts) ?: null,
                        'birthdate' => $details['birthdate'], 'sex' => $details['sex'],
                        'guardian_name' => $booking->guardian_name, 'guardian_relationship' => $booking->guardian_relationship,
                        'contact_number' => $booking->contact_number, 'address' => $booking->guardian_address,
                        'emergency_contact' => $booking->emergency_contact_number, 'status' => 'Active',
                    ]);
                }
                $entry->patient_id = $patient->id;
            }
    }
}
