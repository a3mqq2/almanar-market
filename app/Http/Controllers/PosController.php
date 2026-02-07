<?php

namespace App\Http\Controllers;

use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Models\Cashbox;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Services\InventoryService;
use App\Services\PosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosController extends Controller
{
    protected PosService $posService;
    protected InventoryService $inventoryService;

    public function __construct(PosService $posService, InventoryService $inventoryService)
    {
        $this->posService = $posService;
        $this->inventoryService = $inventoryService;
    }

    public function screen()
    {
        $user = Auth::user();
        $cashboxes = $user->getAccessibleCashboxes();
        $paymentMethods = PaymentMethod::active()->orderBy('sort_order')->get();
        $suspendedCount = Sale::suspended()->count();
        $isManager = $user->isManager();

        $currentShift = Shift::getOpenShift(Auth::id());

        if (!$currentShift && $cashboxes->isNotEmpty()) {
            $currentShift = $this->autoOpenShift($user, $cashboxes);
        }

        $hasOpenShift = $currentShift !== null;

        return view('pos.screen', compact('cashboxes', 'paymentMethods', 'suspendedCount', 'currentShift', 'hasOpenShift', 'isManager'));
    }

    protected function autoOpenShift($user, $cashboxes)
    {
        $terminalId = session()->getId();

        $existingTerminalShift = Shift::open()
            ->where('terminal_id', $terminalId)
            ->where('user_id', '!=', $user->id)
            ->first();

        if ($existingTerminalShift) {
            return null;
        }

        try {
            \DB::beginTransaction();

            $shift = Shift::create([
                'user_id' => $user->id,
                'terminal_id' => $terminalId,
                'opened_at' => now(),
                'status' => 'open',
            ]);

            foreach ($cashboxes as $cashbox) {
                \App\Models\ShiftCashbox::create([
                    'shift_id' => $shift->id,
                    'cashbox_id' => $cashbox->id,
                    'opening_balance' => $cashbox->current_balance ?? 0,
                    'expected_balance' => $cashbox->current_balance ?? 0,
                ]);
            }

            \DB::commit();

            return $shift;

        } catch (\Exception $e) {
            \DB::rollBack();
            return null;
        }
    }

    public function searchProducts(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['success' => true, 'products' => []]);
        }

        $products = Product::with(['productUnits.unit', 'baseUnit.unit'])
            ->where('status', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(fn($p) => $this->formatProductForPos($p));

        return response()->json(['success' => true, 'products' => $products]);
    }

    public function getProductByBarcode(Request $request)
    {
        $product = Product::with(['productUnits.unit', 'baseUnit.unit'])
            ->where('barcode', $request->barcode)
            ->where('status', true)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'product' => $this->formatProductForPos($product)
        ]);
    }

    public function searchCustomers(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['success' => true, 'customers' => []]);
        }

        $customers = Customer::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'phone', 'current_balance', 'credit_limit', 'allow_credit']);

        return response()->json(['success' => true, 'customers' => $customers]);
    }

    public function storeCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'credit_limit' => 'nullable|numeric|min:0',
            'allow_credit' => 'nullable|boolean',
        ]);

        try {
            $customer = Customer::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'credit_limit' => $validated['credit_limit'] ?? 0,
                'allow_credit' => $validated['allow_credit'] ?? false,
                'status' => true,
                'current_balance' => 0,
                'opening_balance' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الزبون بنجاح',
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'current_balance' => $customer->current_balance,
                    'credit_limit' => $customer->credit_limit,
                    'allow_credit' => $customer->allow_credit,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function completeSale(Request $request)
    {
        $currentShift = Shift::getOpenShift(Auth::id());

        if (!$currentShift) {
            return response()->json([
                'success' => false,
                'message' => 'يجب فتح وردية قبل إتمام عملية البيع',
                'type' => 'no_shift'
            ], 422);
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_unit_id' => 'nullable|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.cashbox_id' => 'nullable|exists:cashboxes,id',
            'payments.*.reference_number' => 'nullable|string',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $sale = $this->posService->completeSale(
                $validated['items'],
                $validated['payments'],
                $validated['customer_id'] ?? null,
                [
                    'discount_type' => $validated['discount_type'] ?? null,
                    'discount_value' => $validated['discount_value'] ?? 0,
                    'tax_rate' => $validated['tax_rate'] ?? 0,
                    'notes' => $validated['notes'] ?? null,
                    'shift_id' => $currentShift->id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إتمام عملية البيع',
                'sale' => $sale,
                'invoice_number' => $sale->invoice_number,
                'shift_id' => $sale->shift_id,
            ]);

        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'type' => 'stock_error'
            ], 422);

        } catch (CreditLimitExceededException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'type' => 'credit_error'
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function suspendSale(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'nullable|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.multiplier' => 'nullable|numeric|min:0.0001',
            'customer_id' => 'nullable|exists:customers,id',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $sale = $this->posService->suspendSale($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تعليق الفاتورة',
                'sale_id' => $sale->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSuspendedSales()
    {
        $sales = $this->posService->getSuspendedSales();

        $data = $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'customer_name' => $sale->customer?->name ?? 'زبون عادي',
                'total' => $sale->total,
                'items_count' => $sale->items->count(),
                'notes' => $sale->notes,
                'created_at' => $sale->created_at->format('H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'sales' => $data,
        ]);
    }

    public function resumeSale(int $id)
    {
        try {
            $sale = $this->posService->resumeSale($id);

            $items = $sale->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'unit_id' => $item->product_unit_id,
                    'unit_name' => $item->unitName,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'multiplier' => $item->unit_multiplier,
                    'total_price' => $item->total_price,
                ];
            });

            return response()->json([
                'success' => true,
                'sale' => [
                    'id' => $sale->id,
                    'customer' => $sale->customer,
                    'items' => $items,
                    'notes' => $sale->notes,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancelSale(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->posService->cancelSale($sale, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الفاتورة',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getReceipt(Sale $sale)
    {
        $sale->load(['items.product', 'payments.paymentMethod', 'customer', 'cashier']);

        return response()->json([
            'success' => true,
            'receipt' => [
                'invoice_number' => $sale->invoice_number,
                'sale_date' => $sale->sale_date->format('Y-m-d H:i'),
                'customer_name' => $sale->customer?->name ?? 'زبون عادي',
                'cashier_name' => $sale->cashier?->name ?? '-',
                'items' => $sale->items->map(fn($item) => [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_name' => $item->unitName,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total_price,
                ]),
                'subtotal' => $sale->subtotal,
                'discount_amount' => $sale->discount_amount,
                'tax_amount' => $sale->tax_amount,
                'total' => $sale->total,
                'payments' => $sale->payments->map(fn($p) => [
                    'method' => $p->paymentMethod->name,
                    'amount' => $p->amount,
                ]),
                'paid_amount' => $sale->paid_amount,
                'credit_amount' => $sale->credit_amount,
            ],
        ]);
    }

    public function deleteSuspended(Sale $sale)
    {
        try {
            $this->posService->deleteSuspendedSale($sale);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الفاتورة المعلقة',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    protected function formatProductForPos(Product $product): array
    {
        $stock = $this->inventoryService->getAvailableStock($product);

        $nearestExpiry = $product->inventoryBatches()
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date')
            ->first();

        $expiryDate = $nearestExpiry?->expiry_date;
        $expiryStatus = null;
        $daysToExpiry = null;

        if ($expiryDate) {
            $daysToExpiry = now()->startOfDay()->diffInDays($expiryDate, false);
            if ($daysToExpiry < 0) {
                $expiryStatus = 'expired';
            } elseif ($daysToExpiry <= 7) {
                $expiryStatus = 'critical';
            } elseif ($daysToExpiry <= 30) {
                $expiryStatus = 'warning';
            } else {
                $expiryStatus = 'ok';
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'image' => $product->image,
            'stock' => $stock ?? 0,
            'expiry_date' => $expiryDate?->format('Y-m-d'),
            'expiry_status' => $expiryStatus,
            'days_to_expiry' => $daysToExpiry,
            'base_unit' => $product->baseUnit ? [
                'id' => $product->baseUnit->id,
                'name' => $product->baseUnit->unit?->name ?? 'وحدة',
                'sale_price' => (float) ($product->baseUnit->sell_price ?? 0),
                'multiplier' => 1,
            ] : null,
            'units' => $product->productUnits->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->unit?->name ?? 'وحدة',
                'multiplier' => (float) ($u->multiplier ?? 1),
                'sale_price' => (float) ($u->sell_price ?? 0),
                'is_base' => $u->is_base_unit,
            ]),
        ];
    }
}
