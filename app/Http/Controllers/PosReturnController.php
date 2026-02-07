<?php

namespace App\Http\Controllers;

use App\Models\Cashbox;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\SaleReturnItem;
use App\Models\Shift;
use App\Services\FinancialTransactionService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosReturnController extends Controller
{
    protected FinancialTransactionService $financialService;
    protected InventoryService $inventoryService;

    public function __construct(
        FinancialTransactionService $financialService,
        InventoryService $inventoryService
    ) {
        $this->financialService = $financialService;
        $this->inventoryService = $inventoryService;
    }

    public function searchInvoice(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 3) {
            return response()->json(['success' => false, 'message' => 'أدخل 3 أحرف على الأقل']);
        }

        $sales = Sale::with(['customer', 'items.product'])
            ->where('status', 'completed')
            ->where(function ($q) use ($query) {
                $q->where('invoice_number', 'like', "%{$query}%")
                  ->orWhereHas('customer', function ($q) use ($query) {
                      $q->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                  });
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->filter(fn($sale) => $sale->canBeReturned())
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'sale_date' => $sale->sale_date->format('Y-m-d'),
                    'customer_name' => $sale->customer?->name ?? 'زبون عادي',
                    'total' => $sale->total,
                    'total_returned' => $sale->total_returned,
                    'items_count' => $sale->items->count(),
                ];
            })
            ->values();

        return response()->json(['success' => true, 'sales' => $sales]);
    }

    public function loadSale(Sale $sale)
    {
        if (!$sale->canBeReturned()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن استرداد هذه الفاتورة'
            ], 422);
        }

        $sale->load([
            'items.product',
            'items.productUnit.unit',
            'items.returnItems.salesReturn',
            'payments.paymentMethod',
            'customer',
            'cashier',
        ]);

        $items = $sale->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'unit_name' => $item->unitName,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'returned_qty' => (float) $item->returned_quantity,
                'returnable_qty' => (float) $item->returnable_quantity,
                'cost_at_sale' => (float) ($item->cost_at_sale ?? 0),
                'base_quantity' => (float) $item->base_quantity,
                'unit_multiplier' => (float) $item->unit_multiplier,
            ];
        });

        $payments = $sale->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'method_name' => $payment->paymentMethod->name,
                'method_code' => $payment->paymentMethod->code,
                'amount' => (float) $payment->amount,
                'cashbox_id' => $payment->cashbox_id,
            ];
        });

        return response()->json([
            'success' => true,
            'sale' => [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'sale_date' => $sale->sale_date->format('Y-m-d'),
                'customer_id' => $sale->customer_id,
                'customer_name' => $sale->customer?->name ?? 'زبون عادي',
                'customer_phone' => $sale->customer?->phone,
                'subtotal' => (float) $sale->subtotal,
                'discount_amount' => (float) $sale->discount_amount,
                'tax_amount' => (float) $sale->tax_amount,
                'total' => (float) $sale->total,
                'paid_amount' => (float) $sale->paid_amount,
                'credit_amount' => (float) $sale->credit_amount,
                'total_returned' => (float) $sale->total_returned,
                'payment_status' => $sale->payment_status,
                'items' => $items,
                'payments' => $payments,
            ],
        ]);
    }

    public function processReturn(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'reason' => 'required|in:damaged,wrong_invoice,unsatisfied,expired,other',
            'reason_notes' => 'nullable|string|max:500',
            'refund_method' => 'required|in:cash,same_payment,store_credit,deduct_credit',
            'cashbox_id' => 'nullable|exists:cashboxes,id',
            'restore_stock' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $sale = Sale::with(['items', 'customer'])->findOrFail($validated['sale_id']);

        if (!$sale->canBeReturned()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن استرداد هذه الفاتورة'
            ], 422);
        }

        foreach ($validated['items'] as $item) {
            $saleItem = $sale->items->firstWhere('id', $item['sale_item_id']);
            if (!$saleItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'عنصر غير موجود في الفاتورة'
                ], 422);
            }

            if ($item['quantity'] > $saleItem->returnable_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "الكمية المطلوبة أكبر من الكمية المتاحة للإرجاع للمنتج: {$saleItem->product->name}"
                ], 422);
            }
        }

        if (in_array($validated['refund_method'], ['cash', 'same_payment']) && !$validated['cashbox_id']) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تحديد الخزينة للرد النقدي'
            ], 422);
        }

        if (in_array($validated['refund_method'], ['store_credit', 'deduct_credit']) && !$sale->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إضافة رصيد لزبون عادي'
            ], 422);
        }

        $currentShift = Shift::getOpenShift(Auth::id());
        if (!$currentShift) {
            return response()->json([
                'success' => false,
                'message' => 'يجب فتح وردية قبل إتمام عملية الإرجاع'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $salesReturn = SalesReturn::create([
                'sale_id' => $sale->id,
                'shift_id' => $currentShift->id,
                'return_number' => SalesReturn::generateReturnNumber(),
                'return_date' => now()->toDateString(),
                'status' => 'completed',
                'refund_method' => $validated['refund_method'],
                'cashbox_id' => $validated['cashbox_id'] ?? null,
                'reason' => $validated['reason'],
                'reason_notes' => $validated['reason_notes'] ?? null,
                'restore_stock' => $validated['restore_stock'] ?? true,
                'customer_id' => $sale->customer_id,
                'created_by' => Auth::id(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($validated['items'] as $itemData) {
                $saleItem = $sale->items->firstWhere('id', $itemData['sale_item_id']);
                $qty = $itemData['quantity'];
                $amount = $qty * $saleItem->unit_price;
                $baseQty = $qty * $saleItem->unit_multiplier;

                $returnItem = SaleReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $qty,
                    'unit_price' => $saleItem->unit_price,
                    'total_amount' => $amount,
                    'cost_at_sale' => $saleItem->cost_at_sale ?? 0,
                    'base_quantity' => $baseQty,
                    'stock_restored' => false,
                ]);

                $totalAmount += $amount;

                if ($validated['restore_stock'] ?? true) {
                    $batch = $this->inventoryService->restoreStockFromReturn(
                        $saleItem->product,
                        $baseQty,
                        $saleItem->cost_at_sale ?? 0,
                        "استرداد من فاتورة #{$sale->invoice_number}",
                        "RET-{$salesReturn->return_number}"
                    );

                    $returnItem->update([
                        'stock_restored' => true,
                        'inventory_batch_id' => $batch?->id,
                    ]);
                }
            }

            $salesReturn->update([
                'subtotal' => $totalAmount,
                'total_amount' => $totalAmount,
            ]);

            $this->financialService->processReturnRefund($salesReturn);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم الاسترداد بنجاح',
                'return' => [
                    'id' => $salesReturn->id,
                    'return_number' => $salesReturn->return_number,
                    'total_amount' => $salesReturn->total_amount,
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

    public function printReturnReceipt(SalesReturn $salesReturn)
    {
        $salesReturn->load([
            'items.product',
            'items.saleItem.productUnit.unit',
            'sale',
            'customer',
            'creator',
            'cashbox',
        ]);

        return view('pos.print-return-receipt', ['return' => $salesReturn]);
    }

    public function getRecentReturns(Request $request)
    {
        $returns = SalesReturn::with(['sale', 'customer', 'creator'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($return) {
                return [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'return_date' => $return->return_date->format('Y-m-d'),
                    'sale_invoice' => $return->sale->invoice_number,
                    'customer_name' => $return->customer?->name ?? 'زبون عادي',
                    'total_amount' => $return->total_amount,
                    'reason' => $return->reason_arabic,
                    'refund_method' => $return->refund_method_arabic,
                    'created_by' => $return->creator?->name ?? '-',
                ];
            });

        return response()->json(['success' => true, 'returns' => $returns]);
    }
}
