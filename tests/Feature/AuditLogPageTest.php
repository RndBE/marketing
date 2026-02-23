<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

test('audit log page requires permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

test('user with permission can open audit log page', function () {
    $user = User::factory()->create();
    $role = Role::create([
        'name' => 'Audit Admin',
        'slug' => 'audit-admin',
    ]);
    $permission = Permission::firstOrCreate([
        'slug' => 'view-audit-logs',
    ], [
        'name' => 'Lihat Audit Log',
        'group' => 'User Management',
    ]);

    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role->id);

    AuditLog::create([
        'user_id' => $user->id,
        'action' => 'auth.login',
        'method' => 'POST',
        'route_name' => 'login',
        'url' => 'http://localhost/login',
        'ip_address' => '127.0.0.1',
        'payload' => ['input' => ['email' => $user->email]],
        'status_code' => 302,
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('audit-logs.index'))
        ->assertOk()
        ->assertSee('Audit Log')
        ->assertSee('auth.login');
});
