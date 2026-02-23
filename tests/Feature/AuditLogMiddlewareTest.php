<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('successful login is recorded in audit logs', function () {
    $user = User::factory()->create([
        'password' => Hash::make('audit-pass-123'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'audit-pass-123',
    ]);

    $response->assertRedirect(route('penawaran.index', absolute: false));

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'auth.login',
        'method' => 'POST',
        'route_name' => 'login',
        'user_id' => $user->id,
    ]);
});

test('failed login is recorded in audit logs', function () {
    $user = User::factory()->create([
        'password' => Hash::make('audit-pass-123'),
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'auth.login_failed',
        'method' => 'POST',
        'route_name' => 'login',
        'user_id' => null,
    ]);
});

test('logout is recorded in audit logs', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'auth.logout',
        'method' => 'POST',
        'route_name' => 'logout',
        'user_id' => $user->id,
    ]);
});

test('authenticated write request is recorded in audit logs', function () {
    $user = User::factory()->create();
    $updatedEmail = 'audit+' . uniqid() . '@example.com';

    $response = $this->actingAs($user)->patch('/profile', [
        'name' => 'Nama Audit Baru',
        'email' => $updatedEmail,
    ]);

    $response->assertRedirect(route('profile.edit', absolute: false));

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'profile.update',
        'method' => 'PATCH',
        'route_name' => 'profile.update',
        'user_id' => $user->id,
    ]);
});
