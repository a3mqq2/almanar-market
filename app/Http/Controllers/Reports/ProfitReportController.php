<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesReturn;
use App\Services\Reports\ReportExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProfitReportController extends Controller
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

        return view('reports.print.profit-print', compact('filters', 'reportData'));
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        $exportService = new ReportExportService();

        if ($format === 'excel') {
            return $exportService->exportExcel(
                $reportData['by_date'],
                'profit_report',
                $this->getExcelColumns()
            );
        }

        return $exportService->exportPdf(
            'reports.print.profit-print',
            compact('filters', 'reportData'),
            'profit_report'
        );
    }

    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'shift_id' => 'nullable|exists:shifts,id',
            'cashier_id' => 'nullable|exists:users,id',
            'cashbox_id' => 'nullable|exists:cashboxes,id',
        ]);
    }

    protected function generateReportData(array $filters): array
    {
        $cacheKey = 'profit_report_' . md5(json_encode($filters));

        if ($this->shouldCache($filters) && $cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $dateFrom = $filters['date_from'] ?? today()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? today()->format('Y-m-d');

        $salesQuery = Sale::where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        if (!empty($filters['shift_id'])) {
            $salesQuery->where('shift_id', $filters['shift_id']);
        }
        if (!empty($filters['cashier_id'])) {
            $salesQuery->where('cashier_id', $filters['cashier_id']);
        }

        $saleIds = $salesQuery->pluck('id');

        $revenue = SaleItem::whereIn('sale_id', $saleIds)->sum('total_price');
        $cost = SaleItem::whereIn('sale_id', $saleIds)
            ->selectRaw('SUM(cost_at_sale * base_quantity) as total_cost')
            ->value('total_cost') ?? 0;

        $expensesQuery = Expense::whereBetween('expense_date', [$dateFrom, $dateTo]);
        if (!empty($filters['shift_id'])) {
            $expensesQuery->where('shift_id', $filters['shift_id']);
        }
        if (!empty($filters['cashbox_id'])) {
            $expensesQuery->where('cashbox_id', $filters['cashbox_id']);
        }
        $expenses = $expensesQuery->sum('amount');

        $returnsQuery = SalesReturn::where('status', 'completed')
            ->whereBetween('return_date', [$dateFrom, $dateTo]);
        $returns = $returnsQuery->sum('total_amount');

        $grossProfit = $revenue - $cost;
        $netProfit = $grossProfit - $expenses - $returns;

        $byDate = $this->getProfitByDate($dateFrom, $dateTo, $filters);
        $byProduct = $this->getProfitByProduct($saleIds);
        $expensesByCategory = $this->getExpensesByCategory($dateFrom, $dateTo, $filters);

        $reportData = [
            'summary' => [
                'revenue' => round($revenue, 2),
                'cost' => round($cost, 2),
                'gross_profit' => round($grossProfit, 2),
                'gross_margin' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0,
                'expenses' => round($expenses, 2),
                'returns' => round($returns, 2),
                'net_profit' => round($netProfit, 2),
                'net_margin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0,
            ],
            'by_date' => $byDate,
            'by_product' => $byProduct,
            'expenses_by_category' => $expensesByCategory,
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

    protected function getProfitByDate(string $dateFrom, string $dateTo, array $filters): array
    {
        $salesData = Sale::where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->when(!empty($filters['shift_id']), fn($q) => $q->where('shift_id', $filters['shift_id']))
            ->when(!empty($filters['cashier_id']), fn($q) => $q->where('cashier_id', $filters['cashier_id']))
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->get()
            ->keyBy('date');

        $expensesData = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->when(!empty($filters['shift_id']), fn($q) => $q->where('shift_id', $filters['shift_id']))
            ->when(!empty($filters['cashbox_id']), fn($q) => $q->where('cashbox_id', $filters['cashbox_id']))
            ->select(
                DB::raw('DATE(expense_date) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy(DB::raw('DATE(expense_date)'))
            ->get()
            ->keyBy('date');

        $result = [];
        $current = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        while ($current <= $end) {
            $dateKey = $current->format('Y-m-d');
            $revenue = $salesData->get($dateKey)?->revenue ?? 0;
            $expense = $expensesData->get($dateKey)?->total ?? 0;

            $result[] = [
                'date' => $dateKey,
                'revenue' => round($revenue, 2),
                'expenses' => round($expense, 2),
                'profit' => round($revenue - $expense, 2),
            ];

            $current->addDay();
        }

        return array_reverse($result);
    }

    protected function getProfitByProduct($saleIds): array
    {
        return SaleItem::whereIn('sale_id', $saleIds)
            ->select(
                'product_id',
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('SUM(cost_at_sale * base_quantity) as cost'),
                DB::raw('SUM(base_quantity) as quantity')
            )
            ->groupBy('product_id')
            ->with('product:id,name,barcode')
            ->orderByRaw('SUM(total_price) - SUM(cost_at_sale * base_quantity) DESC')
            ->limit(20)
            ->get()
            ->map(fn($item) => [
                'name' => $item->product->name ?? '-',
                'barcode' => $item->product->barcode ?? '-',
                'quantity' => round($item->quantity, 2),
                'revenue' => round($item->revenue, 2),
                'cost' => round($item->cost, 2),
                'profit' => round($item->revenue - $item->cost, 2),
                'margin' => $item->revenue > 0 ? round((($item->revenue - $item->cost) / $item->revenue) * 100, 2) : 0,
            ])
            ->toArray();
    }

    protected function getExpensesByCategory(string $dateFrom, string $dateTo, array $filters): array
    {
        return Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->when(!empty($filters['shift_id']), fn($q) => $q->where('shift_id', $filters['shift_id']))
            ->when(!empty($filters['cashbox_id']), fn($q) => $q->where('cashbox_id', $filters['cashbox_id']))
            ->select(
                'category_id',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('category_id')
            ->with('category:id,name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($item) => [
                'category' => $item->category->name ?? 'بدون تصنيف',
                'total' => round($item->total, 2),
                'count' => $item->count,
            ])
            ->toArray();
    }

    protected function getExcelColumns(): array
    {
        return [
            'date' => 'التاريخ',
            'revenue' => 'الإيرادات',
            'expenses' => 'المصروفات',
            'profit' => 'الربح',
        ];
    }
}
