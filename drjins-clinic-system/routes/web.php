<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
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