<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MedicalAlert extends Model
{
    protected $fillable = ['patient_id', 'user_id', 'category', 'description', 'reaction', 'voided_at', 'void_reason'];
    public function patient() { return $this->belongsTo(Patient::class); }
}
