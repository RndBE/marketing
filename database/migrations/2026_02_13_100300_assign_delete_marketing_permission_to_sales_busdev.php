<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permission = DB::table('permissions')->where('slug', 'delete-marketing-report')->first();
        if (!$permission) {
            return;
        }

        $now = now();
        $roleIds = DB::table('roles')->whereIn('slug', ['sales', 'busdev'])->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permission->id],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'delete-marketing-report')->first();
        if (!$permission) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('slug', ['sales', 'busdev'])->pluck('id');

        if ($roleIds->isNotEmpty()) {
            DB::table('permission_role')
                ->where('permission_id', $permission->id)
                ->whereIn('role_id', $roleIds)
                ->delete();
        }
    }
};
