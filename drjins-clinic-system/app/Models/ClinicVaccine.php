<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicVaccine extends Model
{
    protected $fillable = ['name', 'available', 'price_centavos', 'dose_count', 'dose_interval_months'];

    protected $casts = [
        'available' => 'boolean',
        'dose_count' => 'integer',
        'dose_interval_months' => 'integer',
    ];
}
