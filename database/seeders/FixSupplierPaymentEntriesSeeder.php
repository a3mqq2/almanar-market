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
            $fixCount = 0;

            $purchases = Purchase::where('status', 'approved')
                ->whereIn('payment_type', ['cash', 'bank'])
                ->get();

            $this->command->info("=== فحص فواتير الكاش/البنك: {$purchases->count()} ===");
            $this->command->info('');

            foreach ($purchases as $purchase) {
                $entries = DB::table('supplier_transactions')
                    ->where('reference_type', Purchase::class)
                    ->where('reference_id', $purchase->id)
                    ->orderBy('id', 'asc')
                    ->get();

                if ($entries->isEmpty()) {
                    $this->command->warn("  #{$purchase->id}: لا توجد قيود!");
                    continue;
                }

                $debits = $entries->where('type', 'debit')->sortBy('id')->values();
                $credits = $entries->where('type', 'credit')->sortBy('id')->values();

                if ($debits->isEmpty() && $credits->isNotEmpty()) {
                    $first = $credits->first();

                    DB::table('supplier_transactions')->where('id', $first->id)->update([
                        'type' => 'debit',
                        'amount' => $purchase->total,
                        'description' => "فاتورة مشتريات #{$purchase->id}",
                        'cashbox_id' => null,
                        'updated_at' => now(),
                    ]);

                    DB::table('supplier_transactions')->insert([
                        'supplier_id' => $purchase->supplier_id,
                        'type' => 'credit',
                        'amount' => $purchase->total,
                        'balance_after' => 0,
                        'description' => "سداد فاتورة مشتريات #{$purchase->id}",
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                        'cashbox_id' => $first->cashbox_id,
                        'transaction_date' => $first->transaction_date,
                        'created_by' => $first->created_by,
                        'created_at' => $first->created_at,
                        'updated_at' => now(),
                    ]);

                    $this->command->info("  #{$purchase->id}: credit→debit + credit جديد");
                    $fixCount++;

                } elseif ($debits->isNotEmpty() && $credits->isNotEmpty()) {
                    $firstDebit = $debits->first();
                    $firstCredit = $credits->first();
                    $changed = false;

                    if ((int) $firstDebit->id > (int) $firstCredit->id) {
                        $cashboxId = $firstCredit->cashbox_id;

                        DB::table('supplier_transactions')->where('id', $firstCredit->id)->update([
                            'type' => 'debit',
                            'amount' => $purchase->total,
                            'description' => "فاتورة مشتريات #{$purchase->id}",
                            'cashbox_id' => null,
                            'updated_at' => now(),
                        ]);

                        DB::table('supplier_transactions')->where('id', $firstDebit->id)->update([
                            'type' => 'credit',
                            'amount' => $purchase->total,
                            'description' => "سداد فاتورة مشتريات #{$purchase->id}",
                            'cashbox_id' => $cashboxId,
                            'updated_at' => now(),
                        ]);

                        $this->command->info("  #{$purchase->id}: تبديل + تصحيح مبلغ السداد → {$purchase->total}");
                        $changed = true;
                    } else {
                        if (abs((float) $firstDebit->amount - (float) $purchase->total) > 0.01) {
                            DB::table('supplier_transactions')->where('id', $firstDebit->id)->update([
                                'amount' => $purchase->total,
                                'updated_at' => now(),
                            ]);
                            $this->command->info("  #{$purchase->id}: تصحيح مدين → {$purchase->total}");
                            $changed = true;
                        }

                        if (abs((float) $firstCredit->amount - (float) $purchase->total) > 0.01) {
                            $old = $firstCredit->amount;
                            DB::table('supplier_transactions')->where('id', $firstCredit->id)->update([
                                'amount' => $purchase->total,
                                'updated_at' => now(),
                            ]);
                            $this->command->info("  #{$purchase->id}: تصحيح سداد {$old} → {$purchase->total}");
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $fixCount++;
                    } else {
                        $this->command->line("  #{$purchase->id}: OK");
                    }

                } elseif ($debits->isNotEmpty() && $credits->isEmpty()) {
                    $firstDebit = $debits->first();

                    if (abs((float) $firstDebit->amount - (float) $purchase->total) > 0.01) {
                        DB::table('supplier_transactions')->where('id', $firstDebit->id)->update([
                            'amount' => $purchase->total,
                            'updated_at' => now(),
                        ]);
                    }

                    if ($debits->count() >= 2) {
                        $lastDebit = $debits->last();
                        DB::table('supplier_transactions')->where('id', $lastDebit->id)->update([
                            'type' => 'credit',
                            'amount' => $purchase->total,
                            'description' => "سداد فاتورة مشتريات #{$purchase->id}",
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('supplier_transactions')->insert([
                            'supplier_id' => $purchase->supplier_id,
                            'type' => 'credit',
                            'amount' => $purchase->total,
                            'balance_after' => 0,
                            'description' => "سداد فاتورة مشتريات #{$purchase->id}",
                            'reference_type' => Purchase::class,
                            'reference_id' => $purchase->id,
                            'cashbox_id' => null,
                            'transaction_date' => $firstDebit->transaction_date,
                            'created_by' => $firstDebit->created_by,
                            'created_at' => $firstDebit->created_at,
                            'updated_at' => now(),
                        ]);
                    }
                    $this->command->info("  #{$purchase->id}: إصلاح كامل");
                    $fixCount++;
                }
            }

            $this->command->info('');
            $this->command->info("=== تصحيحات: {$fixCount} ===");
            $this->command->info('');

            $allSupplierIds = Supplier::pluck('id');
            $this->command->info("=== إعادة احتساب أرصدة {$allSupplierIds->count()} مورد ===");

            foreach ($allSupplierIds as $supplierId) {
                $supplier = Supplier::find($supplierId);
                if (!$supplier) continue;

                $balance = (float) $supplier->opening_balance;
                $oldBalance = (float) $supplier->current_balance;

                $transactions = DB::table('supplier_transactions')
                    ->where('supplier_id', $supplierId)
                    ->orderBy('id', 'asc')
                    ->get();

                if ($transactions->isEmpty()) continue;

                foreach ($transactions as $t) {
                    $balance += $t->type === 'debit' ? (float) $t->amount : -(float) $t->amount;

                    DB::table('supplier_transactions')
                        ->where('id', $t->id)
                        ->update(['balance_after' => round($balance, 2)]);
                }

                DB::table('suppliers')
                    ->where('id', $supplierId)
                    ->update(['current_balance' => round($balance, 2)]);

                if (abs($oldBalance - $balance) > 0.01) {
                    $this->command->info("  #{$supplierId} ({$supplier->name}): {$oldBalance} → {$balance}");
                }
            }

            $this->command->info('');
            $this->command->info('=== إعادة احتساب أرصدة الفواتير ===');

            $allPurchases = Purchase::whereIn('supplier_id', $allSupplierIds)
                ->where('status', 'approved')
                ->orderBy('purchase_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($allPurchases as $p) {
                $directCredits = (float) DB::table('supplier_transactions')
                    ->where('reference_type', Purchase::class)
                    ->where('reference_id', $p->id)
                    ->where('type', 'credit')
                    ->sum('amount');

                DB::table('purchases')->where('id', $p->id)->update([
                    'paid_amount' => round($directCredits, 2),
                    'remaining_amount' => round(max(0, (float) $p->total - $directCredits), 2),
                ]);
            }

            $this->command->info('');
            $this->command->info('=== ربط المدفوعات بالفواتير ===');

            $supplierIdsWithPayments = DB::table('supplier_transactions')
                ->where('reference_type', 'supplier_payment')
                ->where('type', 'credit')
                ->distinct()
                ->pluck('supplier_id');

            foreach ($supplierIdsWithPayments as $supplierId) {
                $payments = DB::table('supplier_transactions')
                    ->where('supplier_id', $supplierId)
                    ->where('reference_type', 'supplier_payment')
                    ->where('type', 'credit')
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($payments as $payment) {
                    $remaining = (float) $payment->amount;

                    $unpaid = Purchase::where('supplier_id', $supplierId)
                        ->where('status', 'approved')
                        ->where('remaining_amount', '>', 0)
                        ->orderBy('purchase_date', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    foreach ($unpaid as $p) {
                        if ($remaining <= 0) break;

                        $payable = min($remaining, (float) $p->remaining_amount);

                        DB::table('purchases')->where('id', $p->id)->update([
                            'paid_amount' => round((float) $p->paid_amount + $payable, 2),
                            'remaining_amount' => round(max(0, (float) $p->remaining_amount - $payable), 2),
                        ]);

                        $p->paid_amount = (float) $p->paid_amount + $payable;
                        $p->remaining_amount = max(0, (float) $p->remaining_amount - $payable);
                        $remaining -= $payable;
                    }
                }
            }

            DB::commit();

            $this->command->info('');
            $this->command->info('=== تم بنجاح ===');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('خطأ: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }
}
