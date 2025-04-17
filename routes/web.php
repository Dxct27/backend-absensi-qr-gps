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

Route::get('/create-superadmin', function () {
    \Illuminate\Support\Facades\Artisan::call('superadmin:create');
    return 'Superadmin created (default email & password).';
});
