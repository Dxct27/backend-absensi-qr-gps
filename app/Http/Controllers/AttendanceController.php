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
        $attendances = Attendance::with(['user', 'opd', 'qrcode'])->paginate(10);
        return response()->json($attendances);
    }

    public function store(Request $request)
    {
        // Log::info('Attendance API Request:', [
        //     'user_id' => $request->user_id,
        //     'opd_id' => $request->opd_id,
        //     'qrcode_value' => $request->qrcode_value,
        //     'date' => $request->date,
        //     'timestamp' => $request->timestamp,
        //     'latitude' => $request->latitude,
        //     'longitude' => $request->longitude,
        // ]);

        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'opd_id' => 'required|exists:opds,id',
                'qrcode_value' => 'required|string',
                'date' => 'required|date_format:Y-m-d',
                'timestamp' => 'required|date_format:H:i:s',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'notes' => 'nullable|string',
                'attachments' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Check if the user has already checked in today
        $existingAttendance = Attendance::where('user_id', $validatedData['user_id'])
            ->where('date', $validatedData['date'])
            ->first();

        if ($existingAttendance) {
            Log::warning('User already checked in', [
                'user_id' => $validatedData['user_id'],
                'date' => $validatedData['date']
            ]);

            return response()->json([
                'message' => 'Anda sudah absen sebelumnya!'
            ], 400);
        }

        // Find the QR code by its hashed value
        $qrCode = \App\Models\Qrcode::where('value', $validatedData['qrcode_value'])->first();

        if (!$qrCode) {
            Log::warning('Invalid QR Code scan attempt', ['qrcode_value' => $validatedData['qrcode_value']]);
            return response()->json(['error' => 'Invalid QR Code'], 400);
        }

        Log::info('QR Code found', ['qrcode_id' => $qrCode->id, 'qrcode_value' => $qrCode->value]);

        // Replace qrcode_value with qrcode_id
        $validatedData['qrcode_id'] = $qrCode->id;
        unset($validatedData['qrcode_value']);

        // Default status
        $status = 'hadir';

        // Location validation
        if (!$request->has('latitude') || !$request->has('longitude')) {
            Log::warning('User location not found', ['user_id' => $validatedData['user_id']]);
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
                Log::warning('User outside valid location radius', [
                    'user_id' => $validatedData['user_id'],
                    'user_lat' => $validatedData['latitude'],
                    'user_lng' => $validatedData['longitude'],
                    'allowed_lat' => $qrCode->latitude,
                    'allowed_lng' => $qrCode->longitude,
                    'radius' => $qrCode->radius
                ]);
                $status = 'lokasi di luar radius';
            }
        }

        // Time validation
        $scanTime = Carbon::createFromFormat('H:i:s', $validatedData['timestamp']);
        $startTime = Carbon::parse($qrCode->waktu_awal);
        $endTime = Carbon::parse($qrCode->waktu_akhir);

        if ($scanTime->lessThan($startTime)) {
            Log::warning('User attempted early check-in', [
                'user_id' => $validatedData['user_id'],
                'scan_time' => $scanTime->toTimeString(),
                'allowed_start' => $startTime->toTimeString()
            ]);
            return response()->json(['error' => 'Attendance too early, not allowed yet.'], 400);
        }

        if ($scanTime->greaterThan($endTime)) {
            Log::info('User is late for attendance', [
                'user_id' => $validatedData['user_id'],
                'scan_time' => $scanTime->toTimeString(),
                'allowed_end' => $endTime->toTimeString()
            ]);
            $status = 'terlambat';
        }

        // Assign final status
        $validatedData['status'] = $status;

        // Save attendance record
        $attendance = Attendance::create($validatedData);
        Log::info('Attendance recorded', ['attendance_id' => $attendance->id, 'status' => $status]);

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
                'attachments' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        if ($request->hasFile('attachments')) {
            if ($attendance->attachments) {
                Storage::disk('public')->delete($attendance->attachments);
            }
            $path = $request->file('attachments')->store('attendance_attachments', 'public');
            $validatedData['attachments'] = $path;
        }

        $attendance->update($validatedData);
        return response()->json($attendance);
    }

    public function destroy(Attendance $attendance)
    {
        if ($attendance->attachments) {
            Storage::disk('public')->delete($attendance->attachments);
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
}
