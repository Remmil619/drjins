<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicVaccine extends Model
{
    protected $fillable = ['name', 'available', 'price_centavos'];

    protected $casts = [
        'available' => 'boolean',
    ];
}
