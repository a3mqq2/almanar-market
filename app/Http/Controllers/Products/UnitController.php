<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:units,name',
            'symbol' => 'nullable|string|max:50',
        ]);

        $unit = Unit::create([
            'name' => $validated['name'],
            'symbol' => $validated['symbol'] ?? $validated['name'],
            'is_default' => false,
        ]);

        return response()->json([
            'success' => true,
            'unit' => $unit,
            'message' => 'تم إضافة الوحدة بنجاح',
        ]);
    }

    public function index()
    {
        $units = Unit::all();

        return response()->json([
            'success' => true,
            'units' => $units,
        ]);
    }
}
