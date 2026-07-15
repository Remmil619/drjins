<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;

Route::get('/', [BookingController::class, 'create'])
    ->name('booking.create');

Route::post('/book-appointment', [BookingController::class, 'store'])
    ->name('booking.store');

Route::get('/booking/success/{appointment_no}', [BookingController::class, 'success'])
    ->name('booking.success');

Route::get('/check-status', [BookingController::class, 'status'])
    ->name('booking.status');