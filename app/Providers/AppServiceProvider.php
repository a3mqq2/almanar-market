<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SalesReturn;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Observers\ExpenseObserver;
use App\Observers\SaleItemObserver;
use App\Observers\SaleObserver;
use App\Observers\SalePaymentObserver;
use App\Observers\SalesReturnObserver;
use App\Observers\ShiftObserver;
use App\Observers\StockMovementObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if (config('desktop.mode')) {
            Sale::observe(SaleObserver::class);
            SaleItem::observe(SaleItemObserver::class);
            SalePayment::observe(SalePaymentObserver::class);
            Shift::observe(ShiftObserver::class);
            Expense::observe(ExpenseObserver::class);
            StockMovement::observe(StockMovementObserver::class);
            SalesReturn::observe(SalesReturnObserver::class);
        }
    }
}
