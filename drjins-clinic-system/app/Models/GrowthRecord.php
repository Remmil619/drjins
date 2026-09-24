<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowthRecord extends Model
{
    protected $fillable = ['patient_id', 'user_id', 'measured_on', 'weight_kg', 'height_cm', 'head_cm', 'temperature_c', 'notes', 'voided_at', 'void_reason'];
    public function patient() { return $this->belongsTo(Patient::class); }
}
