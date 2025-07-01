<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use \App\Models\User;
use \App\Models\Qrcode;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {

        $adminUser = auth()->user();
        $opdId = $adminUser->opd_id;

        $userId = $request->input('user_id');
        $date = $request->input('date');
        $filterType = $request->input('filter', 'daily');
        $qrType = $request->input('qr_type');

        \Log::info('filterType', [$filterType]);
        \Log::info('date', [$date]);

        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        if ($request->input('include_all', false)) {
            // Get all attendance records regardless of qr_type or status
            $query = Attendance::where(function ($q) use ($opdId, $userId) {
                $q->whereHas('user', function ($q) use ($opdId, $userId) {
                    $q->where('opd_id', $opdId);
                    if ($userId) {
                        $q->where('id', $userId);
                    }
                });
            })
                ->with([
                    'user:id,name,nip',
                    'qrcode:id,type,event_id',
                ]);
        } else {
            // Get daily attendance including leave
            $query = Attendance::where(function ($q) use ($opdId, $userId) {
                $q->whereHas('user', function ($q) use ($opdId, $userId) {
                    $q->where('opd_id', $opdId);
                    if ($userId) {
                        $q->where('id', $userId);
                    }
                });
            })
                ->with([
                    'user:id,name,nip',
                    'qrcode:id,type,event_id',
                ])
                ->where(function ($q) {
                    $q->whereHas('qrcode', function ($q) {
                        $q->where('type', 'daily'); // Include daily attendance
                    })
                        ->orWhereIn('status', ['izin', 'sakit', 'dinas luar']); // Include leave records
                });
        }

        if ($qrType) {
            $query->where(function ($q) use ($qrType) {
                $q->whereHas('qrcode', function ($q) use ($qrType) {
                    $q->where('type', $qrType);
                });
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));

            // Exclude special_event and leave records
            $query->whereHas('qrcode', function ($q) {
                $q->where('type', '!=', 'special_event');
            });
        }

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

            case 'custom':
                $dates = explode(',', $date);
                if (count($dates) == 2) {
                    $query->whereBetween('date', [$dates[0], $dates[1]]);
                } else {
                    return response()->json(['error' => 'Invalid custom date range'], 400);
                }
                
                break;

            // prev
            // $startDate = $request->input('start_date');
            // $endDate = $request->input('end_date');

            // if (!$startDate || !$endDate) {
            //     return response()->json(['error' => 'Start date and end date are required for custom filter'], 400);
            // }

            // $query->whereBetween('date', [$startDate, $endDate]);
            // break;

            default:
                return response()->json(['error' => 'Invalid filter type'], 400);
        }

        $attendanceRecords = $query->get();

        return response()->json([
            'count' => $attendanceRecords->count(),
            'hadir' => $attendanceRecords->where('status', 'hadir')->count(),
            'izin' => $attendanceRecords->where('status', 'izin')->count(),
            'sakit' => $attendanceRecords->where('status', 'sakit')->count(),
            'data' => $attendanceRecords
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'opd_id' => 'required|exists:opds,id',
                'qrcode_value' => 'required|string',
                'date' => 'required|date_format:Y-m-d',
                'timestamp' => 'required|date_format:H:i:s',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed', ['error' => $e->errors()]);
            return response()->json(['error' => $e->errors()], 422);
        }

        $userOpdId = User::where('id', $validatedData['user_id'])->value('opd_id');
        $qrCode = Qrcode::where('value', $validatedData['qrcode_value'])->first();

        if (!$qrCode) {
            return response()->json(['error' => 'QR Code tidak ditemukan atau tidak valid.'], 400);
        }

        if ($userOpdId !== $qrCode->opd_id && $qrCode->type == "daily") {
            return response()->json(['error' => 'Anda tidak dapat melakukan absensi menggunakan QR Code dari OPD lain.'], 400);
        }

        $leaveExists = Attendance::where('user_id', $validatedData['user_id'])
            ->whereDate('date', $validatedData['date'])
            ->whereIn('status', ['izin', 'sakit'])
            ->exists();

        if ($leaveExists) {
            return response()->json(['error' => 'Anda tidak dapat absen karena sedang dalam izin atau sakit.'], 400);
        }

        $dinasLuarExists = Attendance::where('user_id', $validatedData['user_id'])
            ->whereDate('date', $validatedData['date'])
            ->where('status', 'dinas luar')
            ->exists();

        if ($dinasLuarExists && $qrCode->type === 'daily') {
            return response()->json(['error' => 'Anda tidak dapat absen harian karena sedang dinas luar.'], 400);
        }

        // Prevent attendance with the same QR code on the same date :: case one qr for one month
        $existingAttendance = Attendance::where('user_id', $validatedData['user_id'])
            ->where('qrcode_id', $qrCode->id)
            ->whereDate('date', $validatedData['date'])
            ->exists();

        if ($existingAttendance) {
            return response()->json(['error' => 'Anda sudah melakukan absensi dengan QR ini pada hari ini!'], 400);
        }

        // // Currently in da sizm
        // // Prevent attendance same QR code but still can for one day :: Future dev, add special event
        // $existingAttendance = Attendance::where('user_id', $validatedData['user_id'])
        //     ->where('qrcode_id', $qrCode->id)
        //     ->first();

        // if ($existingAttendance) {
        //     return response()->json(['error' => 'Anda sudah melakukan absensi dengan QR ini sebelumnya!'], 400);
        // }

        // Prevent attendance in same day
        // $existingAttendance = Attendance::where('user_id', $validatedData['user_id'])
        //     ->whereDate('date', $validatedData['date'])
        //     ->exists();

        // if ($existingAttendance) {
        //     return response()->json(['error' => 'Anda sudah melakukan absensi pada tanggal ini!'], 400);
        // }

        $validatedData['qrcode_id'] = $qrCode->id;
        unset($validatedData['qrcode_value']);

        if ($qrCode->latitude && $qrCode->longitude && $qrCode->radius) {
            if (!isset($validatedData['latitude']) || !isset($validatedData['longitude'])) {
                return response()->json(['error' => 'Lokasi tidak ditemukan. Pastikan GPS aktif dan coba lagi.'], 400);
            }

            $isWithinRadius = $this->isWithinRadius(
                $validatedData['latitude'],
                $validatedData['longitude'],
                $qrCode->latitude,
                $qrCode->longitude,
                $qrCode->radius
            );

            if (!$isWithinRadius) {
                return response()->json(['error' => 'Anda berada di luar lokasi valid absen.'], 400);
            }
        }

        // // Daily scanTime validation QR --NOTE:: scanTime can be injected from date, delete Y-m-d and date to prevent it
        // $scanTime = Carbon::createFromFormat('Y-m-d H:i:s', $validatedData['date']  . $validatedData['timestamp']);
        // $startTime = Carbon::parse($qrCode->waktu_awal);
        // $endTime = Carbon::parse($qrCode->waktu_akhir);

        // if ($scanTime->lessThan($startTime)) {
        //     return response()->json(['error' => 'Absen masih belum dibuka.'], 400);
        // }

        // if ($scanTime->greaterThan($endTime)) {
        //     return response()->json(['message' => 'Anda terlambat. Absensi tidak dapat disimpan.'], 400);
        // }

        // One month scanTime validation QR
        $scanDate = Carbon::parse($validatedData['date']);
        $scanTime = Carbon::createFromFormat('Y-m-d H:i:s', $validatedData['date'] . $validatedData['timestamp']);

        $startDateTime = Carbon::parse($qrCode->waktu_awal);
        $endDateTime = Carbon::parse($qrCode->waktu_akhir);

        $startTime = $startDateTime->copy()->setDate($scanDate->year, $scanDate->month, $scanDate->day);
        $endTime = $endDateTime->copy()->setDate($scanDate->year, $scanDate->month, $scanDate->day);

        if ($scanDate->lessThan($startDateTime->startOfDay()) || $scanDate->greaterThan($endDateTime->endOfDay())) {
            return response()->json(['error' => 'QR Code tidak berlaku pada tanggal ini.'], 400);
        }

        if ($scanTime->lessThan($startTime)) {
            return response()->json(['error' => 'Absen gagal, absen belum dibuka!'], 400);
        }
        if ($scanTime->greaterThan($endTime)) {
            return response()->json(['error' => 'Absen gagal, anda terlambat absen!'], 400);
        }

        $validatedData['status'] = 'hadir';
        $attendance = Attendance::create($validatedData);

        return response()->json(['message' => 'Absensi berhasil dicatat.', 'data' => $attendance], 201);
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

    public function storeLeaveRequest(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'opd_id' => 'required|exists:opds,id',
                'date' => [
                    'required',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) {
                        if (Carbon::parse($value)->isBefore(Carbon::today())) {
                            $fail('Tanggal izin tidak boleh sebelum hari ini.');
                        }
                    },
                ],
                'status' => 'required|in:sakit,izin,dinas luar,absent',
                'notes' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);
            $hasAttendance = Attendance::where('user_id', $validatedData['user_id'])
                ->whereDate('date', $validatedData['date'])
                ->where('status', 'hadir')
                ->exists();

            if ($hasAttendance && in_array($validatedData['status'], ['izin', 'sakit'])) {
                return response()->json(['error' => 'Tidak dapat mengajukan izin karena Anda sudah absen.'], 400);
            }

            $existingLeave = Attendance::where('user_id', $validatedData['user_id'])
                ->whereDate('date', $validatedData['date'])
                ->whereIn('status', ['izin', 'sakit', 'dinas luar', 'absent'])
                ->exists();

            if ($existingLeave) {
                return response()->json(['error' => 'Anda sudah memiliki pengajuan izin pada tanggal ini.'], 400);
            }

            if ($request->hasFile('attachment')) {
                $validatedData['attachment'] = $request->file('attachment')->store('leave_attachments', 'public');
            }

            $leaveRequest = Attendance::create($validatedData);

            return response()->json([
                'message' => 'Izin berhasil disimpan',
                'data' => $leaveRequest
            ], 201);

        } catch (ValidationException $e) {
            Log::error('Leave request validation failed', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error storing leave request', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Terjadi kesalahan saat menyimpan izin.'], 500);
        }
    }

    private function isWithinRadius($userLat, $userLng, $allowedLat, $allowedLng, $radius)
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($userLat - $allowedLat);
        $lngDelta = deg2rad($userLng - $allowedLng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($allowedLat)) * cos(deg2rad($userLat)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $radius;
    }

    public function getAttendanceByQR(Request $request, $qrId)
    {
        $limit = $request->query('limit', null);

        $attendanceRecords = Attendance::where('qrcode_id', $qrId)
            ->with(
                'user:id,name,nip,opd_id',
                'user.opd:id,name'
            )
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $attendanceRecords = $attendanceRecords->take($limit);
        }

        $attendanceRecords = $attendanceRecords->get();

        if ($attendanceRecords->isEmpty()) {
            return response()->json(['message' => 'No attendance records found for this QR code.'], 404);
        }

        return response()->json($attendanceRecords);
    }

    public function userSpecialEventHistory(Request $request)
    {
        $user = auth()->user();

        $attendances = Attendance::with([
            'qrcode.specialEvent.category'
        ])
            ->where('user_id', $user->id)
            ->whereHas('qrcode', function ($q) {
                $q->where('type', 'special_event');
            })
            ->orderByDesc('date')
            ->get();

        // Optionally, map to only unique events if you want event history, not attendance history
        // $events = $attendances->pluck('qrcode.specialEvent')->unique('id')->values();

        return response()->json($attendances);
    }

}
