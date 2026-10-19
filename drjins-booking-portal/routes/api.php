<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppointmentApiController;
use App\Http\Controllers\Api\InquiryApiController;

Route::get('/inquiries', [InquiryApiController::class, 'index']);
Route::get('/inquiries/{reference}', [InquiryApiController::class, 'show']);
Route::post('/inquiries/{reference}/replies', [InquiryApiController::class, 'reply']);

Route::get('/appointments', [AppointmentApiController::class, 'index']);

Route::patch('/appointments/{appointment_no}/status', [AppointmentApiController::class, 'updateStatus']);
