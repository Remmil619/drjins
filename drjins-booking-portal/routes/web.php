<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\InquiryController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
foreach (['services', 'patient-hub', 'contact', 'about', 'privacy'] as $page) {
    Route::view('/'.$page, 'pages.'.$page)->name($page);
}

Route::redirect('/products', '/contact?topic=Product%20availability')->name('products');

Route::post('/contact', [InquiryController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/book-appointment', [BookingController::class, 'create'])
    ->name('booking.create');

Route::post('/book-appointment', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('booking.store');

Route::get('/booking/success/{appointment_no}', [BookingController::class, 'success'])
    ->name('booking.success');

Route::get('/check-status', [BookingController::class, 'status'])
    ->name('booking.status');
Route::post('/check-status', [BookingController::class, 'status'])->middleware('throttle:10,1');
