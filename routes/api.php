<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\QrcodeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController; // Import UserController

// Google Authentication
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Public Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected Routes - Require Token
Route::middleware(['jwt.auth'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']); // Fixed naming consistency

    // New route for setting a password (only if it hasn't been set before)
    Route::post('/auth/set-password', [AuthController::class, 'setPassword']);

    Route::apiResource('/qrcodes', QrcodeController::class);
    Route::apiResource('/opd', OpdController::class);
    Route::apiResource('/attendance', AttendanceController::class);

    // New endpoint to fetch all users
    Route::get('/users', [UserController::class, 'index']);
});
