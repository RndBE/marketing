<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Deploy production hanya menjalankan `migrate --force`, tidak menjalankan seeder.
// Berkas ini memastikan izin Purchase Order tetap terbentuk lewat jalur migration saja,
// lalu siap diberikan ke role lewat halaman Kelola Roles.

function runPurchaseOrderPermissionMigration(): void
{
    $migration = require database_path('migrations/2026_08_06_160000_add_purchase_order_permissions.php');
    $migration->up();
}

test('purchase order permissions exist after migrating without running seeders', function () {
    foreach (['view-purchase-order', 'create-purchase-order'] as $slug) {
        $permission = Permission::where('slug', $slug)->first();

        expect($permission)->not()->toBeNull("Izin {$slug} tidak dibuat oleh migration")
            ->and($permission->group)->toBe('Purchase Order');
    }
});

test('migration does not hand the permissions to any role on its own', function () {
    // Siapa yang boleh mengakses Purchase Order ditentukan lewat UI, bukan oleh deploy.
    foreach (['admin', 'sales', 'admin_project'] as $slug) {
        Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
    }

    runPurchaseOrderPermissionMigration();

    $permissionIds = Permission::whereIn('slug', ['view-purchase-order', 'create-purchase-order'])->pluck('id');

    expect(DB::table('permission_role')->whereIn('permission_id', $permissionIds)->count())->toBe(0);
});

test('migration is safe to run twice without duplicating rows', function () {
    runPurchaseOrderPermissionMigration();
    runPurchaseOrderPermissionMigration();

    expect(Permission::where('slug', 'view-purchase-order')->count())->toBe(1)
        ->and(Permission::where('slug', 'create-purchase-order')->count())->toBe(1);
});

test('purchase order permissions show up on the management pages so roles can be ticked', function () {
    // Halaman RBAC hanya untuk role admin, dan admin harus memegang manage-roles.
    $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
    $managePermission = Permission::firstOrCreate(
        ['slug' => 'manage-roles'],
        ['name' => 'Kelola Roles', 'group' => 'User Management']
    );
    DB::table('permission_role')->updateOrInsert(
        ['role_id' => $adminRole->id, 'permission_id' => $managePermission->id],
        ['created_at' => now(), 'updated_at' => now()]
    );

    runPurchaseOrderPermissionMigration();

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole->id);

    // Kelola Permission: izin PO terdaftar beserta grupnya.
    $this->actingAs($admin)
        ->get(route('permissions.index'))
        ->assertOk()
        ->assertSee('Lihat Purchase Order')
        ->assertSee('Buat Purchase Order')
        ->assertSee('Purchase Order');

    // Kelola Roles: izin PO tersedia sebagai pilihan untuk dicentang ke role mana pun.
    $this->actingAs($admin)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertSee('Lihat Purchase Order')
        ->assertSee('Buat Purchase Order');
});

test('a role granted the permissions through the UI can open the purchase order pages', function () {
    runPurchaseOrderPermissionMigration();

    // Meniru pencentangan di halaman Kelola Roles.
    $role = Role::firstOrCreate(['slug' => 'sales'], ['name' => 'Sales']);
    foreach (Permission::whereIn('slug', ['view-purchase-order', 'create-purchase-order'])->pluck('id') as $permissionId) {
        DB::table('permission_role')->updateOrInsert(
            ['role_id' => $role->id, 'permission_id' => $permissionId],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    $user = User::factory()->create();
    $user->roles()->attach($role->id);

    expect($user->hasPermission('view-purchase-order'))->toBeTrue()
        ->and($user->hasPermission('create-purchase-order'))->toBeTrue();

    $this->actingAs($user)->get(route('purchase-orders.index'))->assertOk();
    $this->actingAs($user)->get(route('purchase-orders.create'))->assertOk();
});

test('a role without the permissions still cannot open the purchase order pages', function () {
    runPurchaseOrderPermissionMigration();

    $role = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
    $user = User::factory()->create();
    $user->roles()->attach($role->id);

    $this->actingAs($user)->get(route('purchase-orders.index'))->assertForbidden();
});
