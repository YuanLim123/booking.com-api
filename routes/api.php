<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\Owner\PropertyController;
use App\Http\Middleware\GateDefineMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('auth/register', RegisterController::class);

Route::middleware(['auth:sanctum', GateDefineMiddleware::class])->group(function () {
    // No owner/user grouping, for now, will do it later with more routes
    Route::get('owner/properties', [PropertyController::class, 'index']);
    Route::get('user/bookings', [BookingController::class, 'index']);

    Route::post('owner/properties', [PropertyController::class, 'store']);
});
