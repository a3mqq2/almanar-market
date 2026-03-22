<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // الأصناف
            ['key' => 'products', 'group' => 'products', 'label' => 'الأصناف', 'parent_key' => null, 'sort_order' => 1],
            ['key' => 'products.create', 'group' => 'products', 'label' => 'إضافة صنف', 'parent_key' => 'products', 'sort_order' => 2],
            ['key' => 'products.edit', 'group' => 'products', 'label' => 'تعديل صنف', 'parent_key' => 'products', 'sort_order' => 3],
            ['key' => 'products.delete', 'group' => 'products', 'label' => 'حذف صنف', 'parent_key' => 'products', 'sort_order' => 4],
            ['key' => 'products.units_prices', 'group' => 'products', 'label' => 'الوحدات والأسعار', 'parent_key' => 'products', 'sort_order' => 5],
            ['key' => 'products.purchase', 'group' => 'products', 'label' => 'شراء صنف', 'parent_key' => 'products', 'sort_order' => 6],
            ['key' => 'products.inventory', 'group' => 'products', 'label' => 'تعديل المخزون وسجل الحركات', 'parent_key' => 'products', 'sort_order' => 7],
            ['key' => 'products.barcodes', 'group' => 'products', 'label' => 'باركودات الصنف', 'parent_key' => 'products', 'sort_order' => 8],

            // سجل المبيعات
            ['key' => 'sales', 'group' => 'sales', 'label' => 'سجل المبيعات', 'parent_key' => null, 'sort_order' => 10],

            // الزبائن
            ['key' => 'customers', 'group' => 'customers', 'label' => 'الزبائن', 'parent_key' => null, 'sort_order' => 20],
            ['key' => 'customers.create', 'group' => 'customers', 'label' => 'إضافة زبون', 'parent_key' => 'customers', 'sort_order' => 21],
            ['key' => 'customers.edit', 'group' => 'customers', 'label' => 'تعديل زبون', 'parent_key' => 'customers', 'sort_order' => 22],
            ['key' => 'customers.delete', 'group' => 'customers', 'label' => 'حذف زبون', 'parent_key' => 'customers', 'sort_order' => 23],
            ['key' => 'customers.transactions', 'group' => 'customers', 'label' => 'إضافة حركة مالية', 'parent_key' => 'customers', 'sort_order' => 24],
            ['key' => 'customers.statement', 'group' => 'customers', 'label' => 'كشف الحساب', 'parent_key' => 'customers', 'sort_order' => 25],

            // المشتريات
            ['key' => 'purchases', 'group' => 'purchases', 'label' => 'سجل المشتريات', 'parent_key' => null, 'sort_order' => 30],

            // الموردين
            ['key' => 'suppliers', 'group' => 'suppliers', 'label' => 'الموردين', 'parent_key' => null, 'sort_order' => 40],
            ['key' => 'suppliers.create', 'group' => 'suppliers', 'label' => 'إضافة مورد', 'parent_key' => 'suppliers', 'sort_order' => 41],
            ['key' => 'suppliers.edit', 'group' => 'suppliers', 'label' => 'تعديل مورد', 'parent_key' => 'suppliers', 'sort_order' => 42],
            ['key' => 'suppliers.delete', 'group' => 'suppliers', 'label' => 'حذف مورد', 'parent_key' => 'suppliers', 'sort_order' => 43],
            ['key' => 'suppliers.transactions', 'group' => 'suppliers', 'label' => 'إضافة حركة مالية', 'parent_key' => 'suppliers', 'sort_order' => 44],
            ['key' => 'suppliers.statement', 'group' => 'suppliers', 'label' => 'كشف الحساب', 'parent_key' => 'suppliers', 'sort_order' => 45],

            // جرد المخزون
            ['key' => 'inventory_counts', 'group' => 'inventory_counts', 'label' => 'جرد المخزون', 'parent_key' => null, 'sort_order' => 50],

            // الخزائن
            ['key' => 'cashboxes', 'group' => 'cashboxes', 'label' => 'إدارة الخزائن', 'parent_key' => null, 'sort_order' => 60],
            ['key' => 'cashboxes.create', 'group' => 'cashboxes', 'label' => 'إضافة خزينة', 'parent_key' => 'cashboxes', 'sort_order' => 61],
            ['key' => 'cashboxes.transactions', 'group' => 'cashboxes', 'label' => 'إضافة حركة', 'parent_key' => 'cashboxes', 'sort_order' => 62],
            ['key' => 'cashboxes.statement', 'group' => 'cashboxes', 'label' => 'كشف الحساب', 'parent_key' => 'cashboxes', 'sort_order' => 63],

            // المصروفات
            ['key' => 'expenses', 'group' => 'expenses', 'label' => 'المصروفات', 'parent_key' => null, 'sort_order' => 70],

            // التقارير
            ['key' => 'reports', 'group' => 'reports', 'label' => 'التقارير', 'parent_key' => null, 'sort_order' => 80],

            // المستخدمين
            ['key' => 'users', 'group' => 'users', 'label' => 'المستخدمين', 'parent_key' => null, 'sort_order' => 90],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['key' => $permission['key']],
                $permission
            );
        }

        $allPermissionIds = Permission::pluck('id');

        User::where('role', 'manager')->each(function ($user) use ($allPermissionIds) {
            $user->permissions()->syncWithoutDetaching($allPermissionIds);
        });
    }
}
