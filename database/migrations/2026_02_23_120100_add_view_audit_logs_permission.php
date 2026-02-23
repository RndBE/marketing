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

        $permissionId = DB::table('permissions')
            ->where('slug', 'view-audit-logs')
            ->value('id');

        $adminRoleIds = DB::table('roles')
            ->where('slug', 'admin')
            ->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'view-audit-logs')->first();
        if (!$permission) {
            return;
        }

        DB::table('permission_role')->where('permission_id', $permission->id)->delete();
        DB::table('permissions')->where('id', $permission->id)->delete();
    }
};
