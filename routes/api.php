<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarbershopController;
use App\Http\Controllers\BarberController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;

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

        Route::apiResource('clients', ClientController::class);

        Route::prefix('clients/custom')->group(function () {
            Route::post('/{client}/loyalty-points', [ClientController::class, 'updateLoyaltyPoints']);
            Route::get('/user/{userId}', [ClientController::class, 'getByUserId']);
            Route::get('/nearby/search', [ClientController::class, 'nearbyClients']);
        });

        Route::apiResource('services', ServiceController::class);

        Route::prefix('services/custom')->group(function () {
            Route::get('/active', [ServiceController::class, 'active']);
            Route::get('/inactive', [ServiceController::class, 'inactive']);
            Route::get('/trashed', [ServiceController::class, 'trashed']);
            Route::get('/by-barber', [ServiceController::class, 'byBarber']);
            Route::post('/{id}/restore', [ServiceController::class, 'restore']);
            Route::post('/{id}/toggle-status', [ServiceController::class, 'toggleStatus']);
        });

        Route::apiResource('products', ProductController::class);
        
        Route::prefix('products/custom')->group(function () {
            Route::post('/{product}/stock', [ProductController::class, 'updateStock']);
            Route::get('/categories', [ProductController::class, 'categories']);
            Route::get('/report/low-stock', [ProductController::class, 'lowStockReport']);
            Route::get('/report/out-of-stock', [ProductController::class, 'outOfStockReport']);
        });
        
        Route::apiResource('appointments', AppointmentController::class);

        Route::prefix('appointments/custom')->group(function () {
            Route::get('/my-appointments', [AppointmentController::class, 'myAppointments']);
            Route::get('/barber-appointments', [AppointmentController::class, 'barberAppointments']);
        });

        Route::apiResource('appointments', AppointmentController::class);
    
        Route::prefix('appointments/custom')->group(function () {
            Route::get('/barbers/{barber}/available-slots/{date}', [AppointmentController::class, 'availableSlots']);
            Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel']);
            Route::post('/{appointment}/complete', [AppointmentController::class, 'complete']);
            Route::post('/{appointment}/confirm', [AppointmentController::class, 'confirm']);
            Route::get('/appointments/upcoming', [AppointmentController::class, 'upcoming']);
            Route::get('/appointments/history', [AppointmentController::class, 'history']);
            Route::get('/services', [AppointmentController::class, 'services']);
            Route::get('/products', [AppointmentController::class, 'products']);
            Route::get('/client/{client}', [AppointmentController::class, 'byClient']);
            Route::get('/barber/{barber}', [AppointmentController::class, 'byBarber']);
            Route::get('/barbershop/{barbershop}', [AppointmentController::class, 'byBarbershop']);
        });
    });
});