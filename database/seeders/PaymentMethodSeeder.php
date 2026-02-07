<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'نقدي', 'code' => 'cash', 'requires_cashbox' => true, 'sort_order' => 1],
            ['name' => 'بطاقة', 'code' => 'card', 'requires_cashbox' => true, 'sort_order' => 2],
            ['name' => 'يسر باي', 'code' => 'yousrpay', 'requires_cashbox' => false, 'sort_order' => 3],
            ['name' => 'مصرفي باي', 'code' => 'masrafypay', 'requires_cashbox' => false, 'sort_order' => 4],
            ['name' => 'موبي كاش', 'code' => 'mobicash', 'requires_cashbox' => false, 'sort_order' => 5],
            ['name' => 'ادفعلي', 'code' => 'edafaly', 'requires_cashbox' => false, 'sort_order' => 7],
            ['name' => 'سداد', 'code' => 'sadad', 'requires_cashbox' => false, 'sort_order' => 8],
            ['name' => 'وان باي - الجمهورية', 'code' => 'sadad', 'requires_cashbox' => false, 'sort_order' => 8],
            ['name' => 'وان باي - التجاري', 'code' => 'sadad', 'requires_cashbox' => false, 'sort_order' => 8],
            ['name' => 'وان باي - الوحدة', 'code' => 'sadad', 'requires_cashbox' => false, 'sort_order' => 8],
            ['name' => 'وان باي - شمال افريقيا', 'code' => 'sadad', 'requires_cashbox' => false, 'sort_order' => 8],
            ['name' => 'موبي ناب', 'code' => 'sadad', 'requires_cashbox' => false, 'sort_order' => 8],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
