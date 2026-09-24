<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'patient_no',
        'online_appointment_no',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthdate',
        'sex',
        'height',
        'weight',
        'temperature',
        'address',
        'guardian_name',
        'guardian_relationship',
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
        $middleName = $this->middle_name ? ' '.$this->middle_name : '';

        $suffix = $this->suffix ? ' '.$this->suffix : '';

        return $this->first_name.$middleName.' '.$this->last_name.$suffix;
    }

    public function getAgeLabelAttribute(): string
    {
        if (!$this->birthdate) return 'Not recorded';
        $birth=Carbon::parse($this->birthdate);
        $months=(int)$birth->diffInMonths(today());
        if ($months < 1) return (int)$birth->diffInDays(today()).' days';
        if ($months < 24) return $months.' months';
        return (int)$birth->age.' years';
    }
    public function getAgeAttribute()
    {
        return $this->birthdate ? Carbon::parse($this->birthdate)->age : null;
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class)->orderByDesc('visit_date')->orderByDesc('id');
    }

    public function appointment()
    {
        return $this->belongsTo(
            OnlineAppointment::class,
            'online_appointment_no',
            'appointment_no'
        );
    }
}

