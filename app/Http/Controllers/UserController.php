<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Authentication failed, user not found'], 401);
        }

        if ($user->group === 'superadmin') {
            // Superadmin gets all users
            $users = User::all();
        } elseif ($user->group === 'admin') {
            // Admin gets only users in the same opd
            $users = User::where('opd_id', $user->opd_id)->get();
        } else {
            // Regular users cannot access this
            return response()->json(['message' => 'Unauthorized (user controller)'], 403);
        }

        return response()->json($users);
    }

}
