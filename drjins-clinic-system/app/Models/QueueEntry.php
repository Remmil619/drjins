<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueEntry extends Model
{
    protected $fillable = ['patient_id', 'online_appointment_id', 'visit_date', 'status', 'reason', 'started_at', 'completed_at'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(OnlineAppointment::class, 'online_appointment_id');
    }
}
