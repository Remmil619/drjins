<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = ['patient_id', 'user_id', 'visit_date', 'complaint', 'assessment', 'plan', 'prescription', 'follow_up_date', 'queue_entry_id', 'request_key', 'follow_up_closed_at', 'follow_up_outcome', 'voided_at', 'void_reason'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}


