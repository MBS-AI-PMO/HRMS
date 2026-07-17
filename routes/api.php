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

    Route::post('save-fcm-token', [ApiController::class, 'saveFcmToken']);
    Route::delete('fcm-token', [ApiController::class, 'deleteFcmToken']);

    Route::get('profile', [ApiController::class, 'profile']);
    Route::match(['put', 'patch'], 'profile', [ApiController::class, 'updateProfile']);
    Route::post('clock-in', [ApiController::class, 'clockIn']);
    Route::post('clock-out', [ApiController::class, 'clockOut']);
    

    Route::get('employees', [ApiController::class, 'employees']);
    Route::get('employees/{id}', [ApiController::class, 'employeeDetails']);

    Route::get('administrators', [ApiController::class, 'administrators']);
    Route::get('office-info', [ApiController::class, 'officeInfo']);

    Route::get('attendances', [ApiController::class, 'myAttendances']);

    Route::get('leave-types', [ApiController::class, 'leaveTypes']);
    Route::get('leave-balances', [ApiController::class, 'leaveBalances']);

    Route::get('leaves', [ApiController::class, 'myLeaves']);
    Route::post('leaves', [ApiController::class, 'storeLeave']);
    Route::get('leaves/{id}', [ApiController::class, 'showLeave'])->whereNumber('id');

    Route::get('wfh-requests', [ApiController::class, 'myWfhRequests']);
    Route::post('wfh-requests', [ApiController::class, 'storeWfhRequest']);
    Route::get('wfh-requests/{id}', [ApiController::class, 'showWfhRequest'])->whereNumber('id');

    Route::get('notifications/unread-count', [ApiController::class, 'notificationsUnreadCount']);
    Route::get('notifications', [ApiController::class, 'myNotifications']);
    Route::post('notifications/mark-read', [ApiController::class, 'markNotificationsRead']);
    Route::delete('notifications', [ApiController::class, 'clearNotifications']);
});


