<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'appointment_no',
        'guardian_name',
        'guardian_relationship',
        'guardian_email',
        'guardian_address',
        'child_name',
        'child_date_of_birth',
        'child_sex',
        'contact_number',
        'emergency_contact_name',
        'emergency_contact_number',
        'allergies',
        'current_medications',
        'medical_notes',
        'appointment_date',
        'appointment_time',
        'reason_for_visit',
        'status',
        'staff_response',
        'responded_at',
    ];
}
