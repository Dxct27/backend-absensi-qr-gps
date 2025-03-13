<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\QrcodeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController; // Import UserController

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::get('/auth/yahoo', [AuthController::class, 'redirectToYahoo']);
Route::get('/auth/yahoo/callback', [AuthController::class, 'handleYahooCallback']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['jwt.auth'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']); // Fixed naming consistency

    Route::post('/auth/set-password', [AuthController::class, 'setPassword']);

    Route::apiResource('/qrcodes', QrcodeController::class);
    Route::apiResource('/opd', OpdController::class);
    Route::apiResource('/attendance', AttendanceController::class);

    Route::post('/leave-request', [AttendanceController::class, 'storeLeaveRequest']);

    Route::get('/users', [UserController::class, 'index']);
});
