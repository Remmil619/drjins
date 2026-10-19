<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['charge_id', 'user_id', 'paid_on', 'amount_centavos', 'method', 'reference', 'request_key', 'voided_at', 'void_reason'];

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }
}
