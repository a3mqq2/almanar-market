<?php

namespace App\Services;

use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialTransactionService
{
    public function createPurchaseEntries(Purchase $purchase, ?int $cashboxId = null): array
    {
        $supplier = $purchase->supplier;
        $result = ['supplier_transaction' => null, 'cashbox_transaction' => null, 'supplier_debit_transaction' => null];

        $paidAmount = $purchase->paid_amount ?? 0;
        $remainingAmount = $purchase->total - $paidAmount;

        if ($purchase->payment_type === 'credit') {
            $result['supplier_transaction'] = $this->createSupplierDebit(
                $supplier,
                $purchase->total,
                "فاتورة مشتريات #{$purchase->id}",
                Purchase::class,
                $purchase->id,
                $purchase->purchase_date
            );

            $purchase->update([
                'paid_amount' => 0,
                'remaining_amount' => $purchase->total,
            ]);
        } else {
            if ($paidAmount > 0) {
                if (!$cashboxId) {
                    throw new \Exception('يجب تحديد الخزينة للدفع النقدي');
                }

                $cashbox = Cashbox::findOrFail($cashboxId);

                if ($cashbox->current_balance < $paidAmount) {
                    throw new \Exception('رصيد الخزينة غير كافٍ. الرصيد الحالي: ' . number_format($cashbox->current_balance, 2));
                }

                $result['supplier_transaction'] = $this->createSupplierCredit(
                    $supplier,
                    $paidAmount,
                    "سداد فاتورة مشتريات #{$purchase->id}" . ($remainingAmount > 0 ? " (دفعة جزئية)" : ""),
                    Purchase::class,
                    $purchase->id,
                    $cashboxId,
                    $purchase->purchase_date
                );

                $result['cashbox_transaction'] = $this->createCashboxOut(
                    $cashbox,
                    $paidAmount,
                    "فاتورة مشتريات #{$purchase->id} - {$supplier->name}" . ($remainingAmount > 0 ? " (دفعة جزئية)" : ""),
                    Purchase::class,
                    $purchase->id,
                    $purchase->purchase_date
                );
            }

            if ($remainingAmount > 0) {
                $result['supplier_debit_transaction'] = $this->createSupplierDebit(
                    $supplier,
                    $remainingAmount,
                    "مستحقات فاتورة مشتريات #{$purchase->id}" . ($paidAmount > 0 ? " (المتبقي بعد الدفعة الجزئية)" : ""),
                    Purchase::class,
                    $purchase->id,
                    $purchase->purchase_date
                );
            }

            $purchase->update([
                'paid_amount' => $paidAmount,
                'remaining_amount' => max(0, $remainingAmount),
            ]);
        }

        return $result;
    }

    public function createSupplierPayment(
        Supplier $supplier,
        float $amount,
        int $cashboxId,
        ?string $description = null,
        ?string $transactionDate = null
    ): array {
        $cashbox = Cashbox::findOrFail($cashboxId);

        if ($cashbox->current_balance < $amount) {
            throw new \Exception('رصيد الخزينة غير كافٍ. الرصيد الحالي: ' . number_format($cashbox->current_balance, 2));
        }

        $date = $transactionDate ?? now();
        $desc = $description ?? 'سداد للمورد';

        $supplierTransaction = $this->createSupplierCredit(
            $supplier,
            $amount,
            $desc,
            'supplier_payment',
            null,
            $cashboxId,
            $date
        );

        $cashboxTransaction = $this->createCashboxOut(
            $cashbox,
            $amount,
            "سداد للمورد: {$supplier->name}",
            'supplier_payment',
            $supplierTransaction->id,
            $date
        );

        DB::table('supplier_transactions')
            ->where('id', $supplierTransaction->id)
            ->update(['reference_id' => $cashboxTransaction->id]);

        return [
            'supplier_transaction' => $supplierTransaction,
            'cashbox_transaction' => $cashboxTransaction,
        ];
    }

    public function reversePurchaseEntries(Purchase $purchase): array
    {
        $supplier = $purchase->supplier;
        $result = ['supplier_transaction' => null, 'cashbox_transaction' => null, 'supplier_credit_transaction' => null];

        if ($purchase->remaining_amount > 0) {
            $result['supplier_credit_transaction'] = $this->createSupplierCredit(
                $supplier,
                $purchase->remaining_amount,
                "إلغاء مستحقات فاتورة مشتريات #{$purchase->id}",
                Purchase::class,
                $purchase->id,
                null,
                now()
            );
        }

        if ($purchase->paid_amount > 0) {
            $originalTransaction = SupplierTransaction::where('reference_type', Purchase::class)
                ->where('reference_id', $purchase->id)
                ->where('type', 'credit')
                ->first();

            if ($originalTransaction && $originalTransaction->cashbox_id) {
                $cashbox = Cashbox::find($originalTransaction->cashbox_id);

                if ($cashbox) {
                    $result['supplier_transaction'] = $this->createSupplierDebit(
                        $supplier,
                        $purchase->paid_amount,
                        "إلغاء سداد فاتورة مشتريات #{$purchase->id}",
                        Purchase::class,
                        $purchase->id,
                        now()
                    );

                    $result['cashbox_transaction'] = $this->createCashboxIn(
                        $cashbox,
                        $purchase->paid_amount,
                        "إلغاء فاتورة مشتريات #{$purchase->id} - {$supplier->name}",
                        Purchase::class,
                        $purchase->id,
                        now()
                    );
                }
            }
        }

        return $result;
    }

    public function createSupplierDebit(
        Supplier $supplier,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        $transactionDate = null
    ): SupplierTransaction {
        $currentBalance = $supplier->current_balance;
        $newBalance = $currentBalance + $amount;

        $transaction = SupplierTransaction::create([
            'supplier_id' => $supplier->id,
            'type' => 'debit',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'transaction_date' => $transactionDate ?? now(),
            'created_by' => Auth::id(),
        ]);

        $supplier->update(['current_balance' => $newBalance]);

        return $transaction;
    }

    public function createSupplierCredit(
        Supplier $supplier,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $cashboxId = null,
        $transactionDate = null
    ): SupplierTransaction {
        $currentBalance = $supplier->current_balance;
        $newBalance = $currentBalance - $amount;

        $transaction = SupplierTransaction::create([
            'supplier_id' => $supplier->id,
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'cashbox_id' => $cashboxId,
            'transaction_date' => $transactionDate ?? now(),
            'created_by' => Auth::id(),
        ]);

        $supplier->update(['current_balance' => $newBalance]);

        return $transaction;
    }

    public function createCashboxOut(
        Cashbox $cashbox,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        $transactionDate = null,
        ?int $shiftId = null,
        ?int $paymentMethodId = null
    ): CashboxTransaction {
        $currentBalance = $cashbox->current_balance;
        $newBalance = $currentBalance - $amount;

        $transaction = CashboxTransaction::create([
            'cashbox_id' => $cashbox->id,
            'shift_id' => $shiftId,
            'payment_method_id' => $paymentMethodId,
            'type' => 'out',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'transaction_date' => $transactionDate ?? now(),
            'created_by' => Auth::id(),
        ]);

        $cashbox->update(['current_balance' => $newBalance]);

        return $transaction;
    }

    public function createCashboxIn(
        Cashbox $cashbox,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        $transactionDate = null,
        ?int $shiftId = null,
        ?int $paymentMethodId = null
    ): CashboxTransaction {
        $currentBalance = $cashbox->current_balance;
        $newBalance = $currentBalance + $amount;

        $transaction = CashboxTransaction::create([
            'cashbox_id' => $cashbox->id,
            'shift_id' => $shiftId,
            'payment_method_id' => $paymentMethodId,
            'type' => 'in',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'transaction_date' => $transactionDate ?? now(),
            'created_by' => Auth::id(),
        ]);

        $cashbox->update(['current_balance' => $newBalance]);

        return $transaction;
    }

    public function validateCashboxBalance(int $cashboxId, float $amount): bool
    {
        $cashbox = Cashbox::find($cashboxId);
        return $cashbox && $cashbox->current_balance >= $amount;
    }

    public function getFinancialTrace(string $referenceType, int $referenceId): array
    {
        $supplierTransactions = SupplierTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->with('supplier', 'cashbox')
            ->get();

        $cashboxTransactions = CashboxTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->with('cashbox')
            ->get();

        return [
            'supplier_transactions' => $supplierTransactions,
            'cashbox_transactions' => $cashboxTransactions,
        ];
    }

    public function createCustomerDebit(
        Customer $customer,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        $transactionDate = null
    ): CustomerTransaction {
        $currentBalance = $customer->current_balance;
        $newBalance = $currentBalance + $amount;

        $transaction = CustomerTransaction::create([
            'customer_id' => $customer->id,
            'type' => 'debit',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'transaction_date' => $transactionDate ?? now(),
            'created_by' => Auth::id(),
        ]);

        $customer->update(['current_balance' => $newBalance]);

        return $transaction;
    }

    public function createCustomerCredit(
        Customer $customer,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $cashboxId = null,
        $transactionDate = null
    ): CustomerTransaction {
        $currentBalance = $customer->current_balance;
        $newBalance = $currentBalance - $amount;

        $transaction = CustomerTransaction::create([
            'customer_id' => $customer->id,
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'cashbox_id' => $cashboxId,
            'transaction_date' => $transactionDate ?? now(),
            'created_by' => Auth::id(),
        ]);

        $customer->update(['current_balance' => $newBalance]);

        return $transaction;
    }

    public function createCustomerPayment(
        Customer $customer,
        float $amount,
        int $cashboxId,
        ?string $description = null,
        ?string $transactionDate = null
    ): array {
        $cashbox = Cashbox::findOrFail($cashboxId);
        $date = $transactionDate ?? now();
        $desc = $description ?? 'سداد من الزبون';

        $customerTransaction = $this->createCustomerCredit(
            $customer,
            $amount,
            $desc,
            'customer_payment',
            null,
            $cashboxId,
            $date
        );

        $cashboxTransaction = $this->createCashboxIn(
            $cashbox,
            $amount,
            "سداد من الزبون: {$customer->name}",
            'customer_payment',
            $customerTransaction->id,
            $date
        );

        DB::table('customer_transactions')
            ->where('id', $customerTransaction->id)
            ->update(['reference_id' => $cashboxTransaction->id]);

        return [
            'customer_transaction' => $customerTransaction,
            'cashbox_transaction' => $cashboxTransaction,
        ];
    }

    public function createSaleEntries(Sale $sale, array $payments, ?int $shiftId = null): array
    {
        $result = [
            'customer_transaction' => null,
            'cashbox_transactions' => [],
            'sale_payments' => [],
        ];

        $totalPaid = 0;

        foreach ($payments as $payment) {
            $amount = $payment['amount'];
            if ($amount <= 0) continue;

            $totalPaid += $amount;

            $paymentMethodId = $payment['payment_method_id'];
            $paymentMethod = PaymentMethod::find($paymentMethodId);

            // Get cashbox: use provided cashbox_id, or get from payment method's linked cashbox
            $cashboxId = $payment['cashbox_id'] ?? $paymentMethod?->cashbox_id;

            $salePayment = SalePayment::create([
                'sale_id' => $sale->id,
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'cashbox_id' => $cashboxId,
                'reference_number' => $payment['reference_number'] ?? null,
            ]);

            $result['sale_payments'][] = $salePayment;

            if ($cashboxId) {
                $cashbox = Cashbox::find($cashboxId);
                if ($cashbox) {
                    $cashboxTransaction = $this->createCashboxIn(
                        $cashbox,
                        $amount,
                        "فاتورة مبيعات #{$sale->invoice_number}",
                        Sale::class,
                        $sale->id,
                        $sale->sale_date,
                        $shiftId,
                        $paymentMethodId
                    );
                    $result['cashbox_transactions'][] = $cashboxTransaction;
                }
            }
        }

        $creditAmount = $sale->total - $totalPaid;

        if ($creditAmount > 0 && $sale->customer_id) {
            $result['customer_transaction'] = $this->createCustomerDebit(
                $sale->customer,
                $creditAmount,
                "فاتورة مبيعات #{$sale->invoice_number}",
                Sale::class,
                $sale->id,
                $sale->sale_date
            );
        }

        $sale->update([
            'paid_amount' => $totalPaid,
            'credit_amount' => $creditAmount,
            'payment_status' => $creditAmount > 0 ? ($totalPaid > 0 ? 'partial' : 'credit') : 'paid',
        ]);

        return $result;
    }

    public function reverseSaleEntries(Sale $sale): array
    {
        $result = [
            'customer_transaction' => null,
            'cashbox_transactions' => [],
        ];

        if ($sale->credit_amount > 0 && $sale->customer_id) {
            $result['customer_transaction'] = $this->createCustomerCredit(
                $sale->customer,
                $sale->credit_amount,
                "إلغاء فاتورة مبيعات #{$sale->invoice_number}",
                Sale::class,
                $sale->id,
                now()
            );
        }

        $originalCashboxTransactions = CashboxTransaction::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->where('type', 'in')
            ->get();

        foreach ($originalCashboxTransactions as $original) {
            $cashbox = Cashbox::find($original->cashbox_id);
            if ($cashbox) {
                $reverseTransaction = $this->createCashboxOut(
                    $cashbox,
                    $original->amount,
                    "إلغاء فاتورة مبيعات #{$sale->invoice_number}",
                    Sale::class,
                    $sale->id,
                    now()
                );
                $result['cashbox_transactions'][] = $reverseTransaction;
            }
        }

        return $result;
    }

    public function processReturnRefund(SalesReturn $return): array
    {
        $result = [
            'customer_transaction' => null,
            'cashbox_transaction' => null,
        ];

        $sale = $return->sale;

        switch ($return->refund_method) {
            case 'cash':
                if ($return->cashbox_id) {
                    $cashbox = Cashbox::find($return->cashbox_id);
                    if ($cashbox) {
                        if ($cashbox->current_balance < $return->total_amount) {
                            throw new \Exception('رصيد الخزينة غير كافٍ للاسترداد');
                        }

                        $result['cashbox_transaction'] = $this->createCashboxOut(
                            $cashbox,
                            $return->total_amount,
                            "استرداد فاتورة #{$sale->invoice_number} - مرتجع #{$return->return_number}",
                            SalesReturn::class,
                            $return->id,
                            $return->return_date
                        );
                    }
                }
                break;

            case 'same_payment':
                $originalCashboxTransactions = CashboxTransaction::where('reference_type', Sale::class)
                    ->where('reference_id', $sale->id)
                    ->where('type', 'in')
                    ->get();

                $remainingAmount = $return->total_amount;

                foreach ($originalCashboxTransactions as $original) {
                    if ($remainingAmount <= 0) break;

                    $refundAmount = min($remainingAmount, $original->amount);
                    $cashbox = Cashbox::find($original->cashbox_id);

                    if ($cashbox && $cashbox->current_balance >= $refundAmount) {
                        $result['cashbox_transaction'] = $this->createCashboxOut(
                            $cashbox,
                            $refundAmount,
                            "استرداد فاتورة #{$sale->invoice_number} - مرتجع #{$return->return_number}",
                            SalesReturn::class,
                            $return->id,
                            $return->return_date
                        );
                        $remainingAmount -= $refundAmount;
                    }
                }
                break;

            case 'store_credit':
                if ($return->customer_id) {
                    $customer = Customer::find($return->customer_id);
                    if ($customer) {
                        $result['customer_transaction'] = $this->createCustomerCredit(
                            $customer,
                            $return->total_amount,
                            "رصيد استرداد فاتورة #{$sale->invoice_number} - مرتجع #{$return->return_number}",
                            SalesReturn::class,
                            $return->id,
                            null,
                            $return->return_date
                        );
                    }
                }
                break;

            case 'deduct_credit':
                if ($return->customer_id) {
                    $customer = Customer::find($return->customer_id);
                    if ($customer) {
                        $result['customer_transaction'] = $this->createCustomerCredit(
                            $customer,
                            $return->total_amount,
                            "خصم مرتجع من حساب - فاتورة #{$sale->invoice_number} - مرتجع #{$return->return_number}",
                            SalesReturn::class,
                            $return->id,
                            null,
                            $return->return_date
                        );
                    }
                }
                break;
        }

        return $result;
    }
}
