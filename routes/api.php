<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\Master\AppointmentController;
use App\Http\Controllers\Api\Master\AppointmentNotificationController;
use App\Http\Controllers\Api\Master\AppointmentCancelController;
use App\Http\Controllers\Api\Master\MasterSearchController;
use App\Http\Controllers\Api\Master\MasterSlotController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/cities', [CityController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/masters/{master}/services', [ServiceController::class, 'forMaster'])->whereNumber('master');
Route::get('/masters/{master}/slots', [MasterSlotController::class, 'index'])->whereNumber('master');
Route::get('/masters', [MasterSearchController::class, 'index']);
// Создание записи только для авторизованного мастера

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::post('/appointments/{appointment}/notify', [AppointmentNotificationController::class, 'notify'])->whereNumber('appointment');
    Route::post('/appointments/{appointment}/cancel', [AppointmentCancelController::class, 'cancel'])->whereNumber('appointment');
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show'])->whereNumber('client');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->whereNumber('client');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->whereNumber('client');
    Route::get('/appointments/at', [AppointmentController::class, 'showAt']);
});
