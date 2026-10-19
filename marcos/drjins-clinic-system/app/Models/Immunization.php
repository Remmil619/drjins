<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Immunization extends Model
{
    protected $fillable = ['patient_id', 'user_id', 'vaccine', 'dose', 'administered_on', 'source', 'provider', 'lot_number', 'expiry_date', 'next_due_on', 'reminder_closed_at', 'reminder_outcome', 'notes', 'voided_at', 'void_reason'];
    public function patient() { return $this->belongsTo(Patient::class); }
}
