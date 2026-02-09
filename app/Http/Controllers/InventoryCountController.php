<?php

namespace App\Http\Controllers;

use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Services\InventoryCountService;
use Illuminate\Http\Request;

class InventoryCountController extends Controller
{
    protected InventoryCountService $countService;

    public function __construct(InventoryCountService $countService)
    {
        $this->countService = $countService;
    }

    public function index(Request $request)
    {
        $query = InventoryCount::with(['countedByUser', 'approvedByUser'])
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('reference_number', 'like', "%{$request->search}%");
        }

        $counts = $query->paginate(20);

        $stats = [
            'total' => InventoryCount::count(),
            'in_progress' => InventoryCount::inProgress()->count(),
            'pending_approval' => InventoryCount::completed()->count(),
            'approved' => InventoryCount::approved()->count(),
        ];

        return view('inventory-counts.index', compact('counts', 'stats'));
    }

    public function create()
    {
        $activeCount = InventoryCount::active()->first();

        if ($activeCount) {
            return redirect()->route('inventory-counts.show', $activeCount)
                ->with('warning', 'يوجد جرد قيد التنفيذ. يجب إكماله أو إلغاؤه أولاً');
        }

        return view('inventory-counts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'count_type' => 'required|in:full,partial',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $count = $this->countService->createCount(
                $validated['count_type'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الجرد بنجاح',
                'redirect' => route('inventory-counts.show', $count),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(InventoryCount $inventoryCount)
    {
        $inventoryCount->load(['items.product', 'items.unit', 'countedByUser', 'approvedByUser']);

        if ($inventoryCount->status == 'in_progress') {
            return redirect()->route('inventory-counts.count', $inventoryCount);
        }

        return view('inventory-counts.show', compact('inventoryCount'));
    }

    public function start(InventoryCount $inventoryCount)
    {
        try {
            $this->countService->startCount($inventoryCount);

            return response()->json([
                'success' => true,
                'message' => 'تم بدء الجرد بنجاح',
                'redirect' => route('inventory-counts.count', $inventoryCount),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function count(InventoryCount $inventoryCount)
    {
        if ($inventoryCount->status != 'in_progress') {
            return redirect()->route('inventory-counts.show', $inventoryCount);
        }

        $inventoryCount->load(['items.product.baseUnit.unit']);

        return view('inventory-counts.count', compact('inventoryCount'));
    }

    public function saveCount(Request $request, InventoryCountItem $item)
    {
        $validated = $request->validate([
            'counted_qty' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->countService->countItem(
                $item,
                $validated['counted_qty'],
                $validated['notes'] ?? null
            );

            $count = $item->inventoryCount->fresh();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الجرد',
                'item' => [
                    'id' => $item->id,
                    'counted_qty' => $item->counted_qty,
                    'difference' => $item->difference,
                    'variance_value' => $item->variance_value,
                    'variance_status' => $item->variance_status,
                ],
                'progress' => [
                    'counted_items' => $count->counted_items,
                    'total_items' => $count->total_items,
                    'percentage' => $count->progress_percentage,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function searchItems(Request $request, InventoryCount $inventoryCount)
    {
        $search = $request->get('q', '');

        $items = $inventoryCount->items()
            ->with('product')
            ->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'barcode' => $item->product->barcode,
                'system_qty' => $item->system_qty,
                'counted_qty' => $item->counted_qty,
                'variance_status' => $item->variance_status,
            ]);

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    public function getItemByBarcode(Request $request, InventoryCount $inventoryCount)
    {
        $barcode = $request->get('barcode');

        $item = $inventoryCount->items()
            ->with('product.baseUnit.unit')
            ->whereHas('product', fn($q) => $q->where('barcode', $barcode))
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود في هذا الجرد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'barcode' => $item->product->barcode,
                'unit_name' => $item->product->baseUnit?->unit?->name ?? 'وحدة',
                'system_qty' => $item->system_qty,
                'system_cost' => $item->system_cost,
                'counted_qty' => $item->counted_qty,
                'difference' => $item->difference,
                'variance_status' => $item->variance_status,
                'notes' => $item->notes,
            ],
        ]);
    }

    public function complete(InventoryCount $inventoryCount)
    {
        try {
            $this->countService->completeCount($inventoryCount);

            return response()->json([
                'success' => true,
                'message' => 'تم إكمال الجرد بنجاح',
                'redirect' => route('inventory-counts.review', $inventoryCount),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function review(InventoryCount $inventoryCount)
    {
        if ($inventoryCount->status != 'completed') {
            return redirect()->route('inventory-counts.show', $inventoryCount);
        }

        $inventoryCount->load(['items.product']);
        $summary = $this->countService->getVarianceSummary($inventoryCount);

        $varianceItems = $inventoryCount->items()
            ->with('product')
            ->where('difference', '!=', 0)
            ->orderBy('variance_value')
            ->get();

        return view('inventory-counts.review', compact('inventoryCount', 'summary', 'varianceItems'));
    }

    public function approve(InventoryCount $inventoryCount)
    {
        try {
            $this->countService->approveCount($inventoryCount);

            return response()->json([
                'success' => true,
                'message' => 'تم اعتماد الجرد وتحديث المخزون',
                'redirect' => route('inventory-counts.show', $inventoryCount),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, InventoryCount $inventoryCount)
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);

        try {
            $this->countService->cancelCount($inventoryCount, $validated['cancel_reason']);

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الجرد',
                'redirect' => route('inventory-counts.index'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function export(InventoryCount $inventoryCount)
    {
        $inventoryCount->load(['items.product', 'countedByUser', 'approvedByUser']);

        return view('inventory-counts.print', compact('inventoryCount'));
    }
}
