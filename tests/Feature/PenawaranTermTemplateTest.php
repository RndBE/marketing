<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

function penawaranTermTemplateTestUser(Company $company): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Term Template Tester ' . uniqid(),
        'slug' => 'term-template-tester-' . uniqid(),
    ]);
    $permission = Permission::firstOrCreate(
        ['slug' => 'edit-penawaran'],
        ['name' => 'Edit Penawaran', 'group' => 'Penawaran']
    );

    $role->permissions()->syncWithoutDetaching([$permission->id]);
    $user->roles()->attach($role->id);

    return $user;
}

test('user can add active penawaran term template', function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);

    $company = Company::firstOrCreate(
        ['code' => 'TERM-TEST'],
        ['name' => 'Term Template Test Company']
    );
    $user = penawaranTermTemplateTestUser($company);

    $response = $this->actingAs($user)
        ->post(route('term_templates.store'), [
            'isi' => 'Keterangan test baru',
            'is_active' => '1',
        ]);

    $response->assertRedirect(route('term_templates.index'));

    $this->assertDatabaseHas('penawaran_term_templates', [
        'company_id' => $company->id,
        'isi' => 'Keterangan test baru',
        'is_active' => true,
        'group_name' => null,
    ]);
});
