<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsulanPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Permissions
        $permissions = [
            ['name' => 'Lihat Usulan', 'slug' => 'view-usulan', 'group' => 'Usulan Penawaran', 'description' => 'Lihat daftar usulan'],
            ['name' => 'Buat Usulan', 'slug' => 'create-usulan', 'group' => 'Usulan Penawaran', 'description' => 'Buat usulan baru'],
            ['name' => 'Edit Usulan', 'slug' => 'edit-usulan', 'group' => 'Usulan Penawaran', 'description' => 'Edit usulan sendiri'],
            ['name' => 'Hapus Usulan', 'slug' => 'delete-usulan', 'group' => 'Usulan Penawaran', 'description' => 'Hapus usulan'],
            ['name' => 'Tanggapi Usulan', 'slug' => 'respond-usulan', 'group' => 'Usulan Penawaran', 'description' => 'Tanggapi dan approve usulan'],
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

        // 2. Assign to Roles

        // Admin: All
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        if ($adminRole) {
            $perms = DB::table('permissions')->where('group', 'Usulan Penawaran')->pluck('id');
            foreach ($perms as $permId) {
                DB::table('permission_role')->updateOrInsert([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permId
                ]);
            }
        }

        // Manager: View, Respond, Delete
        $managerRole = DB::table('roles')->where('slug', 'manager')->first();
        if ($managerRole) {
            $perms = DB::table('permissions')
                ->whereIn('slug', ['view-usulan', 'respond-usulan', 'delete-usulan'])
                ->pluck('id');
            foreach ($perms as $permId) {
                DB::table('permission_role')->updateOrInsert([
                    'role_id' => $managerRole->id,
                    'permission_id' => $permId
                ]);
            }
        }

        // Staff (Sales/BizDev): View, Create, Edit
        $staffRole = DB::table('roles')->where('slug', 'staff')->first();
        if ($staffRole) {
            $perms = DB::table('permissions')
                ->whereIn('slug', ['view-usulan', 'create-usulan', 'edit-usulan', 'respond-usulan']) // Asumsi Staff Sales juga bisa respond/approve level awal jika perlu, atau hapus respond jika hanya Manager
                ->pluck('id');

            // Revisi: Staff hanya create/edit/view. Respond biasanya level atas atau sesama sales senior. 
            // Sesuai request user: "Sales bisa menanggapi". Jika Sales = Staff, maka Staff butuh respond-usulan.
            // Mari kasih respond-usulan ke Staff juga agar Sales bisa menanggapi Usulan Business Dev.

            foreach ($perms as $permId) {
                DB::table('permission_role')->updateOrInsert([
                    'role_id' => $staffRole->id,
                    'permission_id' => $permId
                ]);
            }
        }
    }
}
