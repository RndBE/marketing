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
                'name' => 'Lihat Laporan Marketing',
                'slug' => 'view-marketing-report',
                'group' => 'Laporan Marketing',
                'description' => 'Lihat laporan perjalanan marketing',
            ],
            [
                'name' => 'Lihat Semua Laporan Marketing',
                'slug' => 'view-all-marketing-report',
                'group' => 'Laporan Marketing',
                'description' => 'Lihat semua laporan perjalanan marketing',
            ],
            [
                'name' => 'Buat Laporan Marketing',
                'slug' => 'create-marketing-report',
                'group' => 'Laporan Marketing',
                'description' => 'Buat laporan perjalanan marketing',
            ],
            [
                'name' => 'Edit Laporan Marketing',
                'slug' => 'edit-marketing-report',
                'group' => 'Laporan Marketing',
                'description' => 'Edit laporan perjalanan marketing',
            ],
            [
                'name' => 'Hapus Laporan Marketing',
                'slug' => 'delete-marketing-report',
                'group' => 'Laporan Marketing',
                'description' => 'Hapus laporan perjalanan marketing',
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
                'view-marketing-report',
                'view-all-marketing-report',
                'create-marketing-report',
                'edit-marketing-report',
                'delete-marketing-report',
            ],
            'sales' => [
                'view-marketing-report',
                'create-marketing-report',
                'edit-marketing-report',
            ],
            'busdev' => [
                'view-marketing-report',
                'create-marketing-report',
                'edit-marketing-report',
            ],
            'direktur' => [
                'view-marketing-report',
                'view-all-marketing-report',
            ],
            'manager' => [
                'view-marketing-report',
                'view-all-marketing-report',
                'create-marketing-report',
                'edit-marketing-report',
                'delete-marketing-report',
            ],
            'staff' => [
                'view-marketing-report',
                'create-marketing-report',
                'edit-marketing-report',
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
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $slugs = [
            'view-marketing-report',
            'view-all-marketing-report',
            'create-marketing-report',
            'edit-marketing-report',
            'delete-marketing-report',
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
