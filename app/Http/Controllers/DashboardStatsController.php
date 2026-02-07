<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\PaymentMethod;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardStatsController extends Controller
{
    protected function isManager()
    {
        return Auth::user()->role === 'manager';
    }

    public function getSalesStats()
    {
        $cacheKey = 'dashboard_sales_stats_' . now()->format('Y-m-d-H');

        $data = Cache::remember($cacheKey, 300, function () {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $weekAgo = Carbon::today()->subDays(7);

            $todaySales = Sale::whereDate('created_at', $today)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $yesterdaySales = Sale::whereDate('created_at', $yesterday)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $todayInvoices = Sale::whereDate('created_at', $today)
                ->where('status', '!=', 'cancelled')
                ->count();

            $changePercent = $yesterdaySales > 0
                ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
                : 0;

            $last7Days = Sale::where('status', '!=', 'cancelled')
                ->whereDate('created_at', '>=', $weekAgo)
                ->selectRaw('DATE(created_at) as date, SUM(total) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('total', 'date')
                ->toArray();

            $sparklineData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i)->format('Y-m-d');
                $sparklineData[] = (float)($last7Days[$date] ?? 0);
            }

            return [
                'today' => $todaySales,
                'yesterday' => $yesterdaySales,
                'invoices' => $todayInvoices,
                'change_percent' => $changePercent,
                'sparkline' => $sparklineData,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getProfitStats()
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $cacheKey = 'dashboard_profit_stats_' . now()->format('Y-m-d-H');

        $data = Cache::remember($cacheKey, 300, function () {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            $todayRevenue = Sale::whereDate('created_at', $today)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $todayCost = SaleItem::whereHas('sale', function($q) use ($today) {
                $q->whereDate('created_at', $today)->where('status', '!=', 'cancelled');
            })->sum(DB::raw('base_quantity * cost_at_sale'));

            $todayExpenses = Expense::whereDate('expense_date', $today)->sum('amount');

            $grossProfit = $todayRevenue - $todayCost;
            $netProfit = $grossProfit - $todayExpenses;

            $yesterdayRevenue = Sale::whereDate('created_at', $yesterday)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $yesterdayCost = SaleItem::whereHas('sale', function($q) use ($yesterday) {
                $q->whereDate('created_at', $yesterday)->where('status', '!=', 'cancelled');
            })->sum(DB::raw('base_quantity * cost_at_sale'));

            $yesterdayProfit = $yesterdayRevenue - $yesterdayCost;

            $changePercent = $yesterdayProfit > 0
                ? round((($grossProfit - $yesterdayProfit) / $yesterdayProfit) * 100, 1)
                : 0;

            $profitMargin = $todayRevenue > 0
                ? round(($grossProfit / $todayRevenue) * 100, 1)
                : 0;

            return [
                'revenue' => $todayRevenue,
                'cost' => $todayCost,
                'gross_profit' => $grossProfit,
                'expenses' => $todayExpenses,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin,
                'change_percent' => $changePercent,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getPaymentStats()
    {
        $cacheKey = 'dashboard_payment_stats_' . now()->format('Y-m-d-H');

        $data = Cache::remember($cacheKey, 300, function () {
            $today = Carbon::today();

            $paymentMethods = SalePayment::whereHas('sale', function($q) use ($today) {
                $q->whereDate('created_at', $today)->where('status', '!=', 'cancelled');
            })
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw('payment_methods.name, SUM(sale_payments.amount) as total')
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

            $total = $paymentMethods->sum('total');

            $methods = $paymentMethods->map(function($item) use ($total) {
                return [
                    'name' => $item->name,
                    'total' => $item->total,
                    'percentage' => $total > 0 ? round(($item->total / $total) * 100, 1) : 0,
                ];
            })->toArray();

            return [
                'total' => $total,
                'methods' => $methods,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getCashboxStats()
    {
        $cacheKey = 'dashboard_cashbox_stats_' . now()->format('Y-m-d-H-i');

        $data = Cache::remember($cacheKey, 60, function () {
            $cashboxes = Cashbox::where('status', 'active')->get();

            $totalBalance = $cashboxes->sum('current_balance');

            $cashboxData = $cashboxes->map(function($box) {
                return [
                    'name' => $box->name,
                    'balance' => $box->current_balance,
                ];
            })->toArray();

            return [
                'total_balance' => $totalBalance,
                'cashboxes' => $cashboxData,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getExpensesStats()
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $cacheKey = 'dashboard_expenses_stats_' . now()->format('Y-m-d-H');

        $data = Cache::remember($cacheKey, 300, function () {
            $today = Carbon::today();
            $weekAgo = Carbon::today()->subDays(7);

            $todayExpenses = Expense::whereDate('expense_date', $today)->sum('amount');
            $weekExpenses = Expense::whereDate('expense_date', '>=', $weekAgo)->sum('amount');

            $expenseCount = Expense::whereDate('expense_date', $today)->count();

            $byCategory = Expense::whereDate('expense_date', $today)
                ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
                ->selectRaw('expense_categories.name, SUM(expenses.amount) as total')
                ->groupBy('expense_categories.id', 'expense_categories.name')
                ->get()
                ->toArray();

            return [
                'today' => $todayExpenses,
                'week' => $weekExpenses,
                'count' => $expenseCount,
                'by_category' => $byCategory,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getInventoryStats()
    {
        $cacheKey = 'dashboard_inventory_stats_' . now()->format('Y-m-d-H');

        $data = Cache::remember($cacheKey, 300, function () {
            $totalProducts = Product::count();

            $productsWithStock = DB::table('products')
                ->leftJoin('inventory_batches', 'products.id', '=', 'inventory_batches.product_id')
                ->select('products.id', 'products.name', DB::raw('COALESCE(SUM(inventory_batches.quantity), 0) as total_stock'))
                ->groupBy('products.id', 'products.name')
                ->get();

            $lowStockProducts = $productsWithStock->filter(function($p) {
                return $p->total_stock > 0 && $p->total_stock <= 10;
            })->count();

            $outOfStockProducts = $productsWithStock->filter(function($p) {
                return $p->total_stock <= 0;
            })->count();

            $lowStockList = $productsWithStock->filter(function($p) {
                return $p->total_stock > 0 && $p->total_stock <= 10;
            })->sortBy('total_stock')->take(10)->values()->toArray();

            $stockValue = DB::table('inventory_batches')
                ->sum(DB::raw('quantity * cost_price'));

            return [
                'total_products' => $totalProducts,
                'low_stock_count' => $lowStockProducts,
                'out_of_stock_count' => $outOfStockProducts,
                'low_stock_items' => $lowStockList,
                'stock_value' => $stockValue,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getDebtsStats()
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $cacheKey = 'dashboard_debts_stats_' . now()->format('Y-m-d-H');

        $data = Cache::remember($cacheKey, 300, function () {
            $customerDebts = Customer::where('current_balance', '>', 0)->sum('current_balance');
            $customerCount = Customer::where('current_balance', '>', 0)->count();

            $supplierDebts = Supplier::where('current_balance', '<', 0)->sum('current_balance');
            $supplierCount = Supplier::where('current_balance', '<', 0)->count();

            $topCustomerDebts = Customer::where('current_balance', '>', 0)
                ->select('id', 'name', 'current_balance')
                ->orderByDesc('current_balance')
                ->limit(5)
                ->get()
                ->toArray();

            return [
                'customer_debts' => $customerDebts,
                'customer_count' => $customerCount,
                'supplier_debts' => abs($supplierDebts),
                'supplier_count' => $supplierCount,
                'top_customers' => $topCustomerDebts,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getWeeklySalesChart()
    {
        $cacheKey = 'dashboard_weekly_chart_' . now()->format('Y-m-d-H');

        $data = Cache::remember($cacheKey, 300, function () {
            $labels = [];
            $sales = [];
            $costs = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $labels[] = $date->locale('ar')->dayName;

                $daySales = Sale::whereDate('created_at', $date)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total');

                $dayCost = SaleItem::whereHas('sale', function($q) use ($date) {
                    $q->whereDate('created_at', $date)->where('status', '!=', 'cancelled');
                })->sum(DB::raw('base_quantity * cost_at_sale'));

                $sales[] = (float)$daySales;
                $costs[] = (float)$dayCost;
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'name' => 'المبيعات',
                        'data' => $sales,
                    ],
                    [
                        'name' => 'التكلفة',
                        'data' => $costs,
                    ],
                ],
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getDailySalesChart()
    {
        $cacheKey = 'dashboard_daily_chart_' . now()->format('Y-m-d-H');

        $data = Cache::remember($cacheKey, 300, function () {
            $hours = [];
            $sales = [];

            $today = Carbon::today();

            for ($i = 8; $i <= 23; $i++) {
                $hours[] = sprintf('%02d:00', $i);

                $hourSales = Sale::whereDate('created_at', $today)
                    ->whereRaw('HOUR(created_at) = ?', [$i])
                    ->where('status', '!=', 'cancelled')
                    ->sum('total');

                $sales[] = (float)$hourSales;
            }

            return [
                'labels' => $hours,
                'data' => $sales,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getTopProducts()
    {
        $cacheKey = 'dashboard_top_products_' . now()->format('Y-m-d');

        $data = Cache::remember($cacheKey, 300, function () {
            $monthStart = Carbon::today()->startOfMonth();

            $topProducts = DB::table('sale_items')
                ->join('sales', function($join) use ($monthStart) {
                    $join->on('sale_items.sale_id', '=', 'sales.id')
                        ->where('sales.created_at', '>=', $monthStart)
                        ->where('sales.status', '!=', 'cancelled');
                })
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->select(
                    'products.id as id',
                    'products.name as name',
                    DB::raw('SUM(sale_items.quantity) as quantity'),
                    DB::raw('SUM(sale_items.total_price) as total')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('quantity')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => (float) $item->quantity,
                        'total' => (float) $item->total,
                    ];
                })
                ->toArray();

            return $topProducts;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getRecentSales()
    {
        $sales = Sale::with(['customer', 'cashier'])
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'customer' => $sale->customer?->name ?? 'زبون نقدي',
                    'total' => $sale->total,
                    'status' => $sale->status,
                    'created_at' => $sale->created_at->format('Y-m-d H:i'),
                    'cashier' => $sale->cashier?->name,
                ];
            });

        return response()->json(['success' => true, 'data' => $sales]);
    }

    public function getAllStats()
    {
        $isManager = $this->isManager();

        $data = [
            'sales' => $this->getSalesStatsData(),
            'inventory' => $this->getInventoryStatsData(),
            'cashbox' => $this->getCashboxStatsData(),
            'payment_methods' => $this->getPaymentStatsData(),
        ];

        if ($isManager) {
            $data['profit'] = $this->getProfitStatsData();
            $data['expenses'] = $this->getExpensesStatsData();
            $data['debts'] = $this->getDebtsStatsData();
        }

        return response()->json(['success' => true, 'data' => $data, 'is_manager' => $isManager]);
    }

    protected function getSalesStatsData()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $weekAgo = Carbon::today()->subDays(7);

        $todaySales = Sale::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $yesterdaySales = Sale::whereDate('created_at', $yesterday)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $todayInvoices = Sale::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->count();

        $changePercent = $yesterdaySales > 0
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : 0;

        $last7Days = Sale::where('status', '!=', 'cancelled')
            ->whereDate('created_at', '>=', $weekAgo)
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $sparklineData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $sparklineData[] = (float)($last7Days[$date] ?? 0);
        }

        return [
            'today' => $todaySales,
            'yesterday' => $yesterdaySales,
            'invoices' => $todayInvoices,
            'change_percent' => $changePercent,
            'sparkline' => $sparklineData,
        ];
    }

    protected function getProfitStatsData()
    {
        $today = Carbon::today();

        $todayRevenue = Sale::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $todayCost = SaleItem::whereHas('sale', function($q) use ($today) {
            $q->whereDate('created_at', $today)->where('status', '!=', 'cancelled');
        })->sum(DB::raw('base_quantity * cost_at_sale'));

        $todayExpenses = Expense::whereDate('expense_date', $today)->sum('amount');

        $grossProfit = $todayRevenue - $todayCost;
        $netProfit = $grossProfit - $todayExpenses;

        $profitMargin = $todayRevenue > 0
            ? round(($grossProfit / $todayRevenue) * 100, 1)
            : 0;

        return [
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
        ];
    }

    protected function getInventoryStatsData()
    {
        $productsWithStock = DB::table('products')
            ->leftJoin('inventory_batches', 'products.id', '=', 'inventory_batches.product_id')
            ->select('products.id', DB::raw('COALESCE(SUM(inventory_batches.quantity), 0) as total_stock'))
            ->groupBy('products.id')
            ->get();

        $lowStockProducts = $productsWithStock->filter(function($p) {
            return $p->total_stock > 0 && $p->total_stock <= 10;
        })->count();

        $outOfStockProducts = $productsWithStock->filter(function($p) {
            return $p->total_stock <= 0;
        })->count();

        return [
            'low_stock_count' => $lowStockProducts,
            'out_of_stock_count' => $outOfStockProducts,
        ];
    }

    protected function getCashboxStatsData()
    {
        $totalBalance = Cashbox::where('status', 'active')->sum('current_balance');

        return [
            'total_balance' => $totalBalance,
        ];
    }

    protected function getPaymentStatsData()
    {
        $today = Carbon::today();

        $paymentMethods = SalePayment::whereHas('sale', function($q) use ($today) {
            $q->whereDate('created_at', $today)->where('status', '!=', 'cancelled');
        })
        ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
        ->selectRaw('payment_methods.name, SUM(sale_payments.amount) as total')
        ->groupBy('payment_methods.id', 'payment_methods.name')
        ->get();

        $total = $paymentMethods->sum('total');

        return [
            'total' => $total,
            'methods' => $paymentMethods->map(function($item) use ($total) {
                return [
                    'name' => $item->name,
                    'total' => $item->total,
                    'percentage' => $total > 0 ? round(($item->total / $total) * 100, 1) : 0,
                ];
            })->toArray(),
        ];
    }

    protected function getExpensesStatsData()
    {
        $today = Carbon::today();

        return [
            'today' => Expense::whereDate('expense_date', $today)->sum('amount'),
        ];
    }

    protected function getDebtsStatsData()
    {
        $customerDebts = Customer::where('current_balance', '>', 0)->sum('current_balance');
        $supplierDebts = Supplier::where('current_balance', '<', 0)->sum('current_balance');

        return [
            'customer_debts' => $customerDebts,
            'supplier_debts' => abs($supplierDebts),
        ];
    }
}
