<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. إنشاء مستخدم Super Admin
        $superAdmin = User::create([
            'full_name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('Super Admin');

        // 2. إنشاء مستخدم Admin (مدير المطعم)
        $adminUser = User::create([
            'full_name' => 'Restaurant Manager',
            'username' => 'admin',
            'email' => 'admin@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('Admin');

        // 3. إنشاء حساب كاشير تجريبي (Cashier)
        $cashierUser = User::create([
            'full_name' => 'Main Cashier',
            'username' => 'cashier',
            'email' => 'cashier@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        $cashierUser->assignRole('Cashier');

        // 4. إنشاء حساب ويتر تجريبي (Waiter)
        $waiterUser = User::create([
            'full_name' => 'John Waiter',
            'username' => 'waiter',
            'email' => 'waiter@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        $waiterUser->assignRole('Waiter');
    }
}
