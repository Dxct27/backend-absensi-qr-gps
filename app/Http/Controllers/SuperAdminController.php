<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\QRCode;

class SuperAdminController extends Controller
{
    public function dashboardSummary()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_attendance' => Attendance::count(),
            'total_qrcodes' => QRCode::count(),
        ]);
    }

    public function getUsers(Request $request)
    {
        $query = User::with('opd');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhereHas('opd', function ($opdQuery) use ($search) {
                        $opdQuery->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($request->filled('opd')) {
            $query->where('opd_id', $request->input('opd'));
        }

        $perPage = $request->input('limit', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    public function updateUserRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->group = $request->group;
        $user->save();

        return response()->json(['message' => 'User role updated successfully']);
    }

    public function deleteUser($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function updateUserOpd(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->opd_id = $request->opd_id;
        $user->save();

        return response()->json(['message' => 'User OPD updated successfully']);
    }

    public function getAttendance(Request $request)
    {
        $query = Attendance::with(['user', 'opd', 'qrcode', 'specialEvent']);
        $perPage = $request->input('limit', 15);
        return response()->json($query->paginate($perPage));
    }
    
    public function deleteAttendance($id)
    {
        Attendance::findOrFail($id)->delete();
        return response()->json(['message' => 'Attendance deleted successfully']);
    }

}
