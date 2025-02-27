<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JWTAuthController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\QrcodeController;
use App\Http\Controllers\AttendanceController;

Route::get('/auth/google', [JWTAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [JWTAuthController::class, 'handleGoogleCallback']);

// Public Routes
Route::post('/auth/register', [JWTAuthController::class, 'register']);
Route::post('/auth/login', [JWTAuthController::class, 'login']);

// Protected Routes - Require Token
Route::middleware(['jwt.auth'])->group(function () {
    Route::post('/auth/logout', [JWTAuthController::class, 'logout']);
    Route::get('/user', [JWTAuthController::class, 'getUser']); // Fix the /api/user route

    Route::apiResource('/qrcodes', QrcodeController::class);
    Route::apiResource('/opd', OpdController::class);
    Route::apiResource('/attendance', AttendanceController::class);
});



