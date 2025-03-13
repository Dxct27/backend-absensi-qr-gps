<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $adminUser = auth()->user(); // Get logged-in admin
        $opdId = $adminUser->opd_id; // Get OPD ID of the admin

        $date = $request->input('date');
        $filterType = $request->input('filter', 'daily'); // Default to daily

        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        $query = Attendance::whereHas('user', function ($q) use ($opdId) {
            $q->where('opd_id', $opdId);
        })->with('user:id,name,nip'); // Eager load user name and NIP

        switch ($filterType) {
            case 'daily':
                $query->whereDate('date', $date);
                break;

            case 'weekly':
                $startOfWeek = Carbon::parse($date)->startOfWeek();
                $endOfWeek = Carbon::parse($date)->endOfWeek();
                $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
                break;

            case 'monthly':
                $query->whereYear('date', Carbon::parse($date)->year)
                    ->whereMonth('date', Carbon::parse($date)->month);
                break;

            default:
                return response()->json(['error' => 'Invalid filter type'], 400);
        }

        $attendanceRecords = $query->get();

        return response()->json(['data' => $attendanceRecords]);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'opd_id' => 'required|exists:opds,id',
                'qrcode_value' => 'required|string', // QR Code is now mandatory
                'date' => [
                    'required',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) {
                        if (Carbon::parse($value)->lt(Carbon::today())) {
                            $fail('Attendance cannot be submitted for past dates.');
                        }
                    }
                ],
                'timestamp' => 'required|date_format:H:i:s',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Validate QR Code
        $qrCode = \App\Models\Qrcode::where('value', $validatedData['qrcode_value'])->first();
        if (!$qrCode) {
            return response()->json(['error' => 'Invalid QR Code'], 400);
        }

        // Prevent duplicate attendance
        $existingAttendance = Attendance::where('user_id', $validatedData['user_id'])
            ->where('qrcode_id', $qrCode->id)
            ->first();

        if ($existingAttendance) {
            return response()->json(['message' => 'Anda sudah melakukan absensi dengan QR ini sebelumnya!'], 400);
        }

        // Assign QR Code ID
        $validatedData['qrcode_id'] = $qrCode->id;
        unset($validatedData['qrcode_value']); // No need to store QR value, only its ID

        // Default status
        $status = 'hadir';

        // Check location
        if (!isset($validatedData['latitude']) || !isset($validatedData['longitude'])) {
            $status = 'lokasi tidak ditemukan';
        } else {
            $isWithinRadius = $this->isWithinRadius(
                $validatedData['latitude'],
                $validatedData['longitude'],
                $qrCode->latitude,
                $qrCode->longitude,
                $qrCode->radius
            );

            if (!$isWithinRadius) {
                $status = 'lokasi di luar radius';
            }
        }

        // Validate time range
        $scanTime = Carbon::createFromFormat('H:i:s', $validatedData['timestamp']);
        $startTime = Carbon::parse($qrCode->waktu_awal);
        $endTime = Carbon::parse($qrCode->waktu_akhir);

        if ($scanTime->lessThan($startTime)) {
            return response()->json(['error' => 'Attendance too early, not allowed yet.'], 400);
        }

        if ($scanTime->greaterThan($endTime)) {
            $status = 'terlambat';
        }

        // Assign final status
        $validatedData['status'] = $status;

        // Store attendance
        $attendance = Attendance::create($validatedData);

        return response()->json(['message' => 'Attendance recorded successfully', 'data' => $attendance], 201);
    }



    public function show(Attendance $attendance)
    {
        $attendance->load(['user', 'opd', 'qrcode']);
        return response()->json($attendance);
    }

    public function update(Request $request, Attendance $attendance)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'opd_id' => 'sometimes|exists:opds,id',
                'qrcode_id' => 'sometimes|exists:qrcodes,id',
                'date' => 'sometimes|date_format:Y-m-d',
                'timestamp' => 'sometimes|date_format:H:i:s',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'status' => 'sometimes|string',
                'notes' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        if ($request->hasFile('attachment')) {
            if ($attendance->attachment) {
                Storage::disk('public')->delete($attendance->attachment);
            }
            $path = $request->file('attachment')->store('attendance_attachments', 'public');
            $validatedData['attachment'] = $path;
        }

        $attendance->update($validatedData);
        return response()->json($attendance);
    }

    public function destroy(Attendance $attendance)
    {
        if ($attendance->attachment) {
            Storage::disk('public')->delete($attendance->attachment);
        }

        $attendance->delete();
        return response()->json(null, 204);
    }

    private function isWithinRadius($userLat, $userLng, $allowedLat, $allowedLng, $radius)
    {
        $earthRadius = 6371000; // Radius of Earth in meters

        $latDelta = deg2rad($userLat - $allowedLat);
        $lngDelta = deg2rad($userLng - $allowedLng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($allowedLat)) * cos(deg2rad($userLat)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $radius;
    }

    public function storeLeaveRequest(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'opd_id' => 'required|exists:opds,id',
                'date' => 'required|date_format:Y-m-d',
                'status' => 'required|in:sakit,izin,dinas luar,absent',
                'notes' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048', // 2MB max file
            ]);
        } catch (ValidationException $e) {
            Log::error('Leave request validation failed', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $validatedData['attachment'] = $request->file('attachment')->store('leave_attachments', 'public');
        }

        // Store leave request
        $leaveRequest = Attendance::create($validatedData);

        return response()->json([
            'message' => 'Leave request recorded successfully',
            'data' => $leaveRequest
        ], 201);
    }

}
