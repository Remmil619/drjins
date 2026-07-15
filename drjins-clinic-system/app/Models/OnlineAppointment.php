<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineAppointment extends Model
{
    protected $fillable = [
        'appointment_no',
        'guardian_name',
        'child_name',
        'contact_number',
        'appointment_date',
        'appointment_time',
        'reason_for_visit',
        'status',
        'staff_response',
        'responded_at',
        'synced_at',
    ];
}