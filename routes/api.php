<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/calendar', [BookingController::class, 'calendar']);
