<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Products\InventoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Products\UnitController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierAccountController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PriceCheckerController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\PosReturnController;
use App\Http\Controllers\Reports\FinancialTraceController;
use App\Http\Controllers\Reports\DailyReportController;
use App\Http\Controllers\Reports\PaymentReconciliationController;
use App\Http\Controllers\Reports\ReportsDashboardController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Reports\ProfitReportController;
use App\Http\Controllers\Reports\PaymentMethodsReportController;
use App\Http\Controllers\Reports\InventoryReportController;
use App\Http\Controllers\Reports\ExpensesReportController;
use App\Http\Controllers\Reports\ShiftsReportController;
use App\Http\Controllers\Reports\CustomersReportController;
use App\Http\Controllers\Reports\SuppliersReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InventoryCountController;
use App\Http\Controllers\ShiftReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\DashboardStatsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/','login');
Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard')->middleware('role:manager');

Route::prefix('dashboard/stats')->name('dashboard.stats.')->group(function () {
    Route::get('/all', [DashboardStatsController::class, 'getAllStats'])->name('all');
    Route::get('/sales', [DashboardStatsController::class, 'getSalesStats'])->name('sales');
    Route::get('/profit', [DashboardStatsController::class, 'getProfitStats'])->name('profit');
    Route::get('/payment', [DashboardStatsController::class, 'getPaymentStats'])->name('payment');
    Route::get('/cashbox', [DashboardStatsController::class, 'getCashboxStats'])->name('cashbox');
    Route::get('/expenses', [DashboardStatsController::class, 'getExpensesStats'])->name('expenses');
    Route::get('/inventory', [DashboardStatsController::class, 'getInventoryStats'])->name('inventory');
    Route::get('/debts', [DashboardStatsController::class, 'getDebtsStats'])->name('debts');
    Route::get('/weekly-chart', [DashboardStatsController::class, 'getWeeklySalesChart'])->name('weekly-chart');
    Route::get('/daily-chart', [DashboardStatsController::class, 'getDailySalesChart'])->name('daily-chart');
    Route::get('/top-products', [DashboardStatsController::class, 'getTopProducts'])->name('top-products');
    Route::get('/recent-sales', [DashboardStatsController::class, 'getRecentSales'])->name('recent-sales');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Price Checker Routes
Route::middleware('auth')->group(function () {
    Route::get('/price-checker', [PriceCheckerController::class, 'index'])->name('price-checker');
    Route::post('/price-checker/lookup', [PriceCheckerController::class, 'lookup'])->name('price-checker.lookup');
});

// Products Module Routes (Manager Only)
Route::middleware('role:manager')->group(function () {
    Route::get('/products/generate-barcode', [ProductController::class, 'generateBarcode'])->name('products.generate-barcode');
    Route::get('/products/check-barcode', [ProductController::class, 'checkBarcode'])->name('products.check-barcode');
    Route::post('/products/quick-store', [ProductController::class, 'quickStore'])->name('products.quick-store');
    Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::post('/products/{product}/barcodes', [ProductController::class, 'storeBarcode'])->name('products.barcodes.store');
    Route::put('/products/{product}/barcodes/{barcode}', [ProductController::class, 'updateBarcode'])->name('products.barcodes.update');
    Route::delete('/products/{product}/barcodes/{barcode}', [ProductController::class, 'destroyBarcode'])->name('products.barcodes.destroy');
    Route::resource('products', ProductController::class);
});

// Manager-Only Routes
Route::middleware('role:manager')->group(function () {
    // Inventory Routes (AJAX)
    Route::prefix('products/{product}/inventory')->name('products.inventory.')->group(function () {
        Route::post('/add', [InventoryController::class, 'addStock'])->name('add');
        Route::post('/remove', [InventoryController::class, 'removeStock'])->name('remove');
        Route::post('/adjust', [InventoryController::class, 'adjustStock'])->name('adjust');
        Route::get('/batches', [InventoryController::class, 'getBatches'])->name('batches');
        Route::get('/history', [InventoryController::class, 'getHistory'])->name('history');
        Route::post('/units', [InventoryController::class, 'updateUnits'])->name('units');
    });

    // Units Routes (AJAX)
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('/units', [UnitController::class, 'index'])->name('units.index');

    // Suppliers Routes
    Route::get('/suppliers/check-phone', [SupplierController::class, 'checkPhone'])->name('suppliers.check-phone');
    Route::resource('suppliers', SupplierController::class)->except(['create', 'show', 'edit']);

    // Supplier Account Routes
    Route::prefix('suppliers/{supplier}/account')->name('suppliers.account.')->group(function () {
        Route::get('/', [SupplierAccountController::class, 'show'])->name('show');
        Route::post('/debit', [SupplierAccountController::class, 'addDebit'])->name('debit');
        Route::post('/credit', [SupplierAccountController::class, 'addCredit'])->name('credit');
        Route::post('/opening-balance', [SupplierAccountController::class, 'setOpeningBalance'])->name('opening-balance');
        Route::get('/ledger', [SupplierAccountController::class, 'getLedger'])->name('ledger');
        Route::get('/summary', [SupplierAccountController::class, 'getAccountSummary'])->name('summary');
        Route::get('/print', [SupplierAccountController::class, 'print'])->name('print');
    });

    // Purchases Module Routes
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseController::class, 'create'])->name('create');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/search-products', [PurchaseController::class, 'searchProducts'])->name('search-products');
        Route::get('/product-by-barcode', [PurchaseController::class, 'getProductByBarcode'])->name('product-by-barcode');
        Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('show');
        Route::get('/{purchase}/edit', [PurchaseController::class, 'edit'])->name('edit');
        Route::put('/{purchase}', [PurchaseController::class, 'update'])->name('update');
        Route::post('/{purchase}/approve', [PurchaseController::class, 'approve'])->name('approve');
        Route::post('/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('cancel');
        Route::get('/{purchase}/print', [PurchaseController::class, 'print'])->name('print');
    });

    // Cashbox (Treasury) Routes
    Route::get('/cashboxes/check-name', [CashboxController::class, 'checkName'])->name('cashboxes.check-name');
    Route::get('/cashboxes/list', [CashboxController::class, 'getList'])->name('cashboxes.list');
    Route::get('/cashboxes', [CashboxController::class, 'index'])->name('cashboxes.index');
    Route::post('/cashboxes', [CashboxController::class, 'store'])->name('cashboxes.store');
    Route::get('/cashboxes/{cashbox}', [CashboxController::class, 'show'])->name('cashboxes.show');
    Route::put('/cashboxes/{cashbox}', [CashboxController::class, 'update'])->name('cashboxes.update');

    Route::prefix('cashboxes/{cashbox}')->name('cashboxes.')->group(function () {
        Route::post('/deposit', [CashboxController::class, 'deposit'])->name('deposit');
        Route::post('/withdraw', [CashboxController::class, 'withdraw'])->name('withdraw');
        Route::post('/transfer', [CashboxController::class, 'transfer'])->name('transfer');
        Route::get('/transactions', [CashboxController::class, 'getTransactions'])->name('transactions');
        Route::get('/summary', [CashboxController::class, 'getSummary'])->name('summary');
        Route::post('/opening-balance', [CashboxController::class, 'setOpeningBalance'])->name('opening-balance');
        Route::get('/print', [CashboxController::class, 'print'])->name('print');
    });
});

// Customers Routes (Manager Only)
Route::middleware('role:manager')->group(function () {
    Route::get('/customers/check-phone', [CustomerController::class, 'checkPhone'])->name('customers.check-phone');
    Route::resource('customers', CustomerController::class)->except(['create', 'show', 'edit']);

    // Customer Account Routes
    Route::prefix('customers/{customer}/account')->name('customers.account.')->group(function () {
        Route::get('/', [CustomerAccountController::class, 'show'])->name('show');
        Route::post('/debit', [CustomerAccountController::class, 'addDebit'])->name('debit');
        Route::post('/credit', [CustomerAccountController::class, 'addCredit'])->name('credit');
        Route::post('/opening-balance', [CustomerAccountController::class, 'setOpeningBalance'])->name('opening-balance');
        Route::get('/ledger', [CustomerAccountController::class, 'getLedger'])->name('ledger');
        Route::get('/summary', [CustomerAccountController::class, 'getAccountSummary'])->name('summary');
        Route::get('/print', [CustomerAccountController::class, 'print'])->name('print');
    });
});

// POS Routes
Route::prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [PosController::class, 'screen'])->name('screen');
    Route::get('/search-products', [PosController::class, 'searchProducts'])->name('search-products');
    Route::get('/product-barcode', [PosController::class, 'getProductByBarcode'])->name('product-barcode');
    Route::get('/search-customers', [PosController::class, 'searchCustomers'])->name('search-customers');
    Route::post('/customer', [PosController::class, 'storeCustomer'])->name('customer.store');
    Route::post('/complete-sale', [PosController::class, 'completeSale'])->name('complete-sale');
    Route::post('/suspend', [PosController::class, 'suspendSale'])->name('suspend');
    Route::get('/suspended', [PosController::class, 'getSuspendedSales'])->name('suspended');
    Route::post('/resume/{id}', [PosController::class, 'resumeSale'])->name('resume');
    Route::post('/cancel/{sale}', [PosController::class, 'cancelSale'])->name('cancel');
    Route::get('/receipt/{sale}', [PosController::class, 'getReceipt'])->name('receipt');
    Route::delete('/suspended/{sale}', [PosController::class, 'deleteSuspended'])->name('delete-suspended');

    Route::get('/return/search', [PosReturnController::class, 'searchInvoice'])->name('return.search');
    Route::get('/return/sale/{sale}', [PosReturnController::class, 'loadSale'])->name('return.load-sale');
    Route::post('/return/process', [PosReturnController::class, 'processReturn'])->name('return.process');
    Route::get('/return/{salesReturn}/print', [PosReturnController::class, 'printReturnReceipt'])->name('return.print');
    Route::get('/returns/recent', [PosReturnController::class, 'getRecentReturns'])->name('returns.recent');
});

// Sales Print Routes (All authenticated users - for POS cashiers)
Route::prefix('sales')->name('sales.')->group(function () {
    Route::get('/{sale}/print-thermal', [SalesController::class, 'printThermal'])->name('print-thermal');
});

// Sales Routes (Manager Only)
Route::middleware('role:manager')->prefix('sales')->name('sales.')->group(function () {
    Route::get('/', [SalesController::class, 'index'])->name('index');
    Route::get('/{sale}', [SalesController::class, 'show'])->name('show');
    Route::get('/{sale}/print', [SalesController::class, 'print'])->name('print');
});

// Reports Routes (Manager Only)
Route::middleware('role:manager')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportsDashboardController::class, 'index'])->name('index');

    Route::get('/sales/generate', [SalesReportController::class, 'generate'])->name('sales.generate');
    Route::get('/sales/print', [SalesReportController::class, 'print'])->name('sales.print');
    Route::get('/sales/export/{format}', [SalesReportController::class, 'export'])->name('sales.export');

    Route::get('/profit/generate', [ProfitReportController::class, 'generate'])->name('profit.generate');
    Route::get('/profit/print', [ProfitReportController::class, 'print'])->name('profit.print');
    Route::get('/profit/export/{format}', [ProfitReportController::class, 'export'])->name('profit.export');

    Route::get('/payment-methods/generate', [PaymentMethodsReportController::class, 'generate'])->name('payment-methods.generate');
    Route::get('/payment-methods/print', [PaymentMethodsReportController::class, 'print'])->name('payment-methods.print');
    Route::get('/payment-methods/export/{format}', [PaymentMethodsReportController::class, 'export'])->name('payment-methods.export');

    Route::get('/inventory/generate', [InventoryReportController::class, 'generate'])->name('inventory.generate');
    Route::get('/inventory/print', [InventoryReportController::class, 'print'])->name('inventory.print');
    Route::get('/inventory/export/{format}', [InventoryReportController::class, 'export'])->name('inventory.export');

    Route::get('/expenses/generate', [ExpensesReportController::class, 'generate'])->name('expenses.generate');
    Route::get('/expenses/print', [ExpensesReportController::class, 'print'])->name('expenses.print');
    Route::get('/expenses/export/{format}', [ExpensesReportController::class, 'export'])->name('expenses.export');

    Route::get('/shifts/generate', [ShiftsReportController::class, 'generate'])->name('shifts.generate');
    Route::get('/shifts/print', [ShiftsReportController::class, 'print'])->name('shifts.print');
    Route::get('/shifts/export/{format}', [ShiftsReportController::class, 'export'])->name('shifts.export');

    Route::get('/customers/generate', [CustomersReportController::class, 'generate'])->name('customers.generate');
    Route::get('/customers/print', [CustomersReportController::class, 'print'])->name('customers.print');
    Route::get('/customers/export/{format}', [CustomersReportController::class, 'export'])->name('customers.export');

    Route::get('/suppliers/generate', [SuppliersReportController::class, 'generate'])->name('suppliers.generate');
    Route::get('/suppliers/print', [SuppliersReportController::class, 'print'])->name('suppliers.print');
    Route::get('/suppliers/export/{format}', [SuppliersReportController::class, 'export'])->name('suppliers.export');

    Route::get('/financial-trace', [FinancialTraceController::class, 'index'])->name('financial-trace');
    Route::get('/financial-trace/data', [FinancialTraceController::class, 'getTraceData'])->name('financial-trace.data');
    Route::get('/daily', [DailyReportController::class, 'index'])->name('daily-report');
    Route::get('/daily/generate', [DailyReportController::class, 'generate'])->name('daily-report.generate');
    Route::get('/daily/print', [DailyReportController::class, 'print'])->name('daily-report.print');
    Route::get('/payment-reconciliation', [PaymentReconciliationController::class, 'index'])->name('payment-reconciliation');
    Route::get('/payment-reconciliation/generate', [PaymentReconciliationController::class, 'generate'])->name('payment-reconciliation.generate');
});

// Shifts Routes
Route::prefix('shifts')->name('shifts.')->group(function () {
    Route::get('/current', [ShiftController::class, 'current'])->name('current');
    Route::post('/open', [ShiftController::class, 'open'])->name('open');
    Route::get('/open-list', [ShiftController::class, 'getOpenShifts'])->name('open-list');
    Route::get('/history', [ShiftController::class, 'history'])->name('history');
    Route::post('/validate-cashbox', [ShiftController::class, 'validateCashbox'])->name('validate-cashbox');
    Route::get('/debug', [ShiftController::class, 'debug'])->name('debug');
    Route::get('/{shift}', [ShiftController::class, 'show'])->name('show');
    Route::get('/{shift}/summary', [ShiftController::class, 'summary'])->name('summary');
    Route::get('/{shift}/cashboxes', [ShiftController::class, 'getShiftCashboxes'])->name('cashboxes');
    Route::post('/{shift}/add-cashbox', [ShiftController::class, 'addCashbox'])->name('add-cashbox');
    Route::post('/{shift}/close', [ShiftController::class, 'close'])->name('close');
    Route::post('/{shift}/force-close', [ShiftController::class, 'forceClose'])->name('force-close')->middleware('role:manager');
    Route::post('/{shift}/approve', [ShiftController::class, 'approve'])->name('approve')->middleware('role:manager');
});

// User Management Routes (Manager Only)
Route::middleware('role:manager')->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/check-username', [UserController::class, 'checkUsername'])->name('check-username');
    Route::get('/{user}', [UserController::class, 'show'])->name('show');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    Route::patch('/{user}/status', [UserController::class, 'updateStatus'])->name('status');
    Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
    Route::post('/{user}/cashboxes', [UserController::class, 'assignCashboxes'])->name('cashboxes');
    Route::get('/{user}/activity', [UserController::class, 'getActivityLog'])->name('activity');
});

// Inventory Counts Routes (Manager Only)
Route::middleware('role:manager')->prefix('inventory-counts')->name('inventory-counts.')->group(function () {
    Route::get('/', [InventoryCountController::class, 'index'])->name('index');
    Route::get('/create', [InventoryCountController::class, 'create'])->name('create');
    Route::post('/', [InventoryCountController::class, 'store'])->name('store');
    Route::get('/{inventoryCount}', [InventoryCountController::class, 'show'])->name('show');
    Route::post('/{inventoryCount}/start', [InventoryCountController::class, 'start'])->name('start');
    Route::get('/{inventoryCount}/count', [InventoryCountController::class, 'count'])->name('count');
    Route::get('/{inventoryCount}/search', [InventoryCountController::class, 'searchItems'])->name('search');
    Route::get('/{inventoryCount}/barcode', [InventoryCountController::class, 'getItemByBarcode'])->name('barcode');
    Route::post('/items/{item}/count', [InventoryCountController::class, 'saveCount'])->name('items.count');
    Route::post('/{inventoryCount}/complete', [InventoryCountController::class, 'complete'])->name('complete');
    Route::get('/{inventoryCount}/review', [InventoryCountController::class, 'review'])->name('review');
    Route::post('/{inventoryCount}/approve', [InventoryCountController::class, 'approve'])->name('approve');
    Route::post('/{inventoryCount}/cancel', [InventoryCountController::class, 'cancel'])->name('cancel');
    Route::get('/{inventoryCount}/export/{format?}', [InventoryCountController::class, 'export'])->name('export');
});

// Shift Reports Routes (Manager Only)
Route::middleware('role:manager')->prefix('shift-reports')->name('shift-reports.')->group(function () {
    Route::get('/', [ShiftReportController::class, 'index'])->name('index');
    Route::get('/filter', [ShiftReportController::class, 'filter'])->name('filter');
    Route::get('/export', [ShiftReportController::class, 'export'])->name('export');
    Route::get('/{shift}', [ShiftReportController::class, 'show'])->name('show');
});

// Expenses Routes (Manager Only)
Route::middleware('role:manager')->prefix('expenses')->name('expenses.')->group(function () {
    Route::get('/', [ExpenseController::class, 'index'])->name('index');
    Route::get('/filter', [ExpenseController::class, 'filter'])->name('filter');
    Route::get('/create', [ExpenseController::class, 'create'])->name('create');
    Route::post('/', [ExpenseController::class, 'store'])->name('store');
    Route::get('/report', [ExpenseController::class, 'report'])->name('report');
    Route::get('/{expense}', [ExpenseController::class, 'show'])->name('show');
});

// Expense Categories Routes (Manager Only)
Route::middleware('role:manager')->prefix('expense-categories')->name('expense-categories.')->group(function () {
    Route::get('/', [ExpenseCategoryController::class, 'index'])->name('index');
    Route::post('/', [ExpenseCategoryController::class, 'store'])->name('store');
    Route::put('/{category}', [ExpenseCategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [ExpenseCategoryController::class, 'destroy'])->name('destroy');
});

// Sync Routes (Local Desktop Mode)
Route::prefix('api/sync')->middleware('auth')->group(function () {
    Route::post('/pull', function () {
        if (!config('desktop.mode')) {
            return response()->json(['success' => false, 'message' => 'Sync not enabled']);
        }
        $sync = new \App\Services\SyncService();
        return response()->json($sync->pullChanges(config('desktop.device_id')));
    });
    
    Route::post('/push', function () {
        if (!config('desktop.mode')) {
            return response()->json(['success' => false, 'message' => 'Sync not enabled']);
        }
        $sync = new \App\Services\SyncService();
        return response()->json($sync->pushChanges(config('desktop.device_id')));
    });
    
    Route::get('/status', function () {
        if (!config('desktop.mode')) {
            return response()->json(['success' => false, 'message' => 'Sync not enabled']);
        }
        $sync = new \App\Services\SyncService();
        return response()->json($sync->getSyncStatus(config('desktop.device_id')));
    });

    Route::get('/check-connection', function () {
        if (!config('desktop.mode')) {
            return response()->json(['online' => false, 'reason' => 'desktop mode disabled']);
        }

        $serverUrl = config('desktop.server_url');
        if (empty($serverUrl)) {
            return response()->json(['online' => false, 'reason' => 'server url not configured']);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withOptions([
                'verify' => base_path('certs/cacert.pem'),
            ])->timeout(5)->get($serverUrl . '/api/v1/sync/timestamp');

            return response()->json([
                'online' => $response->successful(),
                'status' => $response->status(),
                'url' => $serverUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'online' => false,
                'reason' => $e->getMessage(),
                'url' => $serverUrl,
            ]);
        }
    });

});

Route::get('/api/sync/compare', function () {
    if (!config('desktop.mode')) {
        return response()->json(['error' => 'Desktop mode only']);
    }

    $models = [
        'users' => \App\Models\User::class,
        'units' => \App\Models\Unit::class,
        'suppliers' => \App\Models\Supplier::class,
        'customers' => \App\Models\Customer::class,
        'cashboxes' => \App\Models\Cashbox::class,
        'payment_methods' => \App\Models\PaymentMethod::class,
        'expense_categories' => \App\Models\ExpenseCategory::class,
        'products' => \App\Models\Product::class,
        'product_units' => \App\Models\ProductUnit::class,
        'product_barcodes' => \App\Models\ProductBarcode::class,
        'inventory_batches' => \App\Models\InventoryBatch::class,
        'purchases' => \App\Models\Purchase::class,
        'purchase_items' => \App\Models\PurchaseItem::class,
        'shifts' => \App\Models\Shift::class,
        'shift_cashboxes' => \App\Models\ShiftCashbox::class,
        'sales' => \App\Models\Sale::class,
        'sale_items' => \App\Models\SaleItem::class,
        'sale_payments' => \App\Models\SalePayment::class,
        'sales_returns' => \App\Models\SalesReturn::class,
        'sale_return_items' => \App\Models\SaleReturnItem::class,
        'expenses' => \App\Models\Expense::class,
        'stock_movements' => \App\Models\StockMovement::class,
        'cashbox_transactions' => \App\Models\CashboxTransaction::class,
        'customer_transactions' => \App\Models\CustomerTransaction::class,
        'supplier_transactions' => \App\Models\SupplierTransaction::class,
        'inventory_counts' => \App\Models\InventoryCount::class,
        'inventory_count_items' => \App\Models\InventoryCountItem::class,
        'user_activity_logs' => \App\Models\UserActivityLog::class,
    ];

    $localCounts = [];
    foreach ($models as $key => $modelClass) {
        try {
            $localCounts[$key] = $modelClass::count();
        } catch (\Exception $e) {
            $localCounts[$key] = 0;
        }
    }

    try {
        $response = \Illuminate\Support\Facades\Http::withOptions([
            'verify' => base_path('certs/cacert.pem'),
        ])->timeout(60)
            ->withToken(config('desktop.api_token'))
            ->get(config('desktop.server_url') . '/api/v1/sync/compare');

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Server returned ' . $response->status(),
                'body' => $response->body(),
            ]);
        }

        $server = $response->json();
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }

    $serverCounts = $server['counts'] ?? [];
    $countSummary = [];
    foreach ($models as $key => $modelClass) {
        $local = $localCounts[$key] ?? 0;
        $srv = $serverCounts[$key] ?? 0;
        $countSummary[$key] = [
            'local' => $local,
            'server' => $srv,
            'diff' => $local - $srv,
            'match' => $local === $srv,
        ];
    }

    $localFinancials = [
        'total_sales' => (float) \App\Models\Sale::where('status', '!=', 'cancelled')->sum('total'),
        'total_cost' => (float) \Illuminate\Support\Facades\DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', '!=', 'cancelled')
            ->selectRaw('SUM(sale_items.cost_at_sale * sale_items.base_quantity) as total')
            ->value('total'),
        'total_expenses' => (float) \App\Models\Expense::sum('amount'),
        'total_purchases' => (float) \App\Models\Purchase::sum('total'),
    ];
    $localFinancials['total_profit'] = $localFinancials['total_sales'] - $localFinancials['total_cost'];

    $serverFinancials = $server['financials'] ?? [];

    $financialComparison = [];
    foreach ($localFinancials as $key => $localVal) {
        $serverVal = (float) ($serverFinancials[$key] ?? 0);
        $financialComparison[$key] = [
            'local' => round($localVal, 2),
            'server' => round($serverVal, 2),
            'diff' => round($localVal - $serverVal, 2),
            'match' => abs($localVal - $serverVal) < 0.01,
        ];
    }

    $serverSales = collect($server['sales'] ?? []);
    $localSales = \App\Models\Sale::select('id', 'invoice_number', 'total', 'subtotal', 'discount_amount', 'tax_amount', 'status', 'local_uuid', 'device_id')
        ->orderBy('id')
        ->get();

    $serverByInvoice = $serverSales->keyBy('invoice_number');
    $localByInvoice = $localSales->keyBy('invoice_number');

    $salesComparison = [
        'only_local' => [],
        'only_server' => [],
        'mismatched_total' => [],
        'mismatched_items' => [],
    ];

    foreach ($localByInvoice as $inv => $sale) {
        if (!$inv) continue;
        if (!$serverByInvoice->has($inv)) {
            $localItems = \App\Models\SaleItem::where('sale_id', $sale->id)->get();
            $salesComparison['only_local'][] = [
                'invoice_number' => $inv,
                'id' => $sale->id,
                'total' => (float) $sale->total,
                'status' => $sale->status,
                'items_count' => $localItems->count(),
            ];
            continue;
        }

        $srv = $serverByInvoice[$inv];
        $localTotal = (float) $sale->total;
        $serverTotal = (float) $srv['total'];

        if (abs($localTotal - $serverTotal) >= 0.01) {
            $localItems = \App\Models\SaleItem::where('sale_id', $sale->id)
                ->select('product_id', 'quantity', 'unit_price', 'total_price', 'cost_at_sale', 'base_quantity')
                ->get();
            $salesComparison['mismatched_total'][] = [
                'invoice_number' => $inv,
                'local_id' => $sale->id,
                'server_id' => $srv['id'],
                'local_total' => $localTotal,
                'server_total' => $serverTotal,
                'local_subtotal' => (float) $sale->subtotal,
                'server_subtotal' => (float) $srv['subtotal'],
                'local_items_count' => $localItems->count(),
                'server_items_count' => $srv['items_count'],
                'local_items' => $localItems->map(fn($i) => [
                    'product_id' => $i->product_id,
                    'qty' => (float) $i->quantity,
                    'price' => (float) $i->unit_price,
                    'total' => (float) $i->total_price,
                    'cost' => (float) $i->cost_at_sale,
                ]),
                'server_items' => $srv['items'],
            ];
        } else {
            $localItemCount = \App\Models\SaleItem::where('sale_id', $sale->id)->count();
            $serverItemCount = $srv['items_count'];
            if ($localItemCount !== $serverItemCount) {
                $salesComparison['mismatched_items'][] = [
                    'invoice_number' => $inv,
                    'total' => $localTotal,
                    'local_items' => $localItemCount,
                    'server_items' => $serverItemCount,
                ];
            }
        }
    }

    foreach ($serverByInvoice as $inv => $srv) {
        if (!$inv) continue;
        if (!$localByInvoice->has($inv)) {
            $salesComparison['only_server'][] = [
                'invoice_number' => $inv,
                'id' => $srv['id'],
                'total' => (float) $srv['total'],
                'status' => $srv['status'],
                'items_count' => $srv['items_count'],
            ];
        }
    }

    $detailConfigs = [
        'purchases' => ['unique' => 'invoice_number', 'amount' => 'total'],
        'sales_returns' => ['unique' => 'return_number', 'amount' => 'total_amount'],
        'expenses' => ['unique' => 'reference_number', 'amount' => 'amount'],
        'inventory_counts' => ['unique' => 'reference_number', 'amount' => null],
    ];

    $otherDetails = [];
    $serverDetails = $server['details'] ?? [];

    foreach ($detailConfigs as $key => $config) {
        $uniqueField = $config['unique'];
        $amountField = $config['amount'];

        try {
            $modelClass = $models[$key];
            $fields = ['id', $uniqueField];
            if ($amountField) $fields[] = $amountField;
            if (\Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), 'status')) {
                $fields[] = 'status';
            }
            $localRecords = $modelClass::select($fields)->orderBy('id')->get();
        } catch (\Exception $e) {
            $otherDetails[$key] = ['error' => $e->getMessage()];
            continue;
        }

        $serverRecords = collect($serverDetails[$key] ?? []);
        $localByUnique = $localRecords->keyBy($uniqueField);
        $serverByUnique = $serverRecords->keyBy($uniqueField);

        $onlyLocal = [];
        $onlyServer = [];
        $mismatched = [];

        foreach ($localByUnique as $uid => $rec) {
            if (!$uid) continue;
            if ($serverByUnique->has($uid)) {
                if ($amountField) {
                    $localAmt = (float) $rec->$amountField;
                    $serverAmt = (float) ($serverByUnique[$uid][$amountField] ?? 0);
                    if (abs($localAmt - $serverAmt) >= 0.01) {
                        $mismatched[] = [$uniqueField => $uid, 'local_amount' => $localAmt, 'server_amount' => $serverAmt];
                    }
                }
            } else {
                $item = [$uniqueField => $uid, 'id' => $rec->id];
                if ($amountField) $item['amount'] = (float) $rec->$amountField;
                $onlyLocal[] = $item;
            }
        }

        foreach ($serverByUnique as $uid => $rec) {
            if (!$uid) continue;
            if (!$localByUnique->has($uid)) {
                $item = [$uniqueField => $uid, 'id' => $rec['id']];
                if ($amountField) $item['amount'] = (float) ($rec[$amountField] ?? 0);
                $onlyServer[] = $item;
            }
        }

        $otherDetails[$key] = ['only_local' => $onlyLocal, 'only_server' => $onlyServer, 'mismatched' => $mismatched];
    }

    $syncLogs = [
        'pending' => \App\Models\SyncLog::where('sync_status', 'pending')->count(),
        'failed' => \App\Models\SyncLog::where('sync_status', 'failed')->count(),
        'conflict' => \App\Models\SyncLog::where('sync_status', 'conflict')->count(),
        'synced' => \App\Models\SyncLog::where('sync_status', 'synced')->count(),
    ];

    return response()->json([
        'financials' => $financialComparison,
        'counts' => $countSummary,
        'sales' => $salesComparison,
        'other' => $otherDetails,
        'sync_logs' => $syncLogs,
    ]);
});

Route::get('/api/sync/cleanup', function () {
    if (!config('desktop.mode')) {
        return response()->json(['error' => 'Desktop mode only']);
    }

    $actions = [];

    $suffixed = \App\Models\Sale::where('invoice_number', 'like', '%-S%')
        ->orWhere('invoice_number', 'like', '%-L%')
        ->get();

    foreach ($suffixed as $sale) {
        $original = preg_replace('/-(S|L)\d+$/', '', $sale->invoice_number);
        $normalExists = \App\Models\Sale::where('invoice_number', $original)->exists();

        if ($normalExists) {
            \App\Models\SaleItem::where('sale_id', $sale->id)->delete();
            \App\Models\SalePayment::where('sale_id', $sale->id)->delete();
            \App\Models\SyncLog::where('syncable_type', 'App\Models\Sale')
                ->where('syncable_id', $sale->id)->delete();
            \App\Models\SyncLog::where('syncable_type', 'App\Models\SaleItem')
                ->whereIn('syncable_id', function ($q) use ($sale) {
                    $q->select('id')->from('sale_items')->where('sale_id', $sale->id);
                })->delete();
            $sale->delete();
            $actions[] = "Deleted duplicate: {$sale->invoice_number} (id:{$sale->id}, total:{$sale->total})";
        } else {
            \Illuminate\Support\Facades\DB::table('sales')
                ->where('id', $sale->id)
                ->update(['invoice_number' => $original]);
            $actions[] = "Renamed: {$sale->invoice_number} → {$original}";
        }
    }

    $serverUrl = config('desktop.server_url');
    $deviceId = config('desktop.device_id');
    $mismatchedRenamed = [];

    try {
        $response = \Illuminate\Support\Facades\Http::withOptions([
            'verify' => base_path('certs/cacert.pem'),
        ])->timeout(15)
            ->withToken(config('desktop.api_token'))
            ->get($serverUrl . '/api/v1/sync/compare');

        if ($response->successful()) {
            $serverSales = collect($response->json('sales'))->keyBy('invoice_number');

            $localSales = \App\Models\Sale::whereNotNull('device_id')
                ->where('invoice_number', 'not like', '%-S%')
                ->where('invoice_number', 'not like', '%-L%')
                ->where('invoice_number', 'not like', 'SAL-D%')
                ->get();

            foreach ($localSales as $sale) {
                if ($serverSales->has($sale->invoice_number)) {
                    $serverTotal = (float) $serverSales[$sale->invoice_number]['total'];
                    $localTotal = (float) $sale->total;

                    if (abs($localTotal - $serverTotal) >= 0.01) {
                        $prefix = 'SAL-D' . date('Ym') . '-';
                        $lastD = \App\Models\Sale::where('invoice_number', 'like', $prefix . '____')
                            ->orderBy('invoice_number', 'desc')
                            ->first();
                        $nextNum = $lastD ? ((int) substr($lastD->invoice_number, -4)) + 1 : 1;
                        $newInvoice = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                        $oldInvoice = $sale->invoice_number;
                        \Illuminate\Support\Facades\DB::table('sales')
                            ->where('id', $sale->id)
                            ->update(['invoice_number' => $newInvoice]);

                        \App\Models\SyncLog::where('syncable_type', 'App\Models\Sale')
                            ->where('syncable_id', $sale->id)
                            ->update(['sync_status' => 'pending', 'error_message' => null]);

                        $actions[] = "Renumbered: {$oldInvoice} → {$newInvoice} (local:{$localTotal}, server:{$serverTotal})";
                        $mismatchedRenamed[] = $newInvoice;
                    }
                }
            }
        }
    } catch (\Exception $e) {
        $actions[] = "Server compare failed: {$e->getMessage()}";
    }

    $failedLogs = \App\Models\SyncLog::where('sync_status', 'failed')->count();
    \App\Models\SyncLog::whereIn('sync_status', ['failed', 'conflict'])->update([
        'sync_status' => 'pending',
        'error_message' => null,
    ]);
    if ($failedLogs > 0) {
        $actions[] = "Reset {$failedLogs} failed logs to pending";
    }

    return response()->json([
        'actions' => $actions,
        'mismatched_renamed' => $mismatchedRenamed,
    ]);
});
