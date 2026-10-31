<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicUnavailableDate extends Model
{
    protected $fillable = ['date', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
