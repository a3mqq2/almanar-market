<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'قطعة', 'symbol' => 'قطعة', 'is_default' => true],
            ['name' => 'كرتون', 'symbol' => 'كرتون', 'is_default' => false],
            ['name' => 'كيلو', 'symbol' => 'كجم', 'is_default' => false],
            ['name' => 'جرام', 'symbol' => 'جم', 'is_default' => false],
            ['name' => 'لتر', 'symbol' => 'لتر', 'is_default' => false],
            ['name' => 'علبة', 'symbol' => 'علبة', 'is_default' => false],
            ['name' => 'باكيت', 'symbol' => 'باكيت', 'is_default' => false],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['name' => $unit['name']],
                $unit
            );
        }
    }
}
