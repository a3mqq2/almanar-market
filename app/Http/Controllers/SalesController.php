<?php

namespace App\Http\Controllers;

use App\Models\Sale;
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

        return view('sales.index', compact('stats'));
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

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
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

        $perPage = $request->get('per_page', 20);
        $sales = $query->paginate($perPage);

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
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
                'from' => $sales->firstItem(),
                'to' => $sales->lastItem(),
            ],
            'summary' => [
                'total_sales' => $query->count(),
                'total_amount' => Sale::whereIn('id', $sales->pluck('id'))->sum('total'),
                'total_paid' => Sale::whereIn('id', $sales->pluck('id'))->sum('paid_amount'),
                'total_credit' => Sale::whereIn('id', $sales->pluck('id'))->sum('credit_amount'),
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
