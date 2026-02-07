<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function generate(Request $request)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        return response()->json([
            'success' => true,
            'data' => $reportData,
        ]);
    }

    public function print(Request $request)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        return view('reports.print.inventory-print', compact('filters', 'reportData'));
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        $exportService = new ReportExportService();

        if ($format === 'excel') {
            return $exportService->exportExcel(
                $reportData['products'],
                'inventory_report',
                $this->getExcelColumns()
            );
        }

        return $exportService->exportPdf(
            'reports.print.inventory-print',
            compact('filters', 'reportData'),
            'inventory_report'
        );
    }

    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'stock_status' => 'nullable|in:all,low_stock,out_of_stock,expiring,expired',
            'expiry_days' => 'nullable|integer|min:1|max:365',
            'search' => 'nullable|string|max:100',
        ]);
    }

    protected function generateReportData(array $filters): array
    {
        $stockStatus = $filters['stock_status'] ?? 'all';
        $expiryDays = $filters['expiry_days'] ?? 30;
        $search = $filters['search'] ?? null;

        $query = Product::where('status', 'active')
            ->with(['inventoryBatches' => fn($q) => $q->where('quantity', '>', 0), 'baseUnit.unit']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->get();

        $productData = $products->map(function ($product) use ($expiryDays) {
            $totalStock = $product->inventoryBatches->sum('quantity');
            $totalValue = $product->inventoryBatches->sum(fn($b) => $b->quantity * $b->cost_price);
            $avgCost = $product->inventoryBatches->count() > 0
                ? $product->inventoryBatches->avg('cost_price')
                : 0;

            $nearestExpiry = $product->inventoryBatches
                ->whereNotNull('expiry_date')
                ->sortBy('expiry_date')
                ->first();

            $expiryDate = $nearestExpiry?->expiry_date;
            $daysToExpiry = $expiryDate ? now()->startOfDay()->diffInDays($expiryDate, false) : null;

            $status = 'ok';
            if ($totalStock <= 0) {
                $status = 'out_of_stock';
            } elseif ($totalStock <= 5) {
                $status = 'low_stock';
            }

            if ($daysToExpiry !== null) {
                if ($daysToExpiry < 0) {
                    $status = 'expired';
                } elseif ($daysToExpiry <= $expiryDays) {
                    $status = 'expiring';
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'unit' => $product->baseUnit?->unit?->name ?? '-',
                'stock' => round($totalStock, 2),
                'value' => round($totalValue, 2),
                'avg_cost' => round($avgCost, 2),
                'expiry_date' => $expiryDate?->format('Y-m-d'),
                'days_to_expiry' => $daysToExpiry,
                'status' => $status,
                'batch_count' => $product->inventoryBatches->count(),
            ];
        });

        if ($stockStatus !== 'all') {
            $productData = $productData->filter(fn($p) => $p['status'] === $stockStatus);
        }

        $allProducts = $productData->values();

        $summary = [
            'total_products' => $products->count(),
            'total_stock_value' => round($allProducts->sum('value'), 2),
            'low_stock_count' => $allProducts->where('status', 'low_stock')->count(),
            'out_of_stock_count' => $allProducts->where('status', 'out_of_stock')->count(),
            'expiring_count' => $allProducts->where('status', 'expiring')->count(),
            'expired_count' => $allProducts->where('status', 'expired')->count(),
            'ok_count' => $allProducts->where('status', 'ok')->count(),
        ];

        return [
            'summary' => $summary,
            'products' => $productData->values()->toArray(),
            'filters' => $filters,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    protected function getExcelColumns(): array
    {
        return [
            'name' => 'اسم المنتج',
            'barcode' => 'الباركود',
            'unit' => 'الوحدة',
            'stock' => 'الكمية',
            'value' => 'القيمة',
            'avg_cost' => 'متوسط التكلفة',
            'expiry_date' => 'تاريخ الانتهاء',
            'status' => 'الحالة',
        ];
    }
}
