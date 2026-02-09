<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\SalePayment;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PaymentMethodsReportController extends Controller
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

        return view('reports.print.payment-methods-print', compact('filters', 'reportData'));
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        $exportService = new ReportExportService();

        if ($format == 'excel') {
            return $exportService->exportExcel(
                $reportData['by_method'],
                'payment_methods_report',
                $this->getExcelColumns()
            );
        }

        return $exportService->exportPdf(
            'reports.print.payment-methods-print',
            compact('filters', 'reportData'),
            'payment_methods_report'
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
        $cacheKey = 'payment_methods_report_' . md5(json_encode($filters));

        if ($this->shouldCache($filters) && $cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $dateFrom = $filters['date_from'] ?? today()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? today()->format('Y-m-d');

        $query = SalePayment::whereHas('sale', function ($q) use ($dateFrom, $dateTo, $filters) {
            $q->where('status', 'completed')
                ->whereBetween('sale_date', [$dateFrom, $dateTo]);

            if (!empty($filters['shift_id'])) {
                $q->where('shift_id', $filters['shift_id']);
            }
            if (!empty($filters['cashier_id'])) {
                $q->where('cashier_id', $filters['cashier_id']);
            }
        });

        if (!empty($filters['cashbox_id'])) {
            $query->where('cashbox_id', $filters['cashbox_id']);
        }

        $totalAmount = $query->sum('amount');
        $totalCount = $query->count();

        $byMethod = $this->getByMethod($query->clone());
        $byCashbox = $this->getByCashbox($query->clone());
        $byDate = $this->getByDate($dateFrom, $dateTo, $filters);

        $reportData = [
            'summary' => [
                'total_amount' => round($totalAmount, 2),
                'total_count' => $totalCount,
            ],
            'by_method' => $byMethod,
            'by_cashbox' => $byCashbox,
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

    protected function getByMethod($query): array
    {
        $payments = $query->select(
            'payment_method_id',
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(*) as transaction_count')
        )
            ->groupBy('payment_method_id')
            ->get();

        $allMethods = PaymentMethod::active()->orderBy('sort_order')->get();

        return $allMethods->map(function ($method) use ($payments) {
            $payment = $payments->firstWhere('payment_method_id', $method->id);
            return [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'total_amount' => round($payment->total_amount ?? 0, 2),
                'transaction_count' => $payment->transaction_count ?? 0,
            ];
        })->toArray();
    }

    protected function getByCashbox($query): array
    {
        $payments = $query->select(
            'cashbox_id',
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(*) as transaction_count')
        )
            ->whereNotNull('cashbox_id')
            ->groupBy('cashbox_id')
            ->get();

        $cashboxes = Cashbox::active()->orderBy('name')->get();

        return $cashboxes->map(function ($cashbox) use ($payments) {
            $payment = $payments->firstWhere('cashbox_id', $cashbox->id);
            return [
                'id' => $cashbox->id,
                'name' => $cashbox->name,
                'type' => $cashbox->type,
                'total_amount' => round($payment->total_amount ?? 0, 2),
                'transaction_count' => $payment->transaction_count ?? 0,
            ];
        })->toArray();
    }

    protected function getByDate(string $dateFrom, string $dateTo, array $filters): array
    {
        return SalePayment::whereHas('sale', function ($q) use ($dateFrom, $dateTo, $filters) {
            $q->where('status', 'completed')
                ->whereBetween('sale_date', [$dateFrom, $dateTo]);

            if (!empty($filters['shift_id'])) {
                $q->where('shift_id', $filters['shift_id']);
            }
            if (!empty($filters['cashier_id'])) {
                $q->where('cashier_id', $filters['cashier_id']);
            }
        })
            ->when(!empty($filters['cashbox_id']), fn($q) => $q->where('cashbox_id', $filters['cashbox_id']))
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->select(
                DB::raw('DATE(sales.sale_date) as date'),
                DB::raw('SUM(sale_payments.amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw('DATE(sales.sale_date)'))
            ->orderBy('date', 'desc')
            ->limit(31)
            ->get()
            ->map(fn($row) => [
                'date' => $row->date,
                'total_amount' => round($row->total_amount, 2),
                'transaction_count' => $row->transaction_count,
            ])
            ->toArray();
    }

    protected function getExcelColumns(): array
    {
        return [
            'name' => 'طريقة الدفع',
            'total_amount' => 'المبلغ',
            'transaction_count' => 'عدد العمليات',
        ];
    }
}
