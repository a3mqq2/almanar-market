<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShiftsReportController extends Controller
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

        return view('reports.print.shifts-print', compact('filters', 'reportData'));
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        $exportService = new ReportExportService();

        if ($format == 'excel') {
            return $exportService->exportExcel(
                $reportData['shifts'],
                'shifts_report',
                $this->getExcelColumns()
            );
        }

        return $exportService->exportPdf(
            'reports.print.shifts-print',
            compact('filters', 'reportData'),
            'shifts_report'
        );
    }

    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'cashier_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:open,closed',
        ]);
    }

    protected function generateReportData(array $filters): array
    {
        $cacheKey = 'shifts_report_' . md5(json_encode($filters));

        if ($this->shouldCache($filters) && $cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $dateFrom = $filters['date_from'] ?? today()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? today()->format('Y-m-d');

        $query = Shift::whereDate('opened_at', '>=', $dateFrom)
            ->whereDate('opened_at', '<=', $dateTo);

        if (!empty($filters['cashier_id'])) {
            $query->where('user_id', $filters['cashier_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $totalShifts = $query->count();
        $totalSales = $query->sum(DB::raw('total_cash_sales + total_card_sales + total_other_sales'));
        $totalExpenses = $query->sum('total_expenses');
        $totalRefunds = $query->sum('total_refunds');

        $shifts = $this->getShiftsList($query->clone());
        $byCashier = $this->getByCashier($query->clone());
        $byDate = $this->getByDate($dateFrom, $dateTo, $filters);

        $reportData = [
            'summary' => [
                'total_shifts' => $totalShifts,
                'total_sales' => round($totalSales, 2),
                'total_expenses' => round($totalExpenses, 2),
                'total_refunds' => round($totalRefunds, 2),
                'average_sales' => $totalShifts > 0 ? round($totalSales / $totalShifts, 2) : 0,
            ],
            'shifts' => $shifts,
            'by_cashier' => $byCashier,
            'by_date' => $byDate,
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

    protected function getShiftsList($query): array
    {
        return $query->with('user:id,name')
            ->orderByDesc('opened_at')
            ->limit(100)
            ->get()
            ->map(fn($shift) => [
                'id' => $shift->id,
                'cashier' => $shift->user->name ?? '-',
                'opened_at' => $shift->opened_at->format('Y-m-d H:i'),
                'closed_at' => $shift->closed_at?->format('Y-m-d H:i') ?? '-',
                'status' => $shift->status,
                'total_sales' => round($shift->total_cash_sales + $shift->total_card_sales + $shift->total_other_sales, 2),
                'total_cash' => round($shift->total_cash_sales, 2),
                'total_card' => round($shift->total_card_sales, 2),
                'total_expenses' => round($shift->total_expenses, 2),
                'total_refunds' => round($shift->total_refunds, 2),
                'sales_count' => $shift->sales_count,
                'approved' => $shift->approved,
            ])
            ->toArray();
    }

    protected function getByCashier($query): array
    {
        return $query->select(
            'user_id',
            DB::raw('COUNT(*) as shift_count'),
            DB::raw('SUM(total_cash_sales + total_card_sales + total_other_sales) as total_sales'),
            DB::raw('SUM(total_expenses) as total_expenses'),
            DB::raw('SUM(sales_count) as total_invoices')
        )
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn($row) => [
                'cashier' => $row->user->name ?? '-',
                'shift_count' => $row->shift_count,
                'total_sales' => round($row->total_sales, 2),
                'total_expenses' => round($row->total_expenses, 2),
                'total_invoices' => $row->total_invoices,
                'average_per_shift' => $row->shift_count > 0 ? round($row->total_sales / $row->shift_count, 2) : 0,
            ])
            ->toArray();
    }

    protected function getByDate(string $dateFrom, string $dateTo, array $filters): array
    {
        return Shift::whereDate('opened_at', '>=', $dateFrom)
            ->whereDate('opened_at', '<=', $dateTo)
            ->when(!empty($filters['cashier_id']), fn($q) => $q->where('user_id', $filters['cashier_id']))
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->select(
                DB::raw('DATE(opened_at) as date'),
                DB::raw('COUNT(*) as shift_count'),
                DB::raw('SUM(total_cash_sales + total_card_sales + total_other_sales) as total_sales')
            )
            ->groupBy(DB::raw('DATE(opened_at)'))
            ->orderBy('date', 'desc')
            ->limit(31)
            ->get()
            ->map(fn($row) => [
                'date' => $row->date,
                'shift_count' => $row->shift_count,
                'total_sales' => round($row->total_sales, 2),
            ])
            ->toArray();
    }

    protected function getExcelColumns(): array
    {
        return [
            'id' => 'رقم الوردية',
            'cashier' => 'الكاشير',
            'opened_at' => 'وقت الفتح',
            'closed_at' => 'وقت الإغلاق',
            'total_sales' => 'إجمالي المبيعات',
            'total_expenses' => 'المصروفات',
            'sales_count' => 'عدد الفواتير',
            'status' => 'الحالة',
        ];
    }
}
