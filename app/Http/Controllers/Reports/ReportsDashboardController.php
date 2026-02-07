<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\Shift;
use App\Models\User;

class ReportsDashboardController extends Controller
{
    public function index()
    {
        $cashiers = User::whereIn('role', ['cashier', 'manager'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $shifts = Shift::with('user:id,name')
            ->orderBy('opened_at', 'desc')
            ->limit(100)
            ->get(['id', 'opened_at', 'closed_at', 'user_id', 'status']);

        $cashboxes = Cashbox::active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = PaymentMethod::active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code']);

        return view('reports.dashboard', compact(
            'cashiers',
            'shifts',
            'cashboxes',
            'paymentMethods'
        ));
    }
}
