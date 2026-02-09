<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SalesReturn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        $cashierId = $request->get('cashier_id');

        $cashiers = User::orderBy('name')->get(['id', 'name']);
        $paymentMethods = PaymentMethod::active()->orderBy('sort_order')->get();

        $reportData = null;
        if ($request->has('generate')) {
            $reportData = $this->generateReportData($date, $cashierId);
        }

        return view('reports.daily-report', compact(
            'date',
            'cashierId',
            'cashiers',
            'paymentMethods',
            'reportData'
        ));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'cashier_id' => 'nullable|exists:users,id',
        ]);

        $date = $validated['date'];
        $cashierId = $validated['cashier_id'] ?? null;

        $reportData = $this->generateReportData($date, $cashierId);

        return response()->json([
            'success' => true,
            'data' => $reportData,
        ]);
    }

    public function print(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        $cashierId = $request->get('cashier_id');

        $reportData = $this->generateReportData($date, $cashierId);
        $cashier = $cashierId ? User::find($cashierId) : null;

        return view('reports.daily-report-print', compact('date', 'cashier', 'reportData'));
    }

    protected function generateReportData(string $date, ?int $cashierId = null): array
    {
        $cacheKey = "daily_report_{$date}_" . ($cashierId ?? 'all');
        $isToday = Carbon::parse($date)->isToday();

        if (!$isToday) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $salesQuery = Sale::where('status', 'completed')
            ->whereDate('sale_date', $date);

        if ($cashierId) {
            $salesQuery->where('cashier_id', $cashierId);
        }

        $saleIds = $salesQuery->pluck('id');

        $summary = $this->getSummary($salesQuery->clone(), $saleIds);
        $soldItems = $this->getSoldItems($saleIds);
        $paymentMethods = $this->getPaymentMethods($saleIds);
        $inventoryStatus = $this->getInventoryStatus();
        $returns = $this->getReturns($date, $cashierId);

        $reportData = [
            'summary' => $summary,
            'sold_items' => $soldItems,
            'payment_methods' => $paymentMethods,
            'inventory_status' => $inventoryStatus,
            'returns' => $returns,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        if (!$isToday) {
            Cache::put($cacheKey, $reportData, now()->addHours(24));
        }

        return $reportData;
    }

    protected function getSummary($salesQuery, $saleIds): array
    {
        $totalSales = (clone $salesQuery)->sum('total');
        $totalDiscount = (clone $salesQuery)->sum('discount_amount');
        $invoiceCount = (clone $salesQuery)->count();

        $itemsData = SaleItem::whereIn('sale_id', $saleIds)
            ->selectRaw('SUM(base_quantity) as total_quantity, SUM(total_price) as total_revenue, SUM(cost_at_sale * base_quantity) as total_cost')
            ->first();

        $totalQuantity = $itemsData->total_quantity ?? 0;
        $totalRevenue = $itemsData->total_revenue ?? 0;
        $totalCost = $itemsData->total_cost ?? 0;
        $totalProfit = $totalRevenue - $totalCost;
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return [
            'total_sales' => round($totalSales, 2),
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
            'profit_margin' => round($profitMargin, 2),
            'invoice_count' => $invoiceCount,
            'items_count' => round($totalQuantity, 2),
            'total_discount' => round($totalDiscount, 2),
            'average_invoice' => $invoiceCount > 0 ? round($totalSales / $invoiceCount, 2) : 0,
        ];
    }

    protected function getSoldItems($saleIds): array
    {
        $items = SaleItem::whereIn('sale_id', $saleIds)
            ->select(
                'product_id',
                DB::raw('SUM(base_quantity) as total_quantity'),
                DB::raw('SUM(total_price) as total_revenue'),
                DB::raw('SUM(cost_at_sale * base_quantity) as total_cost')
            )
            ->groupBy('product_id')
            ->with('product:id,name,barcode')
            ->orderByDesc('total_revenue')
            ->get();

        return $items->map(function ($item) {
            $profit = $item->total_revenue - $item->total_cost;
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? '-',
                'barcode' => $item->product->barcode ?? '-',
                'quantity' => round($item->total_quantity, 2),
                'total_revenue' => round($item->total_revenue, 2),
                'total_cost' => round($item->total_cost, 2),
                'profit' => round($profit, 2),
                'profit_margin' => $item->total_revenue > 0 ? round(($profit / $item->total_revenue) * 100, 2) : 0,
            ];
        })->toArray();
    }

    protected function getPaymentMethods($saleIds): array
    {
        $payments = SalePayment::whereIn('sale_id', $saleIds)
            ->select(
                'payment_method_id',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('payment_method_id')
            ->with('paymentMethod:id,name,code')
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

    protected function getInventoryStatus(): array
    {
        $products = Product::where('status', 'active')
            ->where(function ($query) {
                $query->whereHas('inventoryBatches', function ($q) {
                    $q->where('quantity', '>', 0)
                        ->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '<=', now()->addDays(30));
                })
                ->orWhereDoesntHave('inventoryBatches', function ($q) {
                    $q->where('quantity', '>', 0);
                });
            })
            ->limit(50)
            ->get();

        return $products->map(function ($product) {
            $stock = $product->inventoryBatches()->where('quantity', '>', 0)->sum('quantity');

            $nearestExpiry = $product->inventoryBatches()
                ->where('quantity', '>', 0)
                ->whereNotNull('expiry_date')
                ->orderBy('expiry_date')
                ->first();

            $expiryDate = $nearestExpiry?->expiry_date;
            $daysToExpiry = $expiryDate ? now()->startOfDay()->diffInDays($expiryDate, false) : null;

            $status = 'ok';
            if ($stock <= 0) {
                $status = 'out_of_stock';
            } elseif ($stock <= 5) {
                $status = 'low_stock';
            }

            if ($daysToExpiry != null && $daysToExpiry <= 7) {
                $status = $daysToExpiry < 0 ? 'expired' : 'expiring_soon';
            }

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'barcode' => $product->barcode,
                'current_stock' => round($stock, 2),
                'expiry_date' => $expiryDate?->format('Y-m-d'),
                'days_to_expiry' => $daysToExpiry,
                'status' => $status,
            ];
        })->toArray();
    }

    protected function getReturns(string $date, ?int $cashierId = null): array
    {
        $query = SalesReturn::where('status', 'completed')
            ->whereDate('return_date', $date);

        if ($cashierId) {
            $query->where('created_by', $cashierId);
        }

        $returns = $query->with(['sale:id,invoice_number', 'items.product:id,name'])
            ->get();

        $totalReturns = $returns->sum('total_amount');
        $returnCount = $returns->count();

        $returnItems = $returns->flatMap(function ($return) {
            return $return->items->map(function ($item) use ($return) {
                return [
                    'return_number' => $return->return_number,
                    'invoice_number' => $return->sale->invoice_number ?? '-',
                    'product_name' => $item->product->name ?? '-',
                    'quantity' => $item->quantity,
                    'amount' => $item->total_amount,
                    'reason' => $return->reason_arabic,
                ];
            });
        });

        return [
            'total_amount' => round($totalReturns, 2),
            'count' => $returnCount,
            'items' => $returnItems->toArray(),
        ];
    }
}
