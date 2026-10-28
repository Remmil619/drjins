<?php

namespace App\Providers;

use App\Models\Charge;
use App\Models\Consultation;
use App\Models\GrowthRecord;
use App\Models\Immunization;
use App\Models\MedicalAlert;
use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\QueueEntry;
use App\Observers\RecordObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('register-users', fn (\App\Models\User $user) => $user->role === 'admin');
        Paginator::useBootstrapFive();
        foreach ([
            Patient::class, OnlineAppointment::class, Consultation::class,
            QueueEntry::class, GrowthRecord::class, Immunization::class,
            MedicalAlert::class, Charge::class, Payment::class,
        ] as $model) {
            $model::observe(RecordObserver::class);
        }
    }
}
