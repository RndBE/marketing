<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // Create Roles
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrator dengan akses penuh'],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Manager approval dan monitoring'],
            ['name' => 'Staff', 'slug' => 'staff', 'description' => 'Staff operasional'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Create Permissions
        $permissions = [
            // User Management
            ['name' => 'Kelola Users', 'slug' => 'manage-users', 'group' => 'User Management', 'description' => 'CRUD users'],
            ['name' => 'Kelola Roles', 'slug' => 'manage-roles', 'group' => 'User Management', 'description' => 'CRUD roles'],
            ['name' => 'Kelola Permissions', 'slug' => 'manage-permissions', 'group' => 'User Management', 'description' => 'CRUD permissions'],

            // Penawaran
            ['name' => 'Lihat Penawaran (Sendiri)', 'slug' => 'view-penawaran', 'group' => 'Penawaran', 'description' => 'Lihat penawaran yang dibuat sendiri'],
            ['name' => 'Buat Penawaran', 'slug' => 'create-penawaran', 'group' => 'Penawaran', 'description' => 'Buat penawaran baru'],
            ['name' => 'Edit Penawaran', 'slug' => 'edit-penawaran', 'group' => 'Penawaran', 'description' => 'Edit penawaran'],
            ['name' => 'Hapus Penawaran', 'slug' => 'delete-penawaran', 'group' => 'Penawaran', 'description' => 'Hapus penawaran'],
            ['name' => 'Approve Penawaran', 'slug' => 'approve-penawaran', 'group' => 'Penawaran', 'description' => 'Approve atau reject penawaran'],
            ['name' => 'Lihat Semua Penawaran', 'slug' => 'view-all-penawaran', 'group' => 'Penawaran', 'description' => 'Lihat semua penawaran'],

            // Price List
            ['name' => 'Kelola Price List', 'slug' => 'manage-pricelist', 'group' => 'Price List', 'description' => 'CRUD price list'],

            // PIC
            ['name' => 'Kelola PIC', 'slug' => 'manage-pic', 'group' => 'PIC', 'description' => 'CRUD PIC'],

            // Alur Penawaran
            ['name' => 'Kelola Alur Approval', 'slug' => 'manage-alur', 'group' => 'Alur Approval', 'description' => 'CRUD alur approval'],

            // Purchase Order
            ['name' => 'Lihat Purchase Order', 'slug' => 'view-purchase-order', 'group' => 'Purchase Order', 'description' => 'Lihat daftar purchase order'],
            ['name' => 'Buat Purchase Order', 'slug' => 'create-purchase-order', 'group' => 'Purchase Order', 'description' => 'Buat purchase order baru'],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'group' => $perm['group'],
                    'description' => $perm['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Assign all permissions to Admin role
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        $allPermissions = DB::table('permissions')->pluck('id');

        foreach ($allPermissions as $permId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $adminRole->id, 'permission_id' => $permId],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Assign Manager role permissions
        $managerRole = DB::table('roles')->where('slug', 'manager')->first();
        $managerPerms = DB::table('permissions')
            ->whereIn('slug', ['view-penawaran', 'view-all-penawaran', 'approve-penawaran', 'manage-pricelist', 'manage-pic'])
            ->pluck('id');

        foreach ($managerPerms as $permId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $managerRole->id, 'permission_id' => $permId],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Assign Staff role permissions
        $staffRole = DB::table('roles')->where('slug', 'staff')->first();
        $staffPerms = DB::table('permissions')
            ->whereIn('slug', ['view-penawaran', 'create-penawaran', 'edit-penawaran'])
            ->pluck('id');

        foreach ($staffPerms as $permId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $staffRole->id, 'permission_id' => $permId],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Assign Admin role to first user (Yanu Hertanto)
        $yanu = DB::table('users')->where('name', 'Yanu Hertanto')->first();
        if ($yanu) {
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $yanu->id, 'role_id' => $adminRole->id],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
