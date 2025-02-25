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
        $qrcodes = Qrcode::all();
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
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $qrcode = new Qrcode($validatedData);

        $stringToHash = $qrcode->opd_id . $qrcode->name . now()->timestamp;
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
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $qrcode->update($validatedData);

        $stringToHash = $qrcode->opd_id . $qrcode->name . now()->timestamp;
        $qrcode->value = hash('sha256', $stringToHash);

        $qrcode->save();

        return response()->json($qrcode);
    }


    public function destroy(Qrcode $qrcode)
    {
        $qrcode->delete();

        return response()->json(null, 204);
    }
}