<?php

namespace App\Http\Controllers;

use App\Models\SpecialEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SpecialEventController extends Controller
{
    public function index()
    {
        $specialEvents = SpecialEvent::with('qrcodes')->get();
        return response()->json($specialEvents);
    }

    public function show($id)
    {
        $specialEvent = SpecialEvent::with('qrcodes')->find($id);

        if (!$specialEvent) {
            return response()->json(['message' => 'Special event not found'], 404);
        }

        return response()->json($specialEvent);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|unique:special_events,name|max:255',
                'date' => 'required|date',
                'opd_id' => 'required|exists:opds,id',
                'special_event_category_id' => 'required|exists:special_event_categories,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $specialEvent = SpecialEvent::create($validatedData);

        return response()->json($specialEvent, 201);
    }

    public function update(Request $request, $id)
    {
        $specialEvent = SpecialEvent::find($id);

        if (!$specialEvent) {
            return response()->json(['message' => 'Special event not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'date' => 'sometimes|required|date',
                'opd_id' => 'sometimes|required|exists:opds,id',
                'special_event_category_id' => 'sometimes|required|exists:special_event_categories,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $specialEvent->update($validatedData);

        return response()->json($specialEvent);
    }
    public function destroy($id)
    {
        $specialEvent = SpecialEvent::find($id);

        if (!$specialEvent) {
            return response()->json(['message' => 'Special event not found'], 404);
        }

        $specialEvent->delete();

        return response()->json(['message' => 'Special event deleted successfully']);
    }
}
