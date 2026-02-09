<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\PaymentMethod;
use App\Models\SalePayment;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        $shiftId = $request->get('shift_id');
        $cashboxId = $request->get('cashbox_id');

        $shifts = Shift::whereDate('opened_at', $date)
            ->with('user:id,name')
            ->orderBy('opened_at', 'desc')
            ->get();

        $cashboxes = Cashbox::active()->with('paymentMethod')->orderBy('name')->get();
        $paymentMethods = PaymentMethod::active()->with('cashbox')->orderBy('sort_order')->get();

        $reportData = null;
        if ($request->has('generate')) {
            $reportData = $this->generateReconciliationData($date, $shiftId, $cashboxId);
        }

        return view('reports.payment-reconciliation', compact(
            'date',
            'shiftId',
            'cashboxId',
            'shifts',
            'cashboxes',
            'paymentMethods',
            'reportData'
        ));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'cashbox_id' => 'nullable|exists:cashboxes,id',
        ]);

        $reportData = $this->generateReconciliationData(
            $validated['date'],
            $validated['shift_id'] ?? null,
            $validated['cashbox_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $reportData,
        ]);
    }

    protected function generateReconciliationData(string $date, ?int $shiftId = null, ?int $cashboxId = null): array
    {
        $data = [
            'by_payment_method' => $this->getByPaymentMethod($date, $shiftId),
            'by_cashbox' => $this->getByCashbox($date, $shiftId, $cashboxId),
            'shift_summary' => $shiftId ? $this->getShiftSummary($shiftId) : null,
            'reconciliation' => $this->getReconciliation($date, $shiftId, $cashboxId),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        return $data;
    }

    protected function getByPaymentMethod(string $date, ?int $shiftId = null): array
    {
        $query = SalePayment::whereHas('sale', function ($q) use ($date, $shiftId) {
            $q->whereDate('sale_date', $date)
                ->where('status', 'completed');

            if ($shiftId) {
                $q->where('shift_id', $shiftId);
            }
        });

        $payments = $query->select(
            'payment_method_id',
            'cashbox_id',
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(*) as transaction_count')
        )
            ->groupBy('payment_method_id', 'cashbox_id')
            ->with(['paymentMethod:id,name,code,cashbox_id', 'paymentMethod.cashbox:id,name,type', 'cashbox:id,name,type'])
            ->get();

        $allMethods = PaymentMethod::active()->with('cashbox:id,name,type')->orderBy('sort_order')->get();

        return $allMethods->map(function ($method) use ($payments) {
            $methodPayments = $payments->where('payment_method_id', $method->id);
            $totalAmount = $methodPayments->sum('total_amount');
            $transactionCount = $methodPayments->sum('transaction_count');

            $cashboxBreakdown = $methodPayments->map(function ($p) {
                return [
                    'cashbox_id' => $p->cashbox_id,
                    'cashbox_name' => $p->cashbox->name ?? 'غير محدد',
                    'cashbox_type' => $p->cashbox->type ?? null,
                    'amount' => round($p->total_amount, 2),
                    'count' => $p->transaction_count,
                ];
            })->values()->toArray();

            return [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'linked_cashbox' => $method->cashbox ? [
                    'id' => $method->cashbox->id,
                    'name' => $method->cashbox->name,
                    'type' => $method->cashbox->type,
                ] : null,
                'total_amount' => round($totalAmount, 2),
                'transaction_count' => $transactionCount,
                'cashbox_breakdown' => $cashboxBreakdown,
            ];
        })->toArray();
    }

    protected function getByCashbox(string $date, ?int $shiftId = null, ?int $cashboxId = null): array
    {
        $query = CashboxTransaction::whereDate('transaction_date', $date);

        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        if ($cashboxId) {
            $query->where('cashbox_id', $cashboxId);
        }

        $transactions = $query->select(
            'cashbox_id',
            'payment_method_id',
            'type',
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(*) as transaction_count')
        )
            ->groupBy('cashbox_id', 'payment_method_id', 'type')
            ->with(['cashbox:id,name,type,current_balance', 'paymentMethod:id,name,code'])
            ->get();

        $cashboxes = Cashbox::active()->with('paymentMethod:id,name')->get();

        if ($cashboxId) {
            $cashboxes = $cashboxes->where('id', $cashboxId);
        }

        return $cashboxes->map(function ($cashbox) use ($transactions) {
            $cashboxTransactions = $transactions->where('cashbox_id', $cashbox->id);

            $totalIn = $cashboxTransactions->where('type', 'in')->sum('total_amount');
            $totalOut = $cashboxTransactions->where('type', 'out')->sum('total_amount');
            $netChange = $totalIn - $totalOut;

            $paymentBreakdown = $cashboxTransactions->groupBy('payment_method_id')->map(function ($group, $methodId) {
                $first = $group->first();
                $totalIn = $group->where('type', 'in')->sum('total_amount');
                $totalOut = $group->where('type', 'out')->sum('total_amount');

                return [
                    'payment_method_id' => $methodId,
                    'payment_method_name' => $first->paymentMethod->name ?? 'غير محدد',
                    'total_in' => round($totalIn, 2),
                    'total_out' => round($totalOut, 2),
                    'net' => round($totalIn - $totalOut, 2),
                    'count' => $group->sum('transaction_count'),
                ];
            })->values()->toArray();

            return [
                'id' => $cashbox->id,
                'name' => $cashbox->name,
                'type' => $cashbox->type,
                'type_arabic' => $this->getTypeArabic($cashbox->type),
                'current_balance' => round($cashbox->current_balance, 2),
                'linked_payment_method' => $cashbox->paymentMethod ? [
                    'id' => $cashbox->paymentMethod->id,
                    'name' => $cashbox->paymentMethod->name,
                ] : null,
                'total_in' => round($totalIn, 2),
                'total_out' => round($totalOut, 2),
                'net_change' => round($netChange, 2),
                'payment_breakdown' => $paymentBreakdown,
            ];
        })->values()->toArray();
    }

    protected function getShiftSummary(int $shiftId): ?array
    {
        $shift = Shift::with(['user:id,name', 'shiftCashboxes.cashbox:id,name,type', 'sales' => function ($q) {
            $q->where('status', 'completed');
        }])->find($shiftId);

        if (!$shift) {
            return null;
        }

        $saleIds = $shift->sales->pluck('id');

        $paymentTotals = SalePayment::whereIn('sale_id', $saleIds)
            ->select(
                'payment_method_id',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('payment_method_id')
            ->with('paymentMethod:id,name,code')
            ->get();

        $cashboxTotals = CashboxTransaction::where('shift_id', $shiftId)
            ->where('type', 'in')
            ->select(
                'cashbox_id',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('cashbox_id')
            ->with('cashbox:id,name,type')
            ->get();

        return [
            'shift_id' => $shift->id,
            'cashier' => $shift->user->name ?? '-',
            'opened_at' => $shift->opened_at?->format('Y-m-d H:i'),
            'closed_at' => $shift->closed_at?->format('Y-m-d H:i'),
            'status' => $shift->status,
            'opening_cash' => round($shift->total_opening_balance, 2),
            'closing_cash' => round($shift->total_closing_balance, 2),
            'expected_cash' => round($shift->total_expected_balance, 2),
            'difference' => round($shift->total_difference, 2),
            'total_sales' => round($shift->total_sales, 2),
            'sales_count' => $shift->sales_count,
            'cashboxes' => $shift->shiftCashboxes->map(function ($sc) {
                return [
                    'id' => $sc->cashbox_id,
                    'name' => $sc->cashbox->name ?? '-',
                    'type' => $sc->cashbox->type ?? '-',
                    'opening_balance' => round($sc->opening_balance, 2),
                    'closing_balance' => round($sc->closing_balance ?? 0, 2),
                    'expected_balance' => round($sc->expected_balance, 2),
                    'difference' => round($sc->difference, 2),
                ];
            })->values()->toArray(),
            'payment_totals' => $paymentTotals->map(function ($p) {
                return [
                    'payment_method_id' => $p->payment_method_id,
                    'name' => $p->paymentMethod->name ?? '-',
                    'code' => $p->paymentMethod->code ?? '-',
                    'total' => round($p->total, 2),
                ];
            })->values()->toArray(),
            'cashbox_totals' => $cashboxTotals->map(function ($c) {
                return [
                    'cashbox_id' => $c->cashbox_id,
                    'name' => $c->cashbox->name ?? '-',
                    'type' => $c->cashbox->type ?? '-',
                    'total' => round($c->total, 2),
                ];
            })->values()->toArray(),
        ];
    }

    protected function getReconciliation(string $date, ?int $shiftId = null, ?int $cashboxId = null): array
    {
        // Get sale payments grouped by payment method
        $salePaymentsQuery = SalePayment::whereHas('sale', function ($q) use ($date, $shiftId) {
            $q->whereDate('sale_date', $date)
                ->where('status', 'completed');

            if ($shiftId) {
                $q->where('shift_id', $shiftId);
            }
        });

        if ($cashboxId) {
            $salePaymentsQuery->where('cashbox_id', $cashboxId);
        }

        $salePayments = $salePaymentsQuery->select(
            'payment_method_id',
            'cashbox_id',
            DB::raw('SUM(amount) as expected_amount')
        )
            ->groupBy('payment_method_id', 'cashbox_id')
            ->get();

        // Get actual cashbox transactions
        $transactionsQuery = CashboxTransaction::whereDate('transaction_date', $date)
            ->where('type', 'in');

        if ($shiftId) {
            $transactionsQuery->where('shift_id', $shiftId);
        }

        if ($cashboxId) {
            $transactionsQuery->where('cashbox_id', $cashboxId);
        }

        $actualTransactions = $transactionsQuery->select(
            'cashbox_id',
            'payment_method_id',
            DB::raw('SUM(amount) as actual_amount')
        )
            ->groupBy('cashbox_id', 'payment_method_id')
            ->get();

        // Build reconciliation report
        $reconciliation = [];

        $paymentMethods = PaymentMethod::active()->with('cashbox')->get();
        $cashboxes = Cashbox::active()->get();

        foreach ($paymentMethods as $method) {
            $expectedByCashbox = $salePayments
                ->where('payment_method_id', $method->id)
                ->keyBy('cashbox_id');

            $actualByCashbox = $actualTransactions
                ->where('payment_method_id', $method->id)
                ->keyBy('cashbox_id');

            $allCashboxIds = collect($expectedByCashbox->keys())
                ->merge($actualByCashbox->keys())
                ->unique();

            foreach ($allCashboxIds as $cid) {
                $cashbox = $cashboxes->find($cid);
                if (!$cashbox) continue;

                $expected = round($expectedByCashbox->get($cid)?->expected_amount ?? 0, 2);
                $actual = round($actualByCashbox->get($cid)?->actual_amount ?? 0, 2);
                $difference = round($actual - $expected, 2);

                $status = 'balanced';
                if ($difference > 0.01) {
                    $status = 'surplus';
                } elseif ($difference < -0.01) {
                    $status = 'shortage';
                }

                $reconciliation[] = [
                    'payment_method_id' => $method->id,
                    'payment_method_name' => $method->name,
                    'cashbox_id' => $cid,
                    'cashbox_name' => $cashbox->name,
                    'cashbox_type' => $cashbox->type,
                    'expected' => $expected,
                    'actual' => $actual,
                    'difference' => $difference,
                    'status' => $status,
                    'is_linked' => $method->cashbox_id == $cid,
                ];
            }
        }

        return $reconciliation;
    }

    protected function getTypeArabic(?string $type): string
    {
        return match ($type) {
            'cash' => 'نقدي',
            'card' => 'بطاقة',
            'wallet' => 'محفظة',
            'bank' => 'مصرفي',
            default => 'نقدي',
        };
    }
}
