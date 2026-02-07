<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'مدير النظام',
            'username' => 'admin',
            'email' => 'admin@demo.com',
            'password' => '123123123',
            'role' => 'manager',
            'status' => true,
        ]);

        $this->call([
            UnitSeeder::class,
            PaymentMethodSeeder::class,
            UnitSeeder::class,
        ]);
    }
}
