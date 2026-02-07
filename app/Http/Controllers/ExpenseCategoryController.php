<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::withCount('expenses')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
        ]);

        $category = ExpenseCategory::create([
            'name' => $request->name,
            'status' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التصنيف بنجاح',
            'category' => $category,
        ]);
    }

    public function update(Request $request, ExpenseCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $category->id,
            'status' => 'boolean',
        ]);

        $category->update([
            'name' => $request->name,
            'status' => $request->boolean('status', $category->status),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التصنيف بنجاح',
            'category' => $category,
        ]);
    }

    public function destroy(ExpenseCategory $category)
    {
        if ($category->expenses()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف التصنيف لوجود مصروفات مرتبطة به',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التصنيف بنجاح',
        ]);
    }
}
