<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomersReportController extends Controller
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

        return view('reports.print.customers-print', compact('filters', 'reportData'));
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        $exportService = new ReportExportService();

        if ($format === 'excel') {
            return $exportService->exportExcel(
                $reportData['customers'],
                'customers_report',
                $this->getExcelColumns()
            );
        }

        return $exportService->exportPdf(
            'reports.print.customers-print',
            compact('filters', 'reportData'),
            'customers_report'
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
        $cacheKey = 'customers_report_' . md5(json_encode($filters));

        if ($this->shouldCache($filters) && $cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $dateFrom = $filters['date_from'] ?? today()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? today()->format('Y-m-d');

        $query = Customer::where('status', true);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['balance_status'])) {
            if ($filters['balance_status'] === 'with_balance') {
                $query->where('current_balance', '!=', 0);
            } elseif ($filters['balance_status'] === 'no_balance') {
                $query->where('current_balance', 0);
            }
        }

        $customers = $query->get();

        $salesData = Sale::where('status', 'completed')
            ->whereNotNull('customer_id')
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->select(
                'customer_id',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total) as total_purchases')
            )
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $totalCustomers = $customers->count();
        $totalBalance = $customers->sum('current_balance');
        $totalPurchases = $salesData->sum('total_purchases');

        $customersList = $customers->map(function ($customer) use ($salesData) {
            $sales = $salesData->get($customer->id);
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'current_balance' => round($customer->current_balance, 2),
                'credit_limit' => round($customer->credit_limit, 2),
                'available_credit' => round($customer->available_credit, 2),
                'invoice_count' => $sales?->invoice_count ?? 0,
                'total_purchases' => round($sales?->total_purchases ?? 0, 2),
            ];
        })->sortByDesc('total_purchases')->values();

        $topCustomers = $customersList->take(20)->toArray();

        $reportData = [
            'summary' => [
                'total_customers' => $totalCustomers,
                'total_balance' => round($totalBalance, 2),
                'total_purchases' => round($totalPurchases, 2),
                'with_balance_count' => $customers->where('current_balance', '!=', 0)->count(),
                'average_balance' => $totalCustomers > 0 ? round($totalBalance / $totalCustomers, 2) : 0,
            ],
            'customers' => $customersList->toArray(),
            'top_customers' => $topCustomers,
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
            'name' => 'اسم العميل',
            'phone' => 'رقم الهاتف',
            'current_balance' => 'الرصيد الحالي',
            'credit_limit' => 'حد الائتمان',
            'invoice_count' => 'عدد الفواتير',
            'total_purchases' => 'إجمالي المشتريات',
        ];
    }
}
