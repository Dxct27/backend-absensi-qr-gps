<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Laravel\Socialite\Facades\Socialite;

class JWTAuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string|max:255|unique:users',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'opd_id' => 'required|exists:opds,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create([
            'nip' => $request->nip,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'opd_id' => $request->opd_id,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'), 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $user = auth()->user();
            return response()->json(compact('user', 'token'));
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    public function getUser()
{
    try {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'group' => $user->group, // ✅ Ensure group is returned
            'opd_id' => $user->opd_id, // ✅ Include OPD if needed
        ]);
    } catch (TokenExpiredException $e) {
        return response()->json(['error' => 'Token has expired'], 401);
    } catch (TokenInvalidException $e) {
        return response()->json(['error' => 'Token is invalid'], 401);
    } catch (JWTException $e) {
        return response()->json(['error' => 'Token is missing'], 401);
    }
}



    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Successfully logged out']);
    }

    // Google OAuth Authentication
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $frontendUrl = config('app.frontend_url');
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            \Log::info("Google User Retrieved: ", [
                'id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
            ]);

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => null,
                    ]
            );
            
            \Log::info("User Updated or Created: ", ['user_id' => $user->id]);
            
            $token = JWTAuth::fromUser($user);

            \Log::info("JWT Token Generated: ", ['token' => $token]);

            return redirect("$frontendUrl/auth/google/callback?token=$token");
        } catch (\Exception $e) {
            \Log::error("Google Login Failed: " . $e->getMessage());
            return redirect("$frontendUrl/clipboard")->with('error', 'Google login failed.');
        }
    }

}