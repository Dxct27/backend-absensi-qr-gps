<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $attendances = Attendance::with(['user', 'opd', 'qrcode'])->paginate(10); // Example with pagination and eager loading

        return response()->json($attendances);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'opd_id' => 'required|exists:opds,id',
                'qrcode_id' => 'required|exists:qrcodes,id', 
                'date' => 'required|date_format:Y-m-d',
                'timestamp' => 'required|date_format:H:i:s',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'status' => 'required|string', 
                'notes' => 'nullable|string',
                'attachments' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048', 
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        if ($request->hasFile('attachments')) {
            $path = $request->file('attachments')->store('attendance_attachments', 'public'); // Store in 'storage/app/public/attendance_attachments'
            $validatedData['attachments'] = $path; // Store the path
        }

        $attendance = Attendance::create($validatedData);

        return response()->json($attendance, 201);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load(['user', 'opd', 'qrcode']); // Eager load relationships for the show method
        return response()->json($attendance);
    }

    public function update(Request $request, Attendance $attendance)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'sometimes|exists:users,id', // 'sometimes' allows the field to be optional
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
}