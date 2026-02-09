<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use App\Models\Cashbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftReportController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('role', 'cashier')
            ->orWhere('role', 'manager')
            ->orderBy('name')
            ->get(['id', 'name']);

        $cashboxes = Cashbox::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $terminals = Shift::whereNotNull('terminal_id')
            ->distinct()
            ->pluck('terminal_id');

        return view('shifts.index', compact('users', 'cashboxes', 'terminals'));
    }

    public function filter(Request $request)
    {
        $query = Shift::with(['user:id,name', 'shiftCashboxes.cashbox:id,name'])
            ->select('shifts.*');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('cashbox_id')) {
            $query->whereHas('shiftCashboxes', function ($q) use ($request) {
                $q->where('cashbox_id', $request->cashbox_id);
            });
        }

        if ($request->filled('terminal_id')) {
            $query->where('terminal_id', $request->terminal_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('opened_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('opened_at', '<=', $request->date_to);
        }

        if ($request->filled('has_difference')) {
            if ($request->has_difference == 'yes') {
                $query->whereHas('shiftCashboxes', function ($q) {
                    $q->where('difference', '!=', 0);
                });
            } else {
                $query->whereDoesntHave('shiftCashboxes', function ($q) {
                    $q->where('difference', '!=', 0);
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('terminal_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('shiftCashboxes.cashbox', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $statsQuery = clone $query;
        $stats = [
            'total_shifts' => $statsQuery->count(),
            'open_shifts' => (clone $statsQuery)->where('status', 'open')->count(),
            'total_sales' => (clone $statsQuery)->sum(DB::raw('total_cash_sales + total_card_sales + total_other_sales')),
            'total_difference' => $statsQuery->get()->sum(function ($shift) {
                return $shift->shiftCashboxes->sum('difference');
            }),
        ];

        $sortField = $request->get('sort', 'opened_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['id', 'opened_at', 'closed_at', 'status', 'total_sales', 'difference'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'opened_at';
        }

        if ($sortField == 'total_sales') {
            $query->orderByRaw('(total_cash_sales + total_card_sales + total_other_sales) ' . $sortDirection);
        } elseif ($sortField == 'difference') {
            $query->withSum('shiftCashboxes as total_diff', 'difference')
                ->orderBy('total_diff', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $shifts = $query->paginate(15);

        $shiftsData = $shifts->map(function ($shift) {
            $totalDifference = $shift->shiftCashboxes->sum('difference');
            $cashboxNames = $shift->shiftCashboxes->pluck('cashbox.name')->filter()->implode(', ');

            return [
                'id' => $shift->id,
                'user_name' => $shift->user->name ?? '-',
                'terminal_id' => $shift->terminal_id ?? '-',
                'cashbox_names' => $cashboxNames ?: '-',
                'opened_at' => $shift->opened_at?->format('Y/m/d H:i'),
                'closed_at' => $shift->closed_at?->format('Y/m/d H:i') ?? '-',
                'status' => $shift->status,
                'status_arabic' => $shift->status_arabic,
                'status_color' => $shift->status_color,
                'total_sales' => $shift->total_sales,
                'total_cash' => $shift->total_cash_sales,
                'difference' => $totalDifference,
                'financial_status' => $this->getFinancialStatus($totalDifference),
                'financial_status_color' => $this->getFinancialStatusColor($totalDifference),
            ];
        });

        return response()->json([
            'success' => true,
            'shifts' => $shiftsData,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
                'from' => $shifts->firstItem(),
                'to' => $shifts->lastItem(),
            ],
        ]);
    }

    public function show(Shift $shift)
    {
        $shift->load([
            'user:id,name',
            'forceClosedBy:id,name',
            'approvedBy:id,name',
            'shiftCashboxes.cashbox:id,name,type',
            'sales' => function ($q) {
                $q->with(['customer:id,name', 'payments.paymentMethod:id,name'])
                    ->orderBy('created_at', 'desc');
            },
            'returns' => function ($q) {
                $q->with(['sale:id,invoice_number', 'creator:id,name'])
                    ->orderBy('created_at', 'desc');
            },
            'cashboxTransactions' => function ($q) {
                $q->with(['cashbox:id,name', 'user:id,name'])
                    ->orderBy('created_at', 'desc');
            },
        ]);

        $paymentSummary = [
            'cash' => $shift->total_cash_sales,
            'card' => $shift->total_card_sales,
            'other' => $shift->total_other_sales,
            'total' => $shift->total_sales,
            'refunds' => $shift->total_refunds,
            'net' => $shift->total_sales - $shift->total_refunds,
        ];

        return view('shifts.show', compact('shift', 'paymentSummary'));
    }

    public function export(Request $request)
    {
        $query = Shift::with(['user:id,name', 'shiftCashboxes.cashbox:id,name']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('cashbox_id')) {
            $query->whereHas('shiftCashboxes', function ($q) use ($request) {
                $q->where('cashbox_id', $request->cashbox_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('opened_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('opened_at', '<=', $request->date_to);
        }

        $shifts = $query->orderBy('opened_at', 'desc')->get();

        $data = $shifts->map(function ($shift) {
            $totalDifference = $shift->shiftCashboxes->sum('difference');
            return [
                'رقم الشيفت' => $shift->id,
                'الكاشير' => $shift->user->name ?? '-',
                'الجهاز' => $shift->terminal_id ?? '-',
                'الخزينة' => $shift->shiftCashboxes->pluck('cashbox.name')->implode(', '),
                'تاريخ الفتح' => $shift->opened_at?->format('Y/m/d H:i'),
                'تاريخ الإغلاق' => $shift->closed_at?->format('Y/m/d H:i') ?? '-',
                'الحالة' => $shift->status_arabic,
                'إجمالي المبيعات' => number_format($shift->total_sales, 2),
                'إجمالي الكاش' => number_format($shift->total_cash_sales, 2),
                'الفرق' => number_format($totalDifference, 2),
                'الحالة المالية' => $this->getFinancialStatus($totalDifference),
            ];
        });

        $format = $request->get('format', 'excel');

        if ($format == 'excel') {
            return $this->exportExcel($data);
        }

        return $this->exportPdf($data, $request);
    }

    private function exportExcel($data)
    {
        $filename = 'shifts_report_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($data->isNotEmpty()) {
                fputcsv($file, array_keys($data->first()));
            }

            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportPdf($data, $request)
    {
        $stats = [
            'total_shifts' => $data->count(),
            'total_sales' => $data->sum(fn($row) => floatval(str_replace(',', '', $row['إجمالي المبيعات']))),
            'total_difference' => $data->sum(fn($row) => floatval(str_replace(',', '', $row['الفرق']))),
        ];

        $html = view('shifts.print', [
            'shifts' => $data,
            'stats' => $stats,
            'filters' => $request->only(['user_id', 'cashbox_id', 'status', 'date_from', 'date_to']),
        ])->render();

        return response($html)->header('Content-Type', 'text/html');
    }

    private function getFinancialStatus(float $difference): string
    {
        if ($difference == 0) {
            return 'متوازن';
        } elseif ($difference > 0) {
            return 'زيادة';
        } else {
            return 'عجز';
        }
    }

    private function getFinancialStatusColor(float $difference): string
    {
        if ($difference == 0) {
            return 'success';
        } elseif ($difference > 0) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
}
