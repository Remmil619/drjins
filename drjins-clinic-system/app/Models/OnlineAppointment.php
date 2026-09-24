<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineAppointment extends Model
{
    public function assertStatusChangeAllowed(string $status, string $field = 'status'): void
    {
        if ($this->status === $status) return;
        $message = null;
        if ($this->status === 'Completed') {
            $message = 'This visit is already completed. Arrange a new booking for another visit.';
        } elseif ($this->queueEntry()->whereIn('status', ['waiting', 'in_consultation', 'completed'])->exists()) {
            $message = 'This booking has a clinic queue entry. Complete the consultation or mark the patient as left in the queue first.';
        } elseif (in_array($status, ['Completed', 'Rescheduled'], true) && in_array($this->status, ['Cancelled', 'Declined'], true)) {
            $message = 'This appointment is cancelled or declined. Use the queue for a new visit.';
        } elseif ($status === 'Completed' && $this->appointment_date > today()->toDateString()) {
            $message = 'A future appointment cannot be marked as completed.';
        }
        if ($message) throw \Illuminate\Validation\ValidationException::withMessages([$field => $message]);
    }

    public function queueEntry() { return $this->hasOne(QueueEntry::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    protected $fillable = [
        'appointment_no', 'patient_id',
        'guardian_name',
        'guardian_relationship', 'guardian_email', 'guardian_address',
        'child_date_of_birth', 'child_sex', 'emergency_contact_name',
        'emergency_contact_number', 'allergies', 'current_medications', 'medical_notes',
        'child_name',
        'contact_number',
        'appointment_date',
        'appointment_time',
        'reason_for_visit',
        'status',
        'staff_response',
        'responded_at',
        'synced_at', 'sync_pending',
    ];
}

