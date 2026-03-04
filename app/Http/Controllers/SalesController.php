<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getSalesData($request);
        }

        $stats = [
            'today_count' => Sale::completed()->today()->count(),
            'today_total' => Sale::completed()->today()->sum('total'),
            'today_credit' => Sale::completed()->today()->sum('credit_amount'),
            'today_cash' => Sale::completed()->today()->sum('paid_amount'),
        ];

        $cashierIds = Sale::whereNotNull('cashier_id')->distinct()->pluck('cashier_id');
        $cashiers = User::whereIn('id', $cashierIds)->orderBy('name')->get(['id', 'name']);

        return view('sales.index', compact('stats', 'cashiers'));
    }

    protected function getSalesData(Request $request)
    {
        $query = Sale::with(['customer', 'cashier'])
            ->whereIn('status', ['completed', 'cancelled']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cashier_id')) {
            $query->where('cashier_id', $request->cashier_id);
        }

        if ($request->filled('payment_method')) {
            $method = $request->payment_method;
            if ($method === 'credit') {
                $query->where('payment_status', 'credit');
            } elseif ($method === 'cash') {
                $query->whereHas('payments', function ($q) {
                    $q->whereHas('paymentMethod', fn($q) => $q->where('code', 'cash'));
                });
            } elseif ($method === 'bank') {
                $query->whereHas('payments', function ($q) {
                    $q->whereHas('paymentMethod', fn($q) => $q->where('code', '!=', 'cash'));
                });
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $allowedSorts = ['invoice_number', 'sale_date', 'total', 'status', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        $sales = $query->get();
        $saleIds = $sales->pluck('id');

        $cashTotal = SalePayment::whereIn('sale_id', $saleIds)
            ->whereHas('paymentMethod', fn($q) => $q->where('code', 'cash'))
            ->sum('amount');

        $bankTotal = SalePayment::whereIn('sale_id', $saleIds)
            ->whereHas('paymentMethod', fn($q) => $q->where('code', '!=', 'cash'))
            ->sum('amount');

        $data = $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'sale_date' => $sale->sale_date->format('Y-m-d'),
                'customer_name' => $sale->customer?->name ?? 'زبون عادي',
                'cashier_name' => $sale->cashier?->name ?? '-',
                'subtotal' => $sale->subtotal,
                'discount_amount' => $sale->discount_amount,
                'total' => $sale->total,
                'paid_amount' => $sale->paid_amount,
                'credit_amount' => $sale->credit_amount,
                'status' => $sale->status,
                'status_arabic' => $sale->status_arabic,
                'status_color' => $sale->status_color,
                'payment_status' => $sale->payment_status,
                'payment_status_arabic' => $sale->payment_status_arabic,
                'created_at' => $sale->created_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'data' => $data,
            'summary' => [
                'total_sales' => $sales->count(),
                'total_amount' => $sales->sum('total'),
                'total_paid' => $sales->sum('paid_amount'),
                'total_credit' => $sales->sum('credit_amount'),
                'total_cash' => $cashTotal,
                'total_bank' => $bankTotal,
            ],
        ]);
    }

    public function show(Sale $sale)
    {
        $sale->load([
            'items.product',
            'items.productUnit.unit',
            'payments.paymentMethod',
            'payments.cashbox',
            'customer',
            'cashier',
            'creator',
            'canceller',
        ]);

        return view('sales.show', compact('sale'));
    }

    public function print(Sale $sale)
    {
        $sale->load([
            'items.product',
            'items.productUnit.unit',
            'payments.paymentMethod',
            'customer',
            'cashier',
        ]);

        return view('sales.print', compact('sale'));
    }

    public function printList(Request $request)
    {
        $query = Sale::with(['customer', 'cashier'])
            ->whereIn('status', ['completed', 'cancelled']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cashier_id')) {
            $query->where('cashier_id', $request->cashier_id);
        }

        if ($request->filled('payment_method')) {
            $method = $request->payment_method;
            if ($method === 'credit') {
                $query->where('payment_status', 'credit');
            } elseif ($method === 'cash') {
                $query->whereHas('payments', function ($q) {
                    $q->whereHas('paymentMethod', fn($q) => $q->where('code', 'cash'));
                });
            } elseif ($method === 'bank') {
                $query->whereHas('payments', function ($q) {
                    $q->whereHas('paymentMethod', fn($q) => $q->where('code', '!=', 'cash'));
                });
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        $query->latest();
        $sales = $query->get();
        $saleIds = $sales->pluck('id');

        $cashTotal = SalePayment::whereIn('sale_id', $saleIds)
            ->whereHas('paymentMethod', fn($q) => $q->where('code', 'cash'))
            ->sum('amount');

        $bankTotal = SalePayment::whereIn('sale_id', $saleIds)
            ->whereHas('paymentMethod', fn($q) => $q->where('code', '!=', 'cash'))
            ->sum('amount');

        $summary = [
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total'),
            'total_credit' => $sales->sum('credit_amount'),
            'total_cash' => $cashTotal,
            'total_bank' => $bankTotal,
        ];

        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'cashier' => $request->filled('cashier_id') ? User::find($request->cashier_id)?->name : null,
            'payment_method' => $request->payment_method,
            'status' => $request->status,
        ];

        return view('sales.print-list', compact('sales', 'summary', 'filters'));
    }

    public function printThermal(Sale $sale)
    {
        $sale->load([
            'items.product',
            'items.productUnit.unit',
            'payments.paymentMethod',
            'customer',
            'cashier',
        ]);

        return view('pos.print-receipt', compact('sale'));
    }
}
