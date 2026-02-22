<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixSupplierPaymentEntriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $wrongEntries = DB::table('supplier_transactions')
                ->where('type', 'debit')
                ->where(function ($query) {
                    $query->where('reference_type', 'supplier_payment')
                        ->orWhere(function ($q) {
                            $q->whereNotNull('cashbox_id')
                                ->where('description', 'like', '%سداد%');
                        });
                })
                ->get();

            if ($wrongEntries->isEmpty()) {
                $this->command->info('لا توجد قيود سداد خاطئة.');
                DB::rollBack();
                return;
            }

            $this->command->info("تم العثور على {$wrongEntries->count()} قيد سداد مسجل كمدين بشكل خاطئ.");

            $affectedSupplierIds = $wrongEntries->pluck('supplier_id')->unique();

            foreach ($wrongEntries as $entry) {
                DB::table('supplier_transactions')
                    ->where('id', $entry->id)
                    ->update(['type' => 'credit']);

                $this->command->info("  ← تم قلب القيد #{$entry->id} من مدين إلى دائن (المبلغ: {$entry->amount})");
            }

            $this->command->info('');
            $this->command->info('إعادة احتساب الأرصدة...');

            foreach ($affectedSupplierIds as $supplierId) {
                $supplier = Supplier::find($supplierId);
                if (!$supplier) continue;

                $balance = (float) $supplier->opening_balance;

                $transactions = DB::table('supplier_transactions')
                    ->where('supplier_id', $supplierId)
                    ->orderBy('transaction_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($transactions as $t) {
                    if ($t->type === 'debit') {
                        $balance += (float) $t->amount;
                    } else {
                        $balance -= (float) $t->amount;
                    }

                    DB::table('supplier_transactions')
                        ->where('id', $t->id)
                        ->update(['balance_after' => round($balance, 2)]);
                }

                DB::table('suppliers')
                    ->where('id', $supplierId)
                    ->update(['current_balance' => round($balance, 2)]);

                $this->command->info("  المورد #{$supplierId} ({$supplier->name}): الرصيد الجديد = {$balance}");
            }

            $this->command->info('');
            $this->command->info('إعادة احتساب أرصدة الفواتير...');

            $affectedPurchases = Purchase::whereIn('supplier_id', $affectedSupplierIds)
                ->where('status', 'approved')
                ->orderBy('purchase_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($affectedPurchases as $purchase) {
                $directCredits = (float) DB::table('supplier_transactions')
                    ->where('reference_type', Purchase::class)
                    ->where('reference_id', $purchase->id)
                    ->where('type', 'credit')
                    ->sum('amount');

                $paidAmount = $directCredits;
                $remainingAmount = max(0, (float) $purchase->total - $paidAmount);

                DB::table('purchases')
                    ->where('id', $purchase->id)
                    ->update([
                        'paid_amount' => round($paidAmount, 2),
                        'remaining_amount' => round($remainingAmount, 2),
                    ]);

                $this->command->info("  فاتورة #{$purchase->id}: المدفوع = {$paidAmount}, المتبقي = {$remainingAmount}");
            }

            $this->command->info('');
            $this->command->info('ربط مدفوعات الموردين بالفواتير غير المسددة...');

            foreach ($affectedSupplierIds as $supplierId) {
                $supplierPayments = DB::table('supplier_transactions')
                    ->where('supplier_id', $supplierId)
                    ->where('reference_type', 'supplier_payment')
                    ->where('type', 'credit')
                    ->orderBy('transaction_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($supplierPayments as $payment) {
                    $remainingPayment = (float) $payment->amount;

                    $unpaidPurchases = Purchase::where('supplier_id', $supplierId)
                        ->where('status', 'approved')
                        ->where('remaining_amount', '>', 0)
                        ->orderBy('purchase_date', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    foreach ($unpaidPurchases as $purchase) {
                        if ($remainingPayment <= 0) break;

                        $payable = min($remainingPayment, (float) $purchase->remaining_amount);

                        DB::table('purchases')
                            ->where('id', $purchase->id)
                            ->update([
                                'paid_amount' => round((float) $purchase->paid_amount + $payable, 2),
                                'remaining_amount' => round(max(0, (float) $purchase->remaining_amount - $payable), 2),
                            ]);

                        $purchase->paid_amount = (float) $purchase->paid_amount + $payable;
                        $purchase->remaining_amount = max(0, (float) $purchase->remaining_amount - $payable);

                        $remainingPayment -= $payable;
                        $this->command->info("    سداد #{$payment->id} → فاتورة #{$purchase->id}: خصم {$payable}");
                    }
                }
            }

            DB::commit();

            $this->command->info('');
            $this->command->info('تم تطبيق جميع التصحيحات بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('خطأ: ' . $e->getMessage());
            throw $e;
        }
    }
}
