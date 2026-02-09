<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Services\Reports\ReportExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
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

        return view('reports.print.sales-print', compact('filters', 'reportData'));
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->validateFilters($request);
        $reportData = $this->generateReportData($filters);

        $exportService = new ReportExportService();

        if ($format == 'excel') {
            return $exportService->exportExcel(
                $reportData['invoices'],
                'sales_report',
                $this->getExcelColumns()
            );
        }

        return $exportService->exportPdf(
            'reports.print.sales-print',
            compact('filters', 'reportData'),
            'sales_report'
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
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);
    }

    protected function generateReportData(array $filters): array
    {
        $cacheKey = 'sales_report_' . md5(json_encode($filters));

        if ($this->shouldCache($filters) && $cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $salesQuery = Sale::where('status', 'completed');

        if (!empty($filters['date_from'])) {
            $salesQuery->whereDate('sale_date', '>=', $filters['date_from']);
        } else {
            $salesQuery->whereDate('sale_date', '>=', today()->subDays(30));
        }

        if (!empty($filters['date_to'])) {
            $salesQuery->whereDate('sale_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['shift_id'])) {
            $salesQuery->where('shift_id', $filters['shift_id']);
        }

        if (!empty($filters['cashier_id'])) {
            $salesQuery->where('cashier_id', $filters['cashier_id']);
        }

        $saleIds = $salesQuery->pluck('id');

        if (!empty($filters['payment_method_id'])) {
            $saleIds = SalePayment::whereIn('sale_id', $saleIds)
                ->where('payment_method_id', $filters['payment_method_id'])
                ->pluck('sale_id')
                ->unique();
            $salesQuery->whereIn('id', $saleIds);
        }

        $summary = $this->getSummary($salesQuery->clone(), $saleIds);
        $salesByDate = $this->getSalesByDate($salesQuery->clone());
        $topProducts = $this->getTopProducts($saleIds, 20);
        $salesByHour = $this->getSalesByHour($saleIds);
        $invoices = $this->getInvoicesList($salesQuery->clone());

        $reportData = [
            'summary' => $summary,
            'sales_by_date' => $salesByDate,
            'top_products' => $topProducts,
            'sales_by_hour' => $salesByHour,
            'invoices' => $invoices,
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

    protected function getSummary($salesQuery, $saleIds): array
    {
        $totalSales = $salesQuery->sum('total');
        $totalDiscount = $salesQuery->sum('discount_amount');
        $invoiceCount = $salesQuery->count();

        $itemsData = SaleItem::whereIn('sale_id', $saleIds)
            ->selectRaw('SUM(base_quantity) as total_quantity, SUM(total_price) as total_revenue, SUM(cost_at_sale * base_quantity) as total_cost')
            ->first();

        $totalRevenue = $itemsData->total_revenue ?? 0;
        $totalCost = $itemsData->total_cost ?? 0;
        $totalProfit = $totalRevenue - $totalCost;

        return [
            'total_sales' => round($totalSales, 2),
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
            'profit_margin' => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0,
            'invoice_count' => $invoiceCount,
            'items_count' => round($itemsData->total_quantity ?? 0, 2),
            'total_discount' => round($totalDiscount, 2),
            'average_invoice' => $invoiceCount > 0 ? round($totalSales / $invoiceCount, 2) : 0,
        ];
    }

    protected function getSalesByDate($salesQuery): array
    {
        return $salesQuery->select(
            DB::raw('DATE(sale_date) as date'),
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('SUM(total) as total_sales')
        )
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->orderBy('date', 'desc')
            ->limit(31)
            ->get()
            ->map(fn($row) => [
                'date' => $row->date,
                'invoice_count' => $row->invoice_count,
                'total_sales' => round($row->total_sales, 2),
            ])
            ->toArray();
    }

    protected function getTopProducts($saleIds, int $limit = 20): array
    {
        return SaleItem::whereIn('sale_id', $saleIds)
            ->select(
                'product_id',
                DB::raw('SUM(base_quantity) as total_quantity'),
                DB::raw('SUM(total_price) as total_revenue')
            )
            ->groupBy('product_id')
            ->with('product:id,name,barcode')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(fn($item) => [
                'product_name' => $item->product->name ?? '-',
                'barcode' => $item->product->barcode ?? '-',
                'quantity' => round($item->total_quantity, 2),
                'revenue' => round($item->total_revenue, 2),
            ])
            ->toArray();
    }

    protected function getSalesByHour($saleIds): array
    {
        return Sale::whereIn('id', $saleIds)
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get()
            ->map(fn($row) => [
                'hour' => $row->hour,
                'count' => $row->count,
                'total' => round($row->total, 2),
            ])
            ->toArray();
    }

    protected function getInvoicesList($salesQuery): array
    {
        return $salesQuery->with(['customer:id,name', 'cashier:id,name'])
            ->orderByDesc('sale_date')
            ->limit(100)
            ->get()
            ->map(fn($sale) => [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'sale_date' => $sale->sale_date->format('Y-m-d H:i'),
                'customer' => $sale->customer->name ?? 'عميل نقدي',
                'cashier' => $sale->cashier->name ?? '-',
                'total' => round($sale->total, 2),
                'payment_status' => $sale->payment_status,
            ])
            ->toArray();
    }

    protected function getExcelColumns(): array
    {
        return [
            'invoice_number' => 'رقم الفاتورة',
            'sale_date' => 'التاريخ',
            'customer' => 'العميل',
            'cashier' => 'الكاشير',
            'total' => 'الإجمالي',
            'payment_status' => 'حالة الدفع',
        ];
    }
}
