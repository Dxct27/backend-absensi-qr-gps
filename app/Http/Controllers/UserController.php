<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Authentication failed, user not found'], 401);
        }

        // Base query with OPD relation
        $query = User::with('opd:id,name');

        // Access control based on user group
        if ($user->group === 'superadmin') {
            // Superadmin gets all users
        } elseif ($user->group === 'admin') {
            // Admin gets only users in the same opd
            $query->where('opd_id', $user->opd_id);
        } else {
            return response()->json(['message' => 'Unauthorized (user controller)'], 403);
        }

        // Apply OPD filtering
        if ($request->has('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        // Apply OPD sorting
        if ($request->has('sort') && $request->sort === 'opd') {
            $query->join('opds', 'users.opd_id', '=', 'opds.id')
                ->orderBy('opds.name')
                ->select('users.*');
        }

        return response()->json([
            'count' => $query->count(),
            'data' => $query->get()
        ]);
    }
}
