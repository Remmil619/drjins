<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Patient extends Model
{
    protected $fillable = [
        'patient_no',
        'online_appointment_no',
        'first_name',
        'middle_name',
        'last_name',
        'birthdate',
        'sex',
        'height',
        'weight',
        'address',
        'guardian_name',
        'contact_number',
        'emergency_contact',
        'status',
    ];

    protected $appends = [
        'full_name',
        'age',
    ];

    public function getFullNameAttribute()
    {
        $middleName = $this->middle_name ? ' ' . $this->middle_name : '';

        return $this->first_name . $middleName . ' ' . $this->last_name;
    }

    public function getAgeAttribute()
    {
        return Carbon::parse($this->birthdate)->age;
    }
}