<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExpensesReportController extends Controller
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

        return view('reports.print.expenses-print', compact('filters', 'reportData'));
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        $exportService = new ReportExportService();

        if ($format === 'excel') {
            return $exportService->exportExcel(
                $reportData['expenses'],
                'expenses_report',
                $this->getExcelColumns()
            );
        }

        return $exportService->exportPdf(
            'reports.print.expenses-print',
            compact('filters', 'reportData'),
            'expenses_report'
        );
    }

    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'shift_id' => 'nullable|exists:shifts,id',
            'cashbox_id' => 'nullable|exists:cashboxes,id',
            'category_id' => 'nullable|exists:expense_categories,id',
        ]);
    }

    protected function generateReportData(array $filters): array
    {
        $cacheKey = 'expenses_report_' . md5(json_encode($filters));

        if ($this->shouldCache($filters) && $cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $dateFrom = $filters['date_from'] ?? today()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? today()->format('Y-m-d');

        $query = Expense::whereBetween('expense_date', [$dateFrom, $dateTo]);

        if (!empty($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }
        if (!empty($filters['cashbox_id'])) {
            $query->where('cashbox_id', $filters['cashbox_id']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $totalAmount = $query->sum('amount');
        $totalCount = $query->count();

        $byCategory = $this->getByCategory($query->clone());
        $byDate = $this->getByDate($query->clone());
        $byCashbox = $this->getByCashbox($query->clone());
        $expenses = $this->getExpensesList($query->clone());

        $reportData = [
            'summary' => [
                'total_amount' => round($totalAmount, 2),
                'total_count' => $totalCount,
                'average' => $totalCount > 0 ? round($totalAmount / $totalCount, 2) : 0,
            ],
            'by_category' => $byCategory,
            'by_date' => $byDate,
            'by_cashbox' => $byCashbox,
            'expenses' => $expenses,
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

    protected function getByCategory($query): array
    {
        $totalAmount = $query->sum('amount');

        return $query->select(
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
                'percentage' => $totalAmount > 0 ? round(($item->total / $totalAmount) * 100, 2) : 0,
            ])
            ->toArray();
    }

    protected function getByDate($query): array
    {
        return $query->select(
            DB::raw('DATE(expense_date) as date'),
            DB::raw('SUM(amount) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy(DB::raw('DATE(expense_date)'))
            ->orderBy('date', 'desc')
            ->limit(31)
            ->get()
            ->map(fn($row) => [
                'date' => $row->date,
                'total' => round($row->total, 2),
                'count' => $row->count,
            ])
            ->toArray();
    }

    protected function getByCashbox($query): array
    {
        return $query->select(
            'cashbox_id',
            DB::raw('SUM(amount) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('cashbox_id')
            ->with('cashbox:id,name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($item) => [
                'cashbox' => $item->cashbox->name ?? '-',
                'total' => round($item->total, 2),
                'count' => $item->count,
            ])
            ->toArray();
    }

    protected function getExpensesList($query): array
    {
        return $query->with(['category:id,name', 'cashbox:id,name', 'creator:id,name'])
            ->orderByDesc('expense_date')
            ->limit(100)
            ->get()
            ->map(fn($expense) => [
                'id' => $expense->id,
                'reference_number' => $expense->reference_number,
                'title' => $expense->title,
                'category' => $expense->category->name ?? '-',
                'amount' => round($expense->amount, 2),
                'cashbox' => $expense->cashbox->name ?? '-',
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'creator' => $expense->creator->name ?? '-',
            ])
            ->toArray();
    }

    protected function getExcelColumns(): array
    {
        return [
            'reference_number' => 'الرقم المرجعي',
            'title' => 'العنوان',
            'category' => 'التصنيف',
            'amount' => 'المبلغ',
            'cashbox' => 'الخزينة',
            'expense_date' => 'التاريخ',
            'creator' => 'بواسطة',
        ];
    }
}
