<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\CashboxTransaction;
use App\Models\Purchase;
use App\Models\SupplierTransaction;
use Illuminate\Http\Request;

class FinancialTraceController extends Controller
{
    public function index(Request $request)
    {
        $traces = collect();

        if ($request->filled('reference_type') && $request->filled('reference_id')) {
            $traces = $this->getTrace($request->reference_type, $request->reference_id);
        }

        $purchases = Purchase::with('supplier')
            ->whereIn('status', ['approved', 'cancelled'])
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        return view('reports.financial-trace', compact('traces', 'purchases'));
    }

    public function getTraceData(Request $request)
    {
        $validated = $request->validate([
            'reference_type' => 'required|string',
            'reference_id' => 'required|integer',
        ]);

        $traces = $this->getTrace($validated['reference_type'], $validated['reference_id']);

        return response()->json([
            'success' => true,
            'data' => $traces,
        ]);
    }

    protected function getTrace(string $referenceType, int $referenceId): array
    {
        $supplierTransactions = SupplierTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->with(['supplier', 'cashbox', 'creator'])
            ->orderBy('id')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'ledger' => 'supplier',
                    'ledger_arabic' => 'حساب المورد',
                    'account_name' => $t->supplier->name,
                    'type' => $t->type,
                    'type_arabic' => $t->type_arabic,
                    'amount' => $t->amount,
                    'balance_after' => $t->balance_after,
                    'description' => $t->description,
                    'cashbox_name' => $t->cashbox?->name,
                    'transaction_date' => $t->transaction_date->format('Y-m-d'),
                    'created_by' => $t->creator?->name ?? '-',
                    'created_at' => $t->created_at->format('Y-m-d H:i'),
                ];
            });

        $cashboxTransactions = CashboxTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->with(['cashbox', 'creator'])
            ->orderBy('id')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'ledger' => 'cashbox',
                    'ledger_arabic' => 'الخزينة',
                    'account_name' => $t->cashbox->name,
                    'type' => $t->type,
                    'type_arabic' => $t->type_arabic,
                    'amount' => $t->amount,
                    'balance_after' => $t->balance_after,
                    'description' => $t->description,
                    'cashbox_name' => null,
                    'transaction_date' => $t->transaction_date->format('Y-m-d'),
                    'created_by' => $t->creator?->name ?? '-',
                    'created_at' => $t->created_at->format('Y-m-d H:i'),
                ];
            });

        $reference = null;
        if ($referenceType == Purchase::class || $referenceType == 'App\\Models\\Purchase') {
            $purchase = Purchase::with('supplier')->find($referenceId);
            if ($purchase) {
                $reference = [
                    'type' => 'purchase',
                    'type_arabic' => 'فاتورة مشتريات',
                    'id' => $purchase->id,
                    'date' => $purchase->purchase_date->format('Y-m-d'),
                    'total' => $purchase->total,
                    'payment_type' => $purchase->payment_type,
                    'payment_type_arabic' => $purchase->payment_type_arabic,
                    'status' => $purchase->status,
                    'status_arabic' => $purchase->status_arabic,
                    'supplier_name' => $purchase->supplier->name,
                    'url' => route('purchases.show', $purchase),
                ];
            }
        }

        return [
            'reference' => $reference,
            'supplier_transactions' => $supplierTransactions,
            'cashbox_transactions' => $cashboxTransactions,
        ];
    }
}
