<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SuppliersReportController extends Controller
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

        return view('reports.print.suppliers-print', compact('filters', 'reportData'));
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        $exportService = new ReportExportService();

        if ($format == 'excel') {
            return $exportService->exportExcel(
                $reportData['suppliers'],
                'suppliers_report',
                $this->getExcelColumns()
            );
        }

        return $exportService->exportPdf(
            'reports.print.suppliers-print',
            compact('filters', 'reportData'),
            'suppliers_report'
        );
    }

    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'balance_status' => 'nullable|in:all,with_balance,no_balance',
            'search' => 'nullable|string|max:100',
        ]);
    }

    protected function generateReportData(array $filters): array
    {
        $cacheKey = 'suppliers_report_' . md5(json_encode($filters));

        if ($this->shouldCache($filters) && $cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $dateFrom = $filters['date_from'] ?? today()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? today()->format('Y-m-d');

        $query = Supplier::where('status', true);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['balance_status'])) {
            if ($filters['balance_status'] == 'with_balance') {
                $query->where('current_balance', '!=', 0);
            } elseif ($filters['balance_status'] == 'no_balance') {
                $query->where('current_balance', 0);
            }
        }

        $suppliers = $query->get();

        $purchasesData = Purchase::where('status', 'approved')
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->select(
                'supplier_id',
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('SUM(total) as total_purchases')
            )
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        $totalSuppliers = $suppliers->count();
        $totalBalance = $suppliers->sum('current_balance');
        $totalPurchases = $purchasesData->sum('total_purchases');

        $suppliersList = $suppliers->map(function ($supplier) use ($purchasesData) {
            $purchases = $purchasesData->get($supplier->id);
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'current_balance' => round($supplier->current_balance, 2),
                'purchase_count' => $purchases?->purchase_count ?? 0,
                'total_purchases' => round($purchases?->total_purchases ?? 0, 2),
            ];
        })->sortByDesc('total_purchases')->values();

        $topSuppliers = $suppliersList->take(20)->toArray();

        $reportData = [
            'summary' => [
                'total_suppliers' => $totalSuppliers,
                'total_balance' => round($totalBalance, 2),
                'total_purchases' => round($totalPurchases, 2),
                'with_balance_count' => $suppliers->where('current_balance', '!=', 0)->count(),
                'average_balance' => $totalSuppliers > 0 ? round($totalBalance / $totalSuppliers, 2) : 0,
            ],
            'suppliers' => $suppliersList->toArray(),
            'top_suppliers' => $topSuppliers,
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'filters' => $filters,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        if ($this->shouldCache($filters)) {
            Cache::put($cacheKey, $reportData, now()->addHours(24));
        }

        return $reportData;
    }

    protected function shouldCache(array $filters): bool
    {
        $today = today()->format('Y-m-d');
        return ($filters['date_to'] ?? $today) < $today;
    }

    protected function getExcelColumns(): array
    {
        return [
            'name' => 'اسم المورد',
            'phone' => 'رقم الهاتف',
            'current_balance' => 'الرصيد الحالي',
            'purchase_count' => 'عدد المشتريات',
            'total_purchases' => 'إجمالي المشتريات',
        ];
    }
}
