<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Charge extends Model
{
    protected $fillable = ['patient_id', 'user_id', 'charged_on', 'description', 'amount_centavos', 'request_key', 'voided_at', 'void_reason'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function getPaidCentavosAttribute(): int { return (int) $this->payments()->whereNull('voided_at')->sum('amount_centavos'); }
    public function getBalanceCentavosAttribute(): int { return $this->voided_at ? 0 : $this->amount_centavos - $this->paid_centavos; }
}
