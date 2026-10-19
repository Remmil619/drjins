<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnlineBookingController;
use App\Http\Controllers\PatientController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/booking', [BookingController::class, 'create'])
        ->name('booking.create');

    Route::post('/booking', [BookingController::class, 'store'])
        ->name('booking.store');

    Route::get('/appointments', [OnlineBookingController::class, 'index'])
        ->name('appointments.index');

    Route::get('/appointments/live', [OnlineBookingController::class, 'live'])
        ->name('appointments.live');

    Route::post('/appointments/sync', [OnlineBookingController::class, 'sync'])
        ->name('appointments.sync');

    Route::post('/appointments/update-by-reference', [OnlineBookingController::class, 'updateByReference'])
        ->name('appointments.updateByReference');

    Route::post('/appointments/save-patient-details', [OnlineBookingController::class, 'savePatientDetails'])
        ->name('appointments.savePatientDetails');

    Route::post('/appointments/done', [OnlineBookingController::class, 'done'])->name('appointments.done');

    Route::post('/appointments/update-status', [OnlineBookingController::class, 'updateStatus'])
        ->name('appointments.updateStatus');

    Route::get('/online-bookings', [OnlineBookingController::class, 'redirectToAppointments'])
        ->name('online-bookings.index');

    Route::get('/online-bookings/live', [OnlineBookingController::class, 'live'])
        ->name('online-bookings.live');

    Route::post('/online-bookings/sync', [OnlineBookingController::class, 'sync'])
        ->name('online-bookings.sync');

    Route::get('/patients', [PatientController::class, 'index'])
        ->name('patients.index');

    Route::get('/patients/create', [PatientController::class, 'create'])
        ->name('patients.create');

    Route::post('/patients', [PatientController::class, 'store'])
        ->name('patients.store');

    Route::get('/patients/{patient}', [PatientController::class, 'show'])
        ->name('patients.show');

});
Route::post('/patients/{patient}/consultations', [ConsultationController::class, 'store'])
    ->middleware(['auth', PreventBackHistory::class])->name('consultations.store');
Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/patients/{patient}/prescriptions/{consultation}', [ConsultationController::class, 'prescription'])->name('prescriptions.print');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
});

Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/queue', [\App\Http\Controllers\QueueController::class, 'index'])->name('queue.index');
    Route::post('/queue', [\App\Http\Controllers\QueueController::class, 'store'])->name('queue.store');
    Route::patch('/queue/{entry}', [\App\Http\Controllers\QueueController::class, 'update'])->name('queue.update');
    Route::post('/patients/{patient}/growth', [\App\Http\Controllers\PediatricRecordController::class, 'growth'])->name('growth.store');
    Route::post('/patients/{patient}/immunizations', [\App\Http\Controllers\PediatricRecordController::class, 'immunization'])->name('immunizations.store');
    Route::post('/patients/{patient}/clinic-vaccines', [\App\Http\Controllers\ClinicVaccineController::class, 'store'])->name('clinic-vaccines.store');
    Route::patch('/patients/{patient}/clinic-vaccines/{vaccine}', [\App\Http\Controllers\ClinicVaccineController::class, 'update'])->name('clinic-vaccines.update');
    Route::post('/patients/{patient}/alerts', [\App\Http\Controllers\PediatricRecordController::class, 'alert'])->name('alerts.store');
    Route::post('/patients/{patient}/records/{type}/{record}/void', [\App\Http\Controllers\PediatricRecordController::class, 'void'])->name('records.void');
    Route::get('/follow-ups', [\App\Http\Controllers\OperationsController::class, 'reminders'])->name('reminders.index');
    Route::post('/follow-ups/{type}/{record}/close', [\App\Http\Controllers\PediatricRecordController::class, 'closeReminder'])->name('reminders.close');
    Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
    Route::post('/patients/{patient}/charges', [\App\Http\Controllers\BillingController::class, 'charge'])->name('charges.store');
    Route::post('/charges/{charge}/payments', [\App\Http\Controllers\BillingController::class, 'payment'])->name('payments.store');
    Route::post('/billing/{type}/{record}/void', [\App\Http\Controllers\BillingController::class, 'void'])->name('billing.void');
    Route::get('/reports', [\App\Http\Controllers\OperationsController::class, 'reports'])->name('reports.index');
    Route::get('/activity', [\App\Http\Controllers\OperationsController::class, 'audit'])->name('audit.index');
});

Route::post('/patients/{patient}/checkout', [\App\Http\Controllers\BillingController::class, 'checkout'])->middleware(['auth', PreventBackHistory::class])->name('billing.checkout');