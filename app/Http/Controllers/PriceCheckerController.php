<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PriceCheckerController extends Controller
{
    public function index()
    {
        return view('price-checker');
    }

    public function lookup(Request $request)
    {
        $barcode = $request->input('barcode');

        if (empty($barcode)) {
            return response()->json([
                'success' => false,
                'message' => 'الرجاء إدخال الباركود',
            ]);
        }

        $product = Product::where('barcode', $barcode)
            ->where('status', true)
            ->with(['baseUnit.unit', 'productUnits.unit'])
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود',
            ]);
        }

        $baseUnit = $product->baseUnit;
        $basePrice = $baseUnit?->sell_price ?? 0;
        $baseUnitName = $baseUnit?->unit?->name ?? 'قطعة';

        $units = $product->productUnits()
            ->where('is_base_unit', false)
            ->with('unit')
            ->get()
            ->map(fn($u) => [
                'name' => $u->unit->name ?? '-',
                'price' => $u->sell_price,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $product->name,
                'price' => $basePrice,
                'unit_name' => $baseUnitName,
                'units' => $units,
            ],
        ]);
    }
}
