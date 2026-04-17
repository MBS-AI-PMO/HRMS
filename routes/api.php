<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DemoAutoUpdateController;
use App\Http\Controllers\Api\ApiController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('is-update-available', [DemoAutoUpdateController::class, 'isUpdateAvailable'])->name('is-update-available');

Route::post('login', [ApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [ApiController::class, 'logout']);
    Route::get('profile', [ApiController::class, 'profile']);

    Route::get('employees', [ApiController::class, 'employees']);
    Route::get('employees/{id}', [ApiController::class, 'employeeDetails']);

    Route::get('administrators', [ApiController::class, 'administrators']);
    Route::get('attendances', [ApiController::class, 'attendanceList']);
});


