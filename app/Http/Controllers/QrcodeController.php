<?php

namespace App\Http\Controllers;

use App\Models\Qrcode;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QrcodeController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type');

        $query = QRCode::query();

        if ($type) {
            $query->where('type', $type);
        }

        $qrcodes = $query->orderBy('updated_at', 'desc')->get();

        return response()->json($qrcodes);
    }



    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'opd_id' => 'required|exists:opds,id',
                'name' => 'required|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius' => 'nullable|numeric',
                'waktu_awal' => 'nullable|date_format:Y-m-d H:i:s',
                'waktu_akhir' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:waktu_awal',
                'type' => 'required|in:daily,special_event',
                'event_id' => 'nullable|required_if:type,special_event|exists:events,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $qrcode = new Qrcode($validatedData);

        $stringToHash = implode('|', [
            $qrcode->opd_id,
            $qrcode->name,
            $qrcode->latitude,
            $qrcode->longitude,
            $qrcode->radius,
            $qrcode->waktu_awal,
            $qrcode->waktu_akhir,
            $qrcode->type,
            $qrcode->event_id ?? '',
            now()->timestamp
        ]);

        $qrcode->value = hash('sha256', $stringToHash);
        $qrcode->save();

        return response()->json($qrcode, 201);
    }


    public function show(Qrcode $qrcode)
    {
        $qrcode->load('opd');
        return response()->json($qrcode);
    }

    public function update(Request $request, Qrcode $qrcode)
    {
        try {
            $validatedData = $request->validate([
                'opd_id' => 'required|exists:opds,id',
                'name' => 'required|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius' => 'nullable|numeric',
                'waktu_awal' => 'nullable|date_format:Y-m-d H:i:s',
                'waktu_akhir' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:waktu_awal',
                'type' => 'required|in:daily,special_event',
                'event_id' => 'nullable|required_if:type,special_event|exists:events,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $qrcode->update($validatedData);

        $stringToHash = implode('|', [
            $qrcode->opd_id,
            $qrcode->name,
            $qrcode->latitude,
            $qrcode->longitude,
            $qrcode->radius,
            $qrcode->waktu_awal,
            $qrcode->waktu_akhir,
            $qrcode->type,
            $qrcode->event_id ?? '',
            now()->timestamp
        ]);

        $qrcode->value = hash('sha256', $stringToHash);
        $qrcode->save();

        return response()->json($qrcode);
    }

    public function destroy(Qrcode $qrcode)
    {
        $qrcode->delete();
        return response()->json(['message' => 'QR Code deleted successfully'], 200);
    }
}
