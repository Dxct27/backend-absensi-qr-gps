<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JWTAuthController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\QrcodeController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('/qrcode', QrcodeController::class);
Route::apiResource('/opd', OpdController::class);
Route::apiResource('/attendance', AttendanceController::class);

// Route::post('/auth/register', [AuthController::class, 'register']);
// Route::post('/auth/login', [AuthController::class, 'login']);
// Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::post('/auth/register', [JWTAuthController::class, 'register']);
Route::post('/auth/login', [JWTAuthController::class, 'login']);
Route::post('/auth/logout', [JWTAuthController::class, 'logout']);

Route::get('/post', function() {
    return 'test';
});
