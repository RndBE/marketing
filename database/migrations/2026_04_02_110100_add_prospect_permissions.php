<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            [
                'name' => 'Lihat Prospek',
                'slug' => 'view-prospect',
                'group' => 'Prospek',
                'description' => 'Lihat daftar lead atau prospek',
            ],
            [
                'name' => 'Lihat Semua Prospek',
                'slug' => 'view-all-prospect',
                'group' => 'Prospek',
                'description' => 'Lihat semua lead atau prospek',
            ],
            [
                'name' => 'Buat Prospek',
                'slug' => 'create-prospect',
                'group' => 'Prospek',
                'description' => 'Tambah lead atau prospek baru',
            ],
            [
                'name' => 'Edit Prospek',
                'slug' => 'edit-prospect',
                'group' => 'Prospek',
                'description' => 'Edit lead atau prospek',
            ],
            [
                'name' => 'Hapus Prospek',
                'slug' => 'delete-prospect',
                'group' => 'Prospek',
                'description' => 'Hapus lead atau prospek',
            ],
        ];

        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')->where('slug', $permission['slug'])->first();

            if ($existing) {
                DB::table('permissions')
                    ->where('id', $existing->id)
                    ->update([
                        'name' => $permission['name'],
                        'group' => $permission['group'],
                        'description' => $permission['description'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('permissions')->insert([
                    'name' => $permission['name'],
                    'slug' => $permission['slug'],
                    'group' => $permission['group'],
                    'description' => $permission['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $assignments = [
            'admin' => [
                'view-prospect',
                'view-all-prospect',
                'create-prospect',
                'edit-prospect',
                'delete-prospect',
            ],
            'sales' => [
                'view-prospect',
                'create-prospect',
                'edit-prospect',
                'delete-prospect',
            ],
            'busdev' => [
                'view-prospect',
                'create-prospect',
                'edit-prospect',
                'delete-prospect',
            ],
            'direktur' => [
                'view-prospect',
                'view-all-prospect',
            ],
            'manager' => [
                'view-prospect',
                'view-all-prospect',
                'create-prospect',
                'edit-prospect',
                'delete-prospect',
            ],
            'staff' => [
                'view-prospect',
                'create-prospect',
                'edit-prospect',
            ],
        ];

        foreach ($assignments as $roleSlug => $permissionSlugs) {
            $role = DB::table('roles')->where('slug', $roleSlug)->first();
            if (!$role) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('slug', $permissionSlugs)
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $slugs = [
            'view-prospect',
            'view-all-prospect',
            'create-prospect',
            'edit-prospect',
            'delete-prospect',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $slugs)
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
