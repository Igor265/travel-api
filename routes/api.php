<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TravelOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/logout', [AuthController::class, 'logout']);

    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('/travel-orders', [TravelOrderController::class, 'store']);
    Route::get('/travel-orders', [TravelOrderController::class, 'index']);
    Route::get('/travel-orders/{travelOrder}', [TravelOrderController::class, 'show']);
    Route::patch('/travel-orders/{travelOrder}/status', [TravelOrderController::class, 'updateStatus']);
});
