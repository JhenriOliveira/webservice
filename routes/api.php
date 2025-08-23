<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarbershopController;
use App\Http\Controllers\BarberController;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'version' => '1.0',
            'timestamp' => now()->toDateTimeString(),
            'environment' => app()->environment(),
        ]);
    });

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    
        Route::apiResource('barbershops', BarbershopController::class);

        Route::prefix('barbershops/custom')->group(function () {
            Route::get('/active', [BarbershopController::class, 'active']);
            Route::get('/inactive', [BarbershopController::class, 'inactive']);
            Route::get('/trashed', [BarbershopController::class, 'trashed']);
            Route::post('/{id}/restore', [BarbershopController::class, 'restore']);
            Route::post('/{id}/toggle-status', [BarbershopController::class, 'toggleStatus']);
        });

        Route::apiResource('barbers', BarberController::class);

        Route::prefix('barbers/custom')->group(function () {
            Route::get('/active', [BarberController::class, 'active']);
            Route::get('/inactive', [BarberController::class, 'inactive']);
            Route::get('/trashed', [BarberController::class, 'trashed']);
            Route::get('/barbershop/{barbershopId}', [BarberController::class, 'byBarbershop']);
            Route::post('/{id}/restore', [BarberController::class, 'restore']);
            Route::post('/{id}/toggle-status', [BarberController::class, 'toggleStatus']);
        });
    });
});