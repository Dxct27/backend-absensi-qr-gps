<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\JWTAuthController;

Route::get('/auth/google', [JWTAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [JWTAuthController::class, 'handleGoogleCallback']);


Route::get('/', function () {
    return view('welcome');
});
