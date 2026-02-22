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
            $affectedSupplierIds = collect();
            $fixCount = 0;

            $purchases = Purchase::where('status', 'approved')
                ->whereIn('payment_type', ['cash', 'bank'])
                ->with('supplier')
                ->get();

            $this->command->info('فحص فواتير الكاش/البنك...');
            $this->command->info("عدد الفواتير: {$purchases->count()}");
            $this->command->info('');

            foreach ($purchases as $purchase) {
                $existingDebit = DB::table('supplier_transactions')
                    ->where('reference_type', Purchase::class)
                    ->where('reference_id', $purchase->id)
                    ->where('type', 'debit')
                    ->first();

                $existingCredit = DB::table('supplier_transactions')
                    ->where('reference_type', Purchase::class)
                    ->where('reference_id', $purchase->id)
                    ->where('type', 'credit')
                    ->first();

                if ($existingDebit && abs((float) $existingDebit->amount - (float) $purchase->total) > 0.01) {
                    $oldAmount = $existingDebit->amount;
                    DB::table('supplier_transactions')
                        ->where('id', $existingDebit->id)
                        ->update([
                            'amount' => $purchase->total,
                            'description' => "فاتورة مشتريات #{$purchase->id}",
                        ]);

                    $this->command->info("  فاتورة #{$purchase->id}: تحديث قيد المدين #{$existingDebit->id} من {$oldAmount} إلى {$purchase->total}");
                    $affectedSupplierIds->push($purchase->supplier_id);
                    $fixCount++;

                } elseif (!$existingDebit && $existingCredit) {
                    DB::table('supplier_transactions')->insert([
                        'supplier_id' => $purchase->supplier_id,
                        'type' => 'debit',
                        'amount' => $purchase->total,
                        'balance_after' => 0,
                        'description' => "فاتورة مشتريات #{$purchase->id}",
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                        'cashbox_id' => null,
                        'transaction_date' => $existingCredit->transaction_date,
                        'created_by' => $existingCredit->created_by,
                        'created_at' => $existingCredit->created_at,
                        'updated_at' => now(),
                    ]);

                    $this->command->info("  فاتورة #{$purchase->id}: إضافة قيد مدين مفقود بمبلغ {$purchase->total}");
                    $affectedSupplierIds->push($purchase->supplier_id);
                    $fixCount++;
                }
            }

            if ($fixCount === 0) {
                $this->command->info('لا توجد قيود تحتاج تصحيح.');
                DB::rollBack();
                return;
            }

            $affectedSupplierIds = $affectedSupplierIds->unique();
            $this->command->info('');
            $this->command->info("تم تصحيح {$fixCount} قيد لـ {$affectedSupplierIds->count()} مورد.");
            $this->command->info('');
            $this->command->info('إعادة احتساب الأرصدة...');

            foreach ($affectedSupplierIds as $supplierId) {
                $supplier = Supplier::find($supplierId);
                if (!$supplier) continue;

                $balance = (float) $supplier->opening_balance;

                $transactions = DB::table('supplier_transactions')
                    ->where('supplier_id', $supplierId)
                    ->orderBy('transaction_date', 'asc')
                    ->orderByRaw("CASE WHEN type = 'debit' THEN 0 ELSE 1 END ASC")
                    ->orderBy('created_at', 'asc')
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

            $allPurchases = Purchase::whereIn('supplier_id', $affectedSupplierIds)
                ->where('status', 'approved')
                ->orderBy('purchase_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($allPurchases as $purchase) {
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

                if ($supplierPayments->isEmpty()) continue;

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
