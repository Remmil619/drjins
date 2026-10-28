<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserRegistrationController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClinicInquiryController;
use App\Http\Controllers\ClinicVaccineController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnlineBookingController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PediatricRecordController;
use App\Http\Controllers\QueueCheckInController;
use App\Http\Controllers\QueueController;
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
    Route::get('/users/register', [UserRegistrationController::class, 'create'])
        ->middleware('can:register-users')->name('users.create');
    Route::post('/users/register', [UserRegistrationController::class, 'store'])
        ->middleware('can:register-users')->name('users.store');
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
    Route::get('/patients/{patient}/consultations/{consultation}/print', [ConsultationController::class, 'print'])->name('consultations.print');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
});

Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/queue', [QueueCheckInController::class, 'index'])->name('queue.index');
    Route::get('/queue/search', [QueueCheckInController::class, 'search'])->name('queue.search');
    Route::post('/queue', [QueueCheckInController::class, 'store'])->name('queue.store');
    Route::post('/queue/{entry}/done', [QueueCheckInController::class, 'done'])->name('queue.done');
    Route::patch('/queue/{entry}', [QueueController::class, 'update'])->name('queue.update');
    Route::post('/patients/{patient}/growth', [PediatricRecordController::class, 'growth'])->name('growth.store');
    Route::patch('/patients/{patient}/growth/{record}', [PediatricRecordController::class, 'updateGrowth'])->name('growth.update');
    Route::post('/patients/{patient}/immunizations', [PediatricRecordController::class, 'immunization'])->name('immunizations.store');
    Route::patch('/patients/{patient}/immunizations/{record}', [PediatricRecordController::class, 'immunization'])->name('immunizations.update');
    Route::post('/patients/{patient}/clinic-vaccines', [ClinicVaccineController::class, 'store'])->name('clinic-vaccines.store');
    Route::patch('/patients/{patient}/clinic-vaccines/{vaccine}', [ClinicVaccineController::class, 'update'])->name('clinic-vaccines.update');
    Route::post('/patients/{patient}/alerts', [PediatricRecordController::class, 'alert'])->name('alerts.store');
    Route::post('/patients/{patient}/records/{type}/{record}/void', [PediatricRecordController::class, 'void'])->name('records.void');
    Route::get('/follow-ups', [OperationsController::class, 'reminders'])->name('reminders.index');
    Route::post('/follow-ups/{type}/{record}/close', [PediatricRecordController::class, 'closeReminder'])->name('reminders.close');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/patients/{patient}/billing/print', [BillingController::class, 'statement'])->name('billing.print');
    Route::post('/patients/{patient}/charges', [BillingController::class, 'charge'])->name('charges.store');
    Route::patch('/patients/{patient}/charges/{charge}/price', [BillingController::class, 'price'])->name('charges.price');
    Route::post('/charges/{charge}/payments', [BillingController::class, 'payment'])->name('payments.store');
    Route::post('/billing/{type}/{record}/void', [BillingController::class, 'void'])->name('billing.void');
    Route::get('/reports', [OperationsController::class, 'reports'])->name('reports.index');
    Route::get('/sales', [OperationsController::class, 'sales'])->name('sales.index');
    Route::get('/activity', [OperationsController::class, 'audit'])->name('audit.index');
});

Route::post('/patients/{patient}/checkout', [BillingController::class, 'checkout'])->middleware(['auth', PreventBackHistory::class])->name('billing.checkout');
Route::get('/inquiries', [ClinicInquiryController::class, 'index'])->middleware(['auth', PreventBackHistory::class])->name('inquiries.index');
Route::post('/inquiries/{reference}/reply', [ClinicInquiryController::class, 'reply'])->middleware(['auth', PreventBackHistory::class])->name('inquiries.reply');
