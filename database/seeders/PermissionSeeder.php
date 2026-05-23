<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إعادة تعيين الأدوار والصلاحيات المخزنة مؤقتاً (cache)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'api';

        // --- صلاحيات نظام المطعم والمنيو الفعلي فقط ---
        $permissions = [
            // إدارة المنيو والأقسام والطاولات
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'tables.view', 'tables.create', 'tables.update', 'tables.delete',

            // إدارة والتحكم في الطلبات الوسيطة المرسلة من الويتر للكاشير
            'remote_orders.view',      // عرض الطلبات ومراقبتها
            'remote_orders.create',    // إنشاء طلب جديد (خاص بالويتر)
            'remote_orders.confirm',   // تأكيد الطلب وسحبه لشاشة الكاشير المحلي
            'remote_orders.cancel',    // إلغاء الطلب الوسيط

            // إدارة شؤون الموظفين (الويترز والكاشيرز) داخل النظام
            'users.view', 'users.create', 'users.update', 'users.delete',
        ];

        // إنشاء الصلاحيات مع تحديد الحارس
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }

        // --- إنشاء الأدوار الفعلية للمطعم ---

        // 1. دور الـ Super Admin (المالك / المطور)
        // يحصل على كل شيء تلقائياً عبر حلقة فحص Gate::before
        Role::create([
            'name' => 'Super Admin',
            'guard_name' => $guardName,
        ]);

        // 2. دور الـ Admin (مدير المطعم)
        $adminRole = Role::create([
            'name' => 'Admin',
            'guard_name' => $guardName,
        ]);
        // يمتلك كافة صلاحيات النظام للتحكم الكامل
        $adminRole->givePermissionTo(Permission::where('guard_name', $guardName)->get());

        // 3. دور الكاشير (Cashier)
        $cashierRole = Role::create([
            'name' => 'Cashier',
            'guard_name' => $guardName,
        ]);
        // يراقب الطلبات، ويؤكدها أو يلغيها، ويستعرض المنيو والطاولات
        $cashierRole->givePermissionTo([
            'categories.view',
            'products.view',
            'tables.view',
            'remote_orders.view',
            'remote_orders.confirm',
            'remote_orders.cancel',
        ]);

        // 4. دور الويتر (Waiter)
        $waiterRole = Role::create([
            'name' => 'Waiter',
            'guard_name' => $guardName,
        ]);
        // يستعرض المنيو وينشئ طلبات الطاولات ويرى طلباته السابقة فقط
        $waiterRole->givePermissionTo([
            'categories.view',
            'products.view',
            'tables.view',
            'remote_orders.view',
            'remote_orders.create',
        ]);
    }
}
