<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROLE_PERMISSIONS = [
        'admin_project' => [
            'view-usulan',
            'create-usulan',
            'edit-usulan',
            'respond-usulan',
        ],
        'sales' => [
            'create-usulan',
        ],
    ];

    public function up(): void
    {
        foreach (self::ROLE_PERMISSIONS as $roleSlug => $permissionSlugs) {
            $this->assignPermissions($roleSlug, $permissionSlugs);
        }
    }

    public function down(): void
    {
        foreach (self::ROLE_PERMISSIONS as $roleSlug => $permissionSlugs) {
            $this->removePermissions($roleSlug, $permissionSlugs);
        }
    }

    private function assignPermissions(string $roleSlug, array $permissionSlugs): void
    {
        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function removePermissions(string $roleSlug, array $permissionSlugs): void
    {
        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id');

        DB::table('permission_role')
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
