<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'view-audit-logs'],
            [
                'name' => 'Lihat Audit Log',
                'group' => 'User Management',
                'description' => 'Melihat jejak aktivitas aplikasi',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $viewAuditLogsPermissionId = DB::table('permissions')
            ->where('slug', 'view-audit-logs')
            ->value('id');

        $manageRolesPermissionId = DB::table('permissions')
            ->where('slug', 'manage-roles')
            ->value('id');

        $targetRoleIds = collect();

        if ($manageRolesPermissionId) {
            $targetRoleIds = DB::table('permission_role')
                ->where('permission_id', $manageRolesPermissionId)
                ->pluck('role_id');
        }

        if ($targetRoleIds->isEmpty()) {
            $targetRoleIds = DB::table('roles')
                ->where('slug', 'admin')
                ->pluck('id');
        }

        foreach ($targetRoleIds->unique() as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $viewAuditLogsPermissionId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('slug', 'view-audit-logs')
            ->value('id');

        if (!$permissionId) {
            return;
        }

        DB::table('permission_role')
            ->where('permission_id', $permissionId)
            ->delete();
    }
};
