<?php

namespace App\Services;

use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Models\Cashbox;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosService
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

    public function completeSale(
        array $items,
        array $payments,
        ?int $customerId = null,
        array $options = []
    ): Sale {
        DB::beginTransaction();

        try {
            $sale = $this->createSale($customerId, $options);

            $totalCost = 0;
            foreach ($items as $itemData) {
                $itemCost = $this->processSaleItem($sale, $itemData);
                $totalCost += $itemCost;
            }

            $sale->load('items');
            $sale->calculateTotals();
            $sale->save();

            $totalPaid = collect($payments)->sum('amount');
            $creditAmount = $sale->total - $totalPaid;

            if ($creditAmount > 0) {
                $this->validateCustomerCredit($sale, $creditAmount, $customerId);
            }

            $this->financialService->createSaleEntries($sale, $payments, $options['shift_id'] ?? null);

            $sale->update(['status' => 'completed']);

            DB::commit();
            return $sale->fresh(['items.product', 'payments.paymentMethod', 'customer']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function createSale(?int $customerId, array $options): Sale
    {
        return Sale::create([
            'customer_id' => $customerId,
            'shift_id' => $options['shift_id'] ?? null,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'sale_date' => now()->toDateString(),
            'status' => 'draft',
            'discount_type' => $options['discount_type'] ?? null,
            'discount_value' => $options['discount_value'] ?? 0,
            'tax_rate' => $options['tax_rate'] ?? 0,
            'notes' => $options['notes'] ?? null,
            'cashier_id' => Auth::id(),
            'created_by' => Auth::id(),
        ]);
    }

    protected function processSaleItem(Sale $sale, array $itemData): float
    {
        $product = Product::findOrFail($itemData['product_id']);
        $productUnit = null;
        $multiplier = 1;
        $unitPrice = $itemData['unit_price'];

        if (!empty($itemData['product_unit_id'])) {
            $productUnit = ProductUnit::find($itemData['product_unit_id']);
            $multiplier = $productUnit?->multiplier ?? 1;
        }

        $quantity = $itemData['quantity'];
        $baseQuantity = $quantity * $multiplier;
        $totalPrice = $quantity * $unitPrice;

        $stockResult = $this->inventoryService->deductStock(
            $product,
            $baseQuantity,
            "فاتورة مبيعات #{$sale->invoice_number}",
            "SAL-{$sale->id}"
        );

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_unit_id' => $productUnit?->id,
            'barcode_label' => $itemData['barcode_label'] ?? null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'unit_multiplier' => $multiplier,
            'base_quantity' => $baseQuantity,
            'cost_at_sale' => $stockResult['average_cost'],
            'total_price' => $totalPrice,
        ]);

        return $stockResult['total_cost'];
    }

    protected function validateCustomerCredit(Sale $sale, float $creditAmount, ?int $customerId): void
    {
        if (!$customerId) {
            throw new CreditLimitExceededException(
                'لا يمكن البيع الآجل بدون تحديد زبون',
                null,
                $creditAmount,
                0
            );
        }

        $customer = Customer::find($customerId);

        if (!$customer) {
            throw new CreditLimitExceededException(
                'الزبون غير موجود',
                null,
                $creditAmount,
                0
            );
        }

        if (!$customer->allow_credit) {
            throw new CreditLimitExceededException(
                "الزبون {$customer->name} غير مسموح له بالبيع الآجل",
                $customer,
                $creditAmount,
                $customer->credit_limit
            );
        }

        if (!$customer->canTakeCredit($creditAmount)) {
            throw new CreditLimitExceededException(
                "تجاوز الحد الائتماني للزبون {$customer->name}. المتاح: " . number_format($customer->availableCredit, 2),
                $customer,
                $creditAmount,
                $customer->credit_limit
            );
        }
    }

    public function suspendSale(array $data): Sale
    {
        DB::beginTransaction();

        try {
            $sale = Sale::create([
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_number' => Sale::generateInvoiceNumber(),
                'sale_date' => now()->toDateString(),
                'status' => 'draft',
                'is_suspended' => true,
                'notes' => $data['note'] ?? null,
                'cashier_id' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['unit_id'] ?? null,
                    'barcode_label' => $item['barcode_label'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_multiplier' => $item['multiplier'] ?? 1,
                    'base_quantity' => $item['quantity'] * ($item['multiplier'] ?? 1),
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $sale->calculateTotals();
            $sale->save();

            DB::commit();
            return $sale;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function resumeSale(int $saleId): Sale
    {
        $sale = Sale::suspended()->findOrFail($saleId);
        $sale->update(['is_suspended' => false]);
        return $sale->load('items.product', 'customer');
    }

    public function getSuspendedSales(): Collection
    {
        return Sale::suspended()
            ->with(['customer', 'items.product'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function cancelSale(Sale $sale, string $reason): Sale
    {
        if (!$sale->canBeCancelled()) {
            throw new \Exception('لا يمكن إلغاء هذه الفاتورة');
        }

        DB::beginTransaction();

        try {
            if ($sale->status === 'completed') {
                foreach ($sale->items as $item) {
                    $deductions = [[
                        'batch_id' => $item->inventory_batch_id,
                        'quantity' => $item->base_quantity,
                        'cost_price' => $item->cost_at_sale,
                    ]];

                    $this->inventoryService->restoreStock(
                        $item->product,
                        $deductions,
                        "إلغاء فاتورة مبيعات #{$sale->invoice_number}",
                        "SAL-{$sale->id}-CANCEL"
                    );
                }

                $this->financialService->reverseSaleEntries($sale);
            }

            $sale->update([
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            DB::commit();
            return $sale;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteSuspendedSale(Sale $sale): bool
    {
        if (!$sale->is_suspended) {
            throw new \Exception('لا يمكن حذف فاتورة غير معلقة');
        }

        return $sale->delete();
    }
}
