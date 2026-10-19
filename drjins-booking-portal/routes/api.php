<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppointmentApiController;
use App\Http\Controllers\Api\InquiryApiController;

Route::get('/inquiries', [InquiryApiController::class, 'index']);

Route::get('/appointments', [AppointmentApiController::class, 'index']);

Route::patch('/appointments/{appointment_no}/status', [AppointmentApiController::class, 'updateStatus']);
