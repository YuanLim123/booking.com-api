<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\Owner\PropertyController;
use App\Http\Controllers\Public\PropertySearchController;
use App\Http\Middleware\GateDefineMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('auth/register', RegisterController::class);

Route::middleware(['auth:sanctum', GateDefineMiddleware::class])->group(function () {

    Route::prefix('owner')->group(function () {
        Route::get('properties', [PropertyController::class, 'index']);
        Route::post('properties', [PropertyController::class, 'store']);
    });

    Route::prefix('user')->group(function () {
        Route::get('bookings', [BookingController::class, 'index']);
    });
});

Route::get('search', PropertySearchController::class);
