<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'nullable|string|max:255|unique:users',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'opd_id' => 'required|exists:opds,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'nip' => $request->nip,
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password ? Hash::make($request->password) : null,
            'opd_id' => $request->opd_id,
        ]);

        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'token' => $token,
            'user' => Auth::user(),
        ], 200);
    }
    public function redirectToGoogle()
    {
        // dd("test");
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $frontendUrl = config('app.frontend_url');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            } else {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            }

            \Log::info("User Found or Created: ", ['user_id' => $user->id]);
            \Log::info("User password: ", ['password' => $user->password]);

            $token = JWTAuth::fromUser($user);

            \Log::info("JWT Token Generated: ", ['token' => $token]);

            return redirect()->away("$frontendUrl/auth/google/callback?token=$token");
        } catch (\Exception $e) {
            \Log::error("Google Login Failed: " . $e->getMessage());
            return redirect()->away("$frontendUrl/auth/google/callback?error=Google authentication failed");
        }
    }

    public function redirectToYahoo()
    {
        return Socialite::driver('yahoo')->redirect();
    }

    public function handleYahooCallback()
    {
        $frontendUrl = config('app.frontend_url');

        try {
            $yahooUser = Socialite::driver('yahoo')->stateless()->user();

            $user = User::where('email', $yahooUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'yahoo_id' => $yahooUser->getId(),
                    'yahoo_avatar' => $yahooUser->getAvatar(),
                ]);
            } else {
                $user = User::create([
                    'name' => $yahooUser->getName(),
                    'email' => $yahooUser->getEmail(),
                    'yahoo_id' => $yahooUser->getId(),
                    'yahoo_avatar' => $yahooUser->getAvatar(),
                ]);
            }

            \Log::info("User Found or Created: ", ['user_id' => $user->id]);

            $token = JWTAuth::fromUser($user);

            \Log::info("JWT Token Generated: ", ['token' => $token]);

            return redirect()->away("$frontendUrl/auth/yahoo/callback?token=$token");
        } catch (\Exception $e) {
            \Log::error("Yahoo Login Failed: " . $e->getMessage());
            return redirect()->away("$frontendUrl/auth/yahoo/callback?error=Yahoo authentication failed");
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Logged out successfully'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }
    }

    public function user()
    {
        return response()->json(
            [
                'user' => Auth::user(),
                'has_password' => is_null(Auth::user()->password) ? false : true,
            ]
        );
    }

    public function setPassword(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password set successfully'], 200);
    }

    public function refreshToken() {
        try {
            $newToken = JWTAuth::refresh();
            \Log::info("JWT Token Refreshed: ", ['token' => $newToken]);
            return response()->json(['token' => $newToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token refresh failed'], 401);
        }
    }

}
