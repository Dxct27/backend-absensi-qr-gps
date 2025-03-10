<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AuthController; // ✅ Use AuthController instead

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']); // ✅ Update to AuthController
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']); // ✅ Update to AuthController

Route::get('/', function () {
    return view('welcome');
});
