<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OpdController extends Controller
{
    public function index()
    {
        $opds = Opd::all(); // Or paginate: Opd::paginate(10);
        return response()->json($opds);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $opd = Opd::create($validatedData);

        return response()->json($opd, 201); // 201 Created
    }

    public function show(Opd $opd)
    {
        return response()->json($opd);
    }

    public function update(Request $request, Opd $opd)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $opd->update($validatedData);

        return response()->json($opd);
    }

    public function destroy(Opd $opd)
    {
        $opd->delete();

        return response()->json(null, 204); // 204 No Content
    }
}