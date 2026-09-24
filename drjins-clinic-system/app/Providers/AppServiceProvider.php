<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();
        foreach ([
            \App\Models\Patient::class, \App\Models\OnlineAppointment::class, \App\Models\Consultation::class,
            \App\Models\QueueEntry::class, \App\Models\GrowthRecord::class, \App\Models\Immunization::class,
            \App\Models\MedicalAlert::class, \App\Models\Charge::class, \App\Models\Payment::class,
        ] as $model) $model::observe(\App\Observers\RecordObserver::class);
    }
}
