<?php

namespace App\Http\Controllers;

use App\Models\SpecialEventCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SpecialEventCategoryController extends Controller
{
    public function index()
    {
        $categories = SpecialEventCategory::all();
        return response()->json($categories);
    }
    
    public function show($id)
    {
        $category = SpecialEventCategory::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|unique:special_event_categories,name|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $category = SpecialEventCategory::create($validatedData);

        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = SpecialEventCategory::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $category->update($validatedData);

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = SpecialEventCategory::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
