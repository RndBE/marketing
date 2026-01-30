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
            DB::table('roles')->insert([
                'name' => $role['name'],
                'slug' => $role['slug'],
                'description' => $role['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Permissions
        $permissions = [
            // User Management
            ['name' => 'Kelola Users', 'slug' => 'manage-users', 'group' => 'User Management', 'description' => 'CRUD users'],
            ['name' => 'Kelola Roles', 'slug' => 'manage-roles', 'group' => 'User Management', 'description' => 'CRUD roles'],
            ['name' => 'Kelola Permissions', 'slug' => 'manage-permissions', 'group' => 'User Management', 'description' => 'CRUD permissions'],

            // Penawaran
            ['name' => 'Lihat Penawaran', 'slug' => 'view-penawaran', 'group' => 'Penawaran', 'description' => 'Lihat daftar dan detail penawaran'],
            ['name' => 'Buat Penawaran', 'slug' => 'create-penawaran', 'group' => 'Penawaran', 'description' => 'Buat penawaran baru'],
            ['name' => 'Edit Penawaran', 'slug' => 'edit-penawaran', 'group' => 'Penawaran', 'description' => 'Edit penawaran'],
            ['name' => 'Hapus Penawaran', 'slug' => 'delete-penawaran', 'group' => 'Penawaran', 'description' => 'Hapus penawaran'],
            ['name' => 'Approve Penawaran', 'slug' => 'approve-penawaran', 'group' => 'Penawaran', 'description' => 'Approve atau reject penawaran'],

            // Price List
            ['name' => 'Kelola Price List', 'slug' => 'manage-pricelist', 'group' => 'Price List', 'description' => 'CRUD price list'],

            // PIC
            ['name' => 'Kelola PIC', 'slug' => 'manage-pic', 'group' => 'PIC', 'description' => 'CRUD PIC'],

            // Alur Penawaran
            ['name' => 'Kelola Alur Approval', 'slug' => 'manage-alur', 'group' => 'Alur Approval', 'description' => 'CRUD alur approval'],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->insert([
                'name' => $perm['name'],
                'slug' => $perm['slug'],
                'group' => $perm['group'],
                'description' => $perm['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign all permissions to Admin role
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        $allPermissions = DB::table('permissions')->pluck('id');

        foreach ($allPermissions as $permId) {
            DB::table('permission_role')->insert([
                'role_id' => $adminRole->id,
                'permission_id' => $permId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign Manager role permissions
        $managerRole = DB::table('roles')->where('slug', 'manager')->first();
        $managerPerms = DB::table('permissions')
            ->whereIn('slug', ['view-penawaran', 'approve-penawaran', 'manage-pricelist', 'manage-pic'])
            ->pluck('id');

        foreach ($managerPerms as $permId) {
            DB::table('permission_role')->insert([
                'role_id' => $managerRole->id,
                'permission_id' => $permId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign Staff role permissions
        $staffRole = DB::table('roles')->where('slug', 'staff')->first();
        $staffPerms = DB::table('permissions')
            ->whereIn('slug', ['view-penawaran', 'create-penawaran', 'edit-penawaran'])
            ->pluck('id');

        foreach ($staffPerms as $permId) {
            DB::table('permission_role')->insert([
                'role_id' => $staffRole->id,
                'permission_id' => $permId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign Admin role to first user (Yanu Hertanto)
        $yanu = DB::table('users')->where('name', 'Yanu Hertanto')->first();
        if ($yanu) {
            DB::table('role_user')->insert([
                'user_id' => $yanu->id,
                'role_id' => $adminRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
