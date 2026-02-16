<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('device')->group(function () {
        Route::post('/register', [DeviceController::class, 'register']);
        Route::post('/activate', [DeviceController::class, 'activate']);
    });

    Route::get('/sync/timestamp', [SyncController::class, 'timestamp']);

    Route::prefix('sync')->middleware('verify.device.token')->group(function () {
        Route::post('/push', [SyncController::class, 'push']);
        Route::get('/pull', [SyncController::class, 'pull']);
        Route::get('/status', [SyncController::class, 'status']);
        Route::post('/resolve-conflict', [SyncController::class, 'resolveConflict']);
        Route::post('/retry-failed', [SyncController::class, 'retryFailed']);
    });

    Route::middleware('verify.device.token')->group(function () {
        Route::get('/device/status', [DeviceController::class, 'status']);
        Route::post('/device/heartbeat', [DeviceController::class, 'heartbeat']);
    });

    Route::post('/sync/repair-items', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'sales' => 'required|array',
            'sales.*.invoice_number' => 'required|string',
        ]);

        $actions = [];
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($request->input('sales') as $saleData) {
            $sale = \App\Models\Sale::where('invoice_number', $saleData['invoice_number'])->first();
            if (!$sale) {
                $actions[] = "NOT FOUND: {$saleData['invoice_number']}";
                continue;
            }

            $itemsAdded = 0;
            if (!empty($saleData['items'])) {
                $existingItems = \App\Models\SaleItem::where('sale_id', $sale->id)->count();
                if ($existingItems === 0) {
                    foreach ($saleData['items'] as $itemData) {
                        $itemData['sale_id'] = $sale->id;
                        unset($itemData['id'], $itemData['created_at'], $itemData['updated_at'], $itemData['synced_at'], $itemData['local_uuid'], $itemData['device_id']);
                        \App\Models\SaleItem::create($itemData);
                        $itemsAdded++;
                    }
                }
            }

            $paymentsAdded = 0;
            if (!empty($saleData['payments'])) {
                $existingPayments = \App\Models\SalePayment::where('sale_id', $sale->id)->count();
                if ($existingPayments === 0) {
                    foreach ($saleData['payments'] as $payData) {
                        $payData['sale_id'] = $sale->id;
                        unset($payData['id'], $payData['created_at'], $payData['updated_at'], $payData['synced_at'], $payData['local_uuid'], $payData['device_id']);
                        \App\Models\SalePayment::create($payData);
                        $paymentsAdded++;
                    }
                }
            }

            if ($itemsAdded > 0 || $paymentsAdded > 0) {
                $actions[] = "FIXED: {$saleData['invoice_number']} (id:{$sale->id}) → items:{$itemsAdded}, payments:{$paymentsAdded}";
            } else {
                $existingItems = \App\Models\SaleItem::where('sale_id', $sale->id)->count();
                $existingPayments = \App\Models\SalePayment::where('sale_id', $sale->id)->count();
                $actions[] = "SKIP: {$saleData['invoice_number']} already has items:{$existingItems}, payments:{$existingPayments}";
            }
        }

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'success' => true,
            'actions' => $actions,
            'total_items' => \App\Models\SaleItem::count(),
            'total_payments' => \App\Models\SalePayment::count(),
        ]);
    });

    Route::post('/sync/cancel-empty-sales', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'invoice_numbers' => 'required|array',
            'invoice_numbers.*' => 'string',
        ]);

        $cancelled = [];
        foreach ($request->input('invoice_numbers') as $inv) {
            $sale = \App\Models\Sale::where('invoice_number', $inv)->first();
            if ($sale && \App\Models\SaleItem::where('sale_id', $sale->id)->count() === 0) {
                $sale->update(['status' => 'cancelled']);
                $cancelled[] = $inv;
            }
        }

        return response()->json([
            'success' => true,
            'cancelled' => $cancelled,
            'count' => count($cancelled),
        ]);
    });

    Route::get('/sync/cleanup', function () {
        $actions = [];

        $suffixed = \App\Models\Sale::where('invoice_number', 'like', '%-S%')->get();
        foreach ($suffixed as $sale) {
            $itemCount = \App\Models\SaleItem::where('sale_id', $sale->id)->count();
            \App\Models\SaleItem::where('sale_id', $sale->id)->delete();
            \App\Models\SalePayment::where('sale_id', $sale->id)->delete();
            $sale->delete();
            $actions[] = "Deleted junk: {$sale->invoice_number} (id:{$sale->id}, items:{$itemCount})";
        }

        $drafts = \App\Models\Sale::where('status', 'draft')
            ->where('total', 0)
            ->whereNotNull('device_id')
            ->get();
        foreach ($drafts as $sale) {
            $itemCount = \App\Models\SaleItem::where('sale_id', $sale->id)->count();
            if ($itemCount === 0) {
                \App\Models\SalePayment::where('sale_id', $sale->id)->delete();
                $sale->delete();
                $actions[] = "Deleted empty draft: {$sale->invoice_number} (id:{$sale->id})";
            }
        }

        $validSaleIds = \App\Models\Sale::pluck('id')->toArray();
        $orphanedItems = \App\Models\SaleItem::whereNotIn('sale_id', $validSaleIds)->get();
        $orphanedCount = $orphanedItems->count();
        if ($orphanedCount > 0) {
            \App\Models\SaleItem::whereNotIn('sale_id', $validSaleIds)->delete();
            $actions[] = "Deleted {$orphanedCount} orphaned sale_items (invalid sale_id)";
        }

        $orphanedPayments = \App\Models\SalePayment::whereNotIn('sale_id', $validSaleIds)->count();
        if ($orphanedPayments > 0) {
            \App\Models\SalePayment::whereNotIn('sale_id', $validSaleIds)->delete();
            $actions[] = "Deleted {$orphanedPayments} orphaned sale_payments";
        }

        $salesWithNoItems = \App\Models\Sale::whereNotNull('device_id')
            ->where('total', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->filter(fn($s) => \App\Models\SaleItem::where('sale_id', $s->id)->count() === 0);
        if ($salesWithNoItems->count() > 0) {
            $actions[] = "WARNING: {$salesWithNoItems->count()} device sales have total > 0 but 0 items: " .
                $salesWithNoItems->pluck('invoice_number')->join(', ');
        }

        return response()->json([
            'actions' => $actions,
            'remaining_sales' => \App\Models\Sale::count(),
            'remaining_items' => \App\Models\SaleItem::count(),
        ]);
    });

    Route::get('/sync/compare', function () {
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

        $counts = [];
        foreach ($models as $key => $modelClass) {
            try {
                $counts[$key] = $modelClass::count();
            } catch (\Exception $e) {
                $counts[$key] = 'error: ' . $e->getMessage();
            }
        }

        $sales = \App\Models\Sale::select('id', 'invoice_number', 'total', 'subtotal', 'discount_amount', 'tax_amount', 'status', 'local_uuid', 'device_id')
            ->orderBy('id')
            ->get()
            ->map(function ($s) {
                $items = \App\Models\SaleItem::where('sale_id', $s->id)
                    ->select('id', 'product_id', 'quantity', 'unit_price', 'total_price', 'cost_at_sale', 'base_quantity')
                    ->get();
                $paymentsCount = \App\Models\SalePayment::where('sale_id', $s->id)->count();
                return [
                    'id' => $s->id,
                    'invoice_number' => $s->invoice_number,
                    'total' => (float) $s->total,
                    'subtotal' => (float) $s->subtotal,
                    'discount_amount' => (float) $s->discount_amount,
                    'tax_amount' => (float) $s->tax_amount,
                    'status' => $s->status,
                    'local_uuid' => $s->local_uuid,
                    'device_id' => $s->device_id,
                    'items_count' => $items->count(),
                    'payments_count' => $paymentsCount,
                    'items_total' => (float) $items->sum('total_price'),
                    'items_cost' => (float) $items->sum(fn($i) => $i->cost_at_sale * $i->base_quantity),
                    'items' => $items->map(fn($i) => [
                        'id' => $i->id,
                        'product_id' => $i->product_id,
                        'qty' => (float) $i->quantity,
                        'price' => (float) $i->unit_price,
                        'total' => (float) $i->total_price,
                        'cost' => (float) $i->cost_at_sale,
                    ]),
                ];
            });

        $financials = [
            'total_sales' => (float) \App\Models\Sale::where('status', '!=', 'cancelled')->sum('total'),
            'total_cost' => (float) \Illuminate\Support\Facades\DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.status', '!=', 'cancelled')
                ->selectRaw('SUM(sale_items.cost_at_sale * sale_items.base_quantity) as total')
                ->value('total'),
            'total_profit' => 0,
            'total_expenses' => (float) \App\Models\Expense::sum('amount'),
            'total_purchases' => (float) \App\Models\Purchase::sum('total'),
        ];
        $financials['total_profit'] = $financials['total_sales'] - $financials['total_cost'];

        $detailModels = [
            'purchases' => ['class' => \App\Models\Purchase::class, 'unique' => 'invoice_number', 'fields' => ['id', 'invoice_number', 'total', 'status', 'local_uuid', 'device_id']],
            'sales_returns' => ['class' => \App\Models\SalesReturn::class, 'unique' => 'return_number', 'fields' => ['id', 'return_number', 'total_amount', 'status', 'local_uuid', 'device_id']],
            'expenses' => ['class' => \App\Models\Expense::class, 'unique' => 'reference_number', 'fields' => ['id', 'reference_number', 'amount', 'local_uuid', 'device_id']],
            'inventory_counts' => ['class' => \App\Models\InventoryCount::class, 'unique' => 'reference_number', 'fields' => ['id', 'reference_number', 'status', 'local_uuid', 'device_id']],
        ];

        $details = [];
        foreach ($detailModels as $key => $config) {
            try {
                $details[$key] = $config['class']::select($config['fields'])
                    ->orderBy('id')
                    ->get()
                    ->map(function ($r) use ($config) {
                        $arr = [];
                        foreach ($config['fields'] as $f) {
                            $arr[$f] = $r->$f;
                        }
                        return $arr;
                    });
            } catch (\Exception $e) {
                $details[$key] = [];
            }
        }

        return response()->json([
            'counts' => $counts,
            'financials' => $financials,
            'sales' => $sales,
            'details' => $details,
        ]);
    });
});
