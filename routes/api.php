<?php

use App\Http\Controllers\SpecialEventCategoryController;
use App\Http\Controllers\SpecialEventController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\QrcodeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Middleware\SuperAdminMiddleware;

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('/auth/yahoo', [AuthController::class, 'redirectToYahoo']);
Route::get('/auth/yahoo/callback', [AuthController::class, 'handleYahooCallback']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('jwt.auth')->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/set-password', [AuthController::class, 'setPassword']);

    Route::apiResource('/qrcodes', QrcodeController::class);
    Route::apiResource('/opd', OpdController::class);
    Route::apiResource('/attendance', AttendanceController::class);
    Route::get('/qrcodes/{qrId}/attendances', [AttendanceController::class, 'getAttendanceByQR']);
    Route::post('/leave-request', [AttendanceController::class, 'storeLeaveRequest']);
    
    Route::apiResource('/special-events', SpecialEventController::class);
    Route::apiResource('/special-event-categories', SpecialEventCategoryController::class);
    Route::get('/special-events/attendance/history', [AttendanceController::class, 'userSpecialEventHistory']);

    Route::get('/users', [UserController::class, 'index']);
    Route::middleware(SuperAdminMiddleware::class)->group(function () {
        Route::get('/superadmin/summary', [SuperAdminController::class, 'dashboardSummary']);
        Route::get('/superadmin/users', [SuperAdminController::class, 'getUsers']);
        Route::put('/superadmin/users/{id}/group', [SuperAdminController::class, 'updateUserRole']);
        Route::put('/superadmin/users/{id}/opd', [SuperAdminController::class, 'updateUserOpd']);
        Route::delete('/superadmin/users/{id}', [SuperAdminController::class, 'deleteUser']);

        Route::get('/superadmin/attendance', [SuperAdminController::class, 'getAttendance']);
        Route::delete('/superadmin/attendance/{id}', [SuperAdminController::class, 'deleteAttendance']);
    });

});
