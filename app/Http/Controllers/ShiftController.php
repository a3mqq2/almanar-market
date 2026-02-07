<?php

namespace App\Http\Controllers;

use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\Shift;
use App\Models\ShiftCashbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function current()
    {
        $userId = Auth::id();
        $shift = Shift::getOpenShift($userId);

        if (!$shift) {
            return response()->json([
                'success' => false,
                'has_shift' => false,
                'message' => 'لا يوجد وردية مفتوح',
            ]);
        }

        $shift->load(['shiftCashboxes.cashbox:id,name,type', 'user:id,name']);
        $shift->recalculateTotals();
        $shift->save();

        return response()->json([
            'success' => true,
            'has_shift' => true,
            'shift' => [
                'id' => $shift->id,
                'user_name' => $shift->user->name,
                'terminal_id' => $shift->terminal_id,
                'total_cash_sales' => $shift->total_cash_sales,
                'total_card_sales' => $shift->total_card_sales,
                'total_other_sales' => $shift->total_other_sales,
                'total_refunds' => $shift->total_refunds,
                'total_expenses' => $shift->total_expenses,
                'total_deposits' => $shift->total_deposits,
                'total_withdrawals' => $shift->total_withdrawals,
                'sales_count' => $shift->sales_count,
                'refunds_count' => $shift->refunds_count,
                'total_opening_balance' => $shift->total_opening_balance,
                'total_expected_balance' => $shift->total_expected_balance,
                'opened_at' => $shift->opened_at->format('Y-m-d H:i'),
                'status' => $shift->status,
                'cashboxes' => $shift->shiftCashboxes->map(function ($sc) {
                    return [
                        'id' => $sc->cashbox_id,
                        'name' => $sc->cashbox->name,
                        'type' => $sc->cashbox->type,
                        'opening_balance' => round($sc->opening_balance, 2),
                        'expected_balance' => round($sc->expected_balance, 2),
                        'total_in' => round($sc->total_in, 2),
                        'total_out' => round($sc->total_out, 2),
                    ];
                }),
            ],
        ]);
    }

    public function open(Request $request)
    {
        $validated = $request->validate([
            'cashboxes' => 'required|array|min:1',
            'cashboxes.*.cashbox_id' => 'required|exists:cashboxes,id',
            'cashboxes.*.opening_balance' => 'required|numeric|min:0',
            'terminal_id' => 'nullable|string|max:50',
        ]);

        $userId = Auth::id();
        $terminalId = $validated['terminal_id'] ?? session()->getId();

        if (Shift::hasOpenShift($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'لديك وردية مفتوح بالفعل',
            ], 422);
        }

        $existingTerminalShift = Shift::open()
            ->where('terminal_id', $terminalId)
            ->where('user_id', '!=', $userId)
            ->first();

        if ($existingTerminalShift) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الجهاز مستخدم من قبل ' . $existingTerminalShift->user->name,
            ], 422);
        }

        $cashboxIds = collect($validated['cashboxes'])->pluck('cashbox_id');
        $duplicates = $cashboxIds->duplicates();
        if ($duplicates->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تكرار نفس الصندوق',
            ], 422);
        }

        $cashboxes = Cashbox::whereIn('id', $cashboxIds)->where('status', true)->get();
        if ($cashboxes->count() !== $cashboxIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'بعض الصناديق غير موجودة أو غير نشطة',
            ], 422);
        }

        $user = Auth::user();
        foreach ($cashboxIds as $cashboxId) {
            if (!$user->canAccessCashbox($cashboxId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية الوصول لبعض الصناديق المحددة',
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            $shift = Shift::create([
                'user_id' => $userId,
                'terminal_id' => $terminalId,
                'opened_at' => now(),
                'status' => 'open',
            ]);

            $cashboxData = [];
            foreach ($validated['cashboxes'] as $cb) {
                $cashbox = $cashboxes->find($cb['cashbox_id']);
                $shiftCashbox = ShiftCashbox::create([
                    'shift_id' => $shift->id,
                    'cashbox_id' => $cb['cashbox_id'],
                    'opening_balance' => $cb['opening_balance'],
                    'expected_balance' => $cb['opening_balance'],
                ]);
                $cashboxData[] = [
                    'id' => $cashbox->id,
                    'name' => $cashbox->name,
                    'opening_balance' => round($cb['opening_balance'], 2),
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم فتح الوردية بنجاح',
                'shift' => [
                    'id' => $shift->id,
                    'opened_at' => $shift->opened_at->format('Y-m-d H:i'),
                    'cashboxes' => $cashboxData,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function addCashbox(Request $request, Shift $shift)
    {
        if ($shift->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'الوردية مغلقة',
            ], 422);
        }

        if ($shift->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعديل وردية مستخدم آخر',
            ], 403);
        }

        $validated = $request->validate([
            'cashbox_id' => 'required|exists:cashboxes,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        if ($shift->hasCashbox($validated['cashbox_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الصندوق موجود بالفعل في الوردية',
            ], 422);
        }

        $cashbox = Cashbox::where('id', $validated['cashbox_id'])->where('status', true)->first();
        if (!$cashbox) {
            return response()->json([
                'success' => false,
                'message' => 'الصندوق غير موجود أو غير نشط',
            ], 422);
        }

        if (!Auth::user()->canAccessCashbox($validated['cashbox_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية الوصول لهذا الصندوق',
            ], 403);
        }

        ShiftCashbox::create([
            'shift_id' => $shift->id,
            'cashbox_id' => $validated['cashbox_id'],
            'opening_balance' => $validated['opening_balance'],
            'expected_balance' => $validated['opening_balance'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الصندوق للوردية',
            'cashbox' => [
                'id' => $cashbox->id,
                'name' => $cashbox->name,
                'opening_balance' => round($validated['opening_balance'], 2),
            ],
        ]);
    }

    public function summary(Shift $shift)
    {
        $shift->load(['shiftCashboxes.cashbox:id,name,type', 'user:id,name']);
        $shift->recalculateTotals();
        $shift->save();

        return response()->json([
            'success' => true,
            'summary' => [
                'id' => $shift->id,
                'user_name' => $shift->user->name,
                'terminal_id' => $shift->terminal_id,
                'opened_at' => $shift->opened_at->format('Y-m-d H:i'),
                'total_cash_sales' => round($shift->total_cash_sales, 2),
                'total_card_sales' => round($shift->total_card_sales, 2),
                'total_other_sales' => round($shift->total_other_sales, 2),
                'total_sales' => round($shift->total_sales, 2),
                'sales_count' => $shift->sales_count,
                'total_refunds' => round($shift->total_refunds, 2),
                'refunds_count' => $shift->refunds_count,
                'total_expenses' => round($shift->total_expenses, 2),
                'total_deposits' => round($shift->total_deposits, 2),
                'total_withdrawals' => round($shift->total_withdrawals, 2),
                'total_opening_balance' => round($shift->total_opening_balance, 2),
                'total_expected_balance' => round($shift->total_expected_balance, 2),
                'status' => $shift->status,
                'cashboxes' => $shift->shiftCashboxes->map(function ($sc) {
                    return [
                        'id' => $sc->cashbox_id,
                        'name' => $sc->cashbox->name,
                        'type' => $sc->cashbox->type,
                        'opening_balance' => round($sc->opening_balance, 2),
                        'expected_balance' => round($sc->expected_balance, 2),
                        'closing_balance' => $sc->closing_balance !== null ? round($sc->closing_balance, 2) : null,
                        'difference' => round($sc->difference, 2),
                        'total_in' => round($sc->total_in, 2),
                        'total_out' => round($sc->total_out, 2),
                    ];
                }),
            ],
        ]);
    }

    public function close(Request $request, Shift $shift)
    {
        if ($shift->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'الوردية مغلق بالفعل',
            ], 422);
        }

        $user = Auth::user();
        if ($shift->user_id !== $user->id && !$user->isManager()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إغلاق وردية مستخدم آخر',
            ], 403);
        }

        $shift->load('shiftCashboxes');
        $cashboxIds = $shift->shiftCashboxes->pluck('cashbox_id')->toArray();

        $rules = [
            'cashboxes' => 'required|array|min:1',
            'cashboxes.*.cashbox_id' => 'required|in:' . implode(',', $cashboxIds),
            'cashboxes.*.closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ];

        $validated = $request->validate($rules);

        $providedCashboxIds = collect($validated['cashboxes'])->pluck('cashbox_id')->toArray();
        $missingCashboxes = array_diff($cashboxIds, $providedCashboxIds);

        if (!empty($missingCashboxes)) {
            return response()->json([
                'success' => false,
                'message' => 'يجب إدخال رصيد الإغلاق لجميع الصناديق',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $shift->recalculateTotals();

            $closingBalancesMap = collect($validated['cashboxes'])->keyBy('cashbox_id');

            $totalDifference = 0;
            foreach ($shift->shiftCashboxes as $sc) {
                $closingBalance = $closingBalancesMap[$sc->cashbox_id]['closing_balance'];
                $sc->closing_balance = $closingBalance;
                $sc->difference = $sc->calculateDifference();
                $sc->save();
                $totalDifference += $sc->difference;
            }

            $shift->closed_at = now();
            $shift->status = 'closed';
            $shift->notes = $validated['notes'] ?? null;

            if ($totalDifference == 0) {
                $shift->approved = true;
                $shift->approved_by = Auth::id();
                $shift->approved_at = now();
            }

            $shift->save();

            DB::commit();

            $shift->load('shiftCashboxes.cashbox:id,name');

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق الوردية بنجاح',
                'shift' => [
                    'id' => $shift->id,
                    'closed_at' => $shift->closed_at->format('Y-m-d H:i'),
                    'total_difference' => round($totalDifference, 2),
                    'requires_approval' => $totalDifference != 0,
                    'cashboxes' => $shift->shiftCashboxes->map(function ($sc) {
                        return [
                            'id' => $sc->cashbox_id,
                            'name' => $sc->cashbox->name,
                            'opening_balance' => round($sc->opening_balance, 2),
                            'closing_balance' => round($sc->closing_balance, 2),
                            'expected_balance' => round($sc->expected_balance, 2),
                            'difference' => round($sc->difference, 2),
                        ];
                    }),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function forceClose(Request $request, Shift $shift)
    {
        if ($shift->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'الوردية مغلق بالفعل',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'cashboxes' => 'nullable|array',
            'cashboxes.*.cashbox_id' => 'required_with:cashboxes|exists:cashboxes,id',
            'cashboxes.*.closing_balance' => 'required_with:cashboxes|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $shift->load('shiftCashboxes');
            $shift->recalculateTotals();

            if (!empty($validated['cashboxes'])) {
                $closingBalancesMap = collect($validated['cashboxes'])->keyBy('cashbox_id');
                foreach ($shift->shiftCashboxes as $sc) {
                    if (isset($closingBalancesMap[$sc->cashbox_id])) {
                        $sc->closing_balance = $closingBalancesMap[$sc->cashbox_id]['closing_balance'];
                    } else {
                        $sc->closing_balance = $sc->expected_balance;
                    }
                    $sc->difference = $sc->calculateDifference();
                    $sc->save();
                }
            } else {
                foreach ($shift->shiftCashboxes as $sc) {
                    $sc->closing_balance = $sc->expected_balance;
                    $sc->difference = 0;
                    $sc->save();
                }
            }

            $shift->closed_at = now();
            $shift->status = 'closed';
            $shift->force_closed = true;
            $shift->force_closed_by = Auth::id();
            $shift->force_close_reason = $validated['reason'];
            $shift->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق الوردية قسرياً',
                'shift' => [
                    'id' => $shift->id,
                    'closed_at' => $shift->closed_at->format('Y-m-d H:i'),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, Shift $shift)
    {
        if ($shift->approved) {
            return response()->json([
                'success' => false,
                'message' => 'الوردية معتمد بالفعل',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $shift->approved = true;
        $shift->approved_by = Auth::id();
        $shift->approved_at = now();
        if ($validated['notes']) {
            $shift->notes = ($shift->notes ? $shift->notes . "\n" : '') . 'ملاحظات الاعتماد: ' . $validated['notes'];
        }
        $shift->save();

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد الوردية',
        ]);
    }

    public function history(Request $request)
    {
        $query = Shift::with(['user:id,name', 'shiftCashboxes.cashbox:id,name'])
            ->orderBy('opened_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('cashbox_id')) {
            $query->whereHas('shiftCashboxes', function ($q) use ($request) {
                $q->where('cashbox_id', $request->cashbox_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('opened_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('opened_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $shifts = $query->paginate(20);

        return response()->json([
            'success' => true,
            'shifts' => $shifts->map(function ($shift) {
                return [
                    'id' => $shift->id,
                    'user_name' => $shift->user->name,
                    'cashboxes' => $shift->shiftCashboxes->map(fn($sc) => [
                        'id' => $sc->cashbox_id,
                        'name' => $sc->cashbox->name,
                    ]),
                    'terminal_id' => $shift->terminal_id,
                    'opened_at' => $shift->opened_at->format('Y-m-d H:i'),
                    'closed_at' => $shift->closed_at?->format('Y-m-d H:i'),
                    'total_opening_balance' => round($shift->total_opening_balance, 2),
                    'total_closing_balance' => round($shift->total_closing_balance, 2),
                    'total_expected_balance' => round($shift->total_expected_balance, 2),
                    'total_difference' => round($shift->total_difference, 2),
                    'total_sales' => round($shift->total_sales, 2),
                    'sales_count' => $shift->sales_count,
                    'status' => $shift->status,
                    'status_arabic' => $shift->status_arabic,
                    'force_closed' => $shift->force_closed,
                    'approved' => $shift->approved,
                ];
            }),
            'pagination' => [
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
                'total' => $shifts->total(),
            ],
        ]);
    }

    public function show(Shift $shift)
    {
        $shift->load([
            'user:id,name',
            'shiftCashboxes.cashbox:id,name,type',
            'forceClosedBy:id,name',
            'approvedBy:id,name',
            'sales' => fn($q) => $q->where('status', 'completed')->with('payments.paymentMethod'),
            'returns' => fn($q) => $q->where('status', 'completed'),
        ]);

        if ($shift->status === 'open') {
            $shift->recalculateTotals();
            $shift->save();
        }

        $paymentBreakdown = [];
        $payments = $shift->sales->flatMap(fn($sale) => $sale->payments);

        foreach ($payments->groupBy('payment_method_id') as $methodId => $methodPayments) {
            $method = $methodPayments->first()->paymentMethod;
            $paymentBreakdown[] = [
                'method_name' => $method->name,
                'method_code' => $method->code,
                'total' => round($methodPayments->sum('amount'), 2),
                'count' => $methodPayments->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'shift' => [
                'id' => $shift->id,
                'user_name' => $shift->user->name,
                'terminal_id' => $shift->terminal_id,
                'opened_at' => $shift->opened_at->format('Y-m-d H:i'),
                'closed_at' => $shift->closed_at?->format('Y-m-d H:i'),
                'cashboxes' => $shift->shiftCashboxes->map(function ($sc) {
                    return [
                        'id' => $sc->cashbox_id,
                        'name' => $sc->cashbox->name,
                        'type' => $sc->cashbox->type,
                        'opening_balance' => round($sc->opening_balance, 2),
                        'closing_balance' => $sc->closing_balance !== null ? round($sc->closing_balance, 2) : null,
                        'expected_balance' => round($sc->expected_balance, 2),
                        'difference' => round($sc->difference, 2),
                        'total_in' => round($sc->total_in, 2),
                        'total_out' => round($sc->total_out, 2),
                    ];
                }),
                'total_opening_balance' => round($shift->total_opening_balance, 2),
                'total_closing_balance' => round($shift->total_closing_balance, 2),
                'total_expected_balance' => round($shift->total_expected_balance, 2),
                'total_difference' => round($shift->total_difference, 2),
                'total_cash_sales' => round($shift->total_cash_sales, 2),
                'total_card_sales' => round($shift->total_card_sales, 2),
                'total_other_sales' => round($shift->total_other_sales, 2),
                'total_sales' => round($shift->total_sales, 2),
                'sales_count' => $shift->sales_count,
                'total_refunds' => round($shift->total_refunds, 2),
                'refunds_count' => $shift->refunds_count,
                'total_expenses' => round($shift->total_expenses, 2),
                'total_deposits' => round($shift->total_deposits, 2),
                'total_withdrawals' => round($shift->total_withdrawals, 2),
                'status' => $shift->status,
                'status_arabic' => $shift->status_arabic,
                'force_closed' => $shift->force_closed,
                'force_closed_by' => $shift->forceClosedBy?->name,
                'force_close_reason' => $shift->force_close_reason,
                'approved' => $shift->approved,
                'approved_by' => $shift->approvedBy?->name,
                'approved_at' => $shift->approved_at?->format('Y-m-d H:i'),
                'notes' => $shift->notes,
                'payment_breakdown' => $paymentBreakdown,
            ],
        ]);
    }

    public function getOpenShifts()
    {
        $shifts = Shift::open()
            ->with(['user:id,name', 'shiftCashboxes.cashbox:id,name'])
            ->get();

        return response()->json([
            'success' => true,
            'shifts' => $shifts->map(function ($shift) {
                return [
                    'id' => $shift->id,
                    'user_name' => $shift->user->name,
                    'cashboxes' => $shift->shiftCashboxes->map(fn($sc) => [
                        'id' => $sc->cashbox_id,
                        'name' => $sc->cashbox->name,
                        'opening_balance' => round($sc->opening_balance, 2),
                    ]),
                    'terminal_id' => $shift->terminal_id,
                    'opened_at' => $shift->opened_at->format('Y-m-d H:i'),
                    'total_opening_balance' => round($shift->total_opening_balance, 2),
                ];
            }),
        ]);
    }

    public function getShiftCashboxes(Shift $shift)
    {
        $shift->load('shiftCashboxes.cashbox:id,name,type');

        return response()->json([
            'success' => true,
            'cashboxes' => $shift->shiftCashboxes->map(function ($sc) {
                return [
                    'id' => $sc->cashbox_id,
                    'name' => $sc->cashbox->name,
                    'type' => $sc->cashbox->type,
                    'opening_balance' => round($sc->opening_balance, 2),
                    'expected_balance' => round($sc->expected_balance, 2),
                    'closing_balance' => $sc->closing_balance !== null ? round($sc->closing_balance, 2) : null,
                    'difference' => round($sc->difference, 2),
                    'total_in' => round($sc->total_in, 2),
                    'total_out' => round($sc->total_out, 2),
                ];
            }),
        ]);
    }

    public function validateCashbox(Request $request)
    {
        $request->validate([
            'cashbox_id' => 'required|exists:cashboxes,id',
        ]);

        $userId = Auth::id();
        $shift = Shift::getOpenShift($userId);

        if (!$shift) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'لا يوجد وردية مفتوح',
            ]);
        }

        $isValid = $shift->hasCashbox($request->cashbox_id);

        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'message' => $isValid ? 'الصندوق مرتبط بالوردية' : 'الصندوق غير مرتبط بالوردية الحالية',
        ]);
    }

    public function debug()
    {
        $userId = Auth::id();
        $currentShift = Shift::getOpenShift($userId);

        $recentSales = \App\Models\Sale::orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'invoice_number', 'shift_id', 'status', 'total', 'created_at']);

        $salesByShift = \App\Models\Sale::selectRaw('shift_id, COUNT(*) as count, SUM(total) as total')
            ->where('status', 'completed')
            ->groupBy('shift_id')
            ->get();

        $currentShiftSales = [];
        $currentShiftCashboxes = [];
        if ($currentShift) {
            $currentShift->load('shiftCashboxes.cashbox:id,name');
            $currentShiftSales = $currentShift->sales()
                ->where('status', 'completed')
                ->get(['id', 'invoice_number', 'total', 'created_at']);
            $currentShiftCashboxes = $currentShift->shiftCashboxes->map(fn($sc) => [
                'cashbox_id' => $sc->cashbox_id,
                'cashbox_name' => $sc->cashbox->name,
                'opening_balance' => $sc->opening_balance,
                'expected_balance' => $sc->expected_balance,
            ]);
        }

        return response()->json([
            'success' => true,
            'user_id' => $userId,
            'current_shift' => $currentShift ? [
                'id' => $currentShift->id,
                'opened_at' => $currentShift->opened_at->format('Y-m-d H:i:s'),
                'status' => $currentShift->status,
                'cashboxes' => $currentShiftCashboxes,
            ] : null,
            'recent_sales' => $recentSales->map(fn($s) => [
                'id' => $s->id,
                'invoice_number' => $s->invoice_number,
                'shift_id' => $s->shift_id,
                'status' => $s->status,
                'total' => $s->total,
                'created_at' => $s->created_at->format('Y-m-d H:i:s'),
            ]),
            'sales_by_shift' => $salesByShift,
            'current_shift_sales_count' => count($currentShiftSales),
            'current_shift_sales' => $currentShiftSales,
        ]);
    }
}
