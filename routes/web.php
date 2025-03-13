<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AuthController; // ✅ Use AuthController instead

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']); 
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']); 

Route::get('/auth/yahoo', [AuthController::class, 'redirectToYahoo']);
Route::get('/auth/yahoo/callback', [AuthController::class, 'handleYahooCallback']);

Route::get('/', function () {
    return view('welcome');
});
