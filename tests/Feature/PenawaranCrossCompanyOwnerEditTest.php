<?php

use App\Models\Company;
use App\Models\Penawaran;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

function crossCompanyEditUser(Company $company, array $permissionSlugs): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Cross Company Edit Tester '.uniqid(),
        'slug' => 'cross-company-edit-tester-'.uniqid(),
    ]);

    foreach ($permissionSlugs as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Penawaran']
        );
        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}

function crossCompanyEditOffer(Company $company, User $owner, string $title): Penawaran
{
    return Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $owner->id,
        'judul' => $title,
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
}

test('moved owner with edit permission can edit and update their old quotation', function () {
    $cv = Company::create(['code' => 'OWNER-CV', 'name' => 'Owner CV']);
    $pt = Company::create(['code' => 'OWNER-PT', 'name' => 'Owner PT']);
    $owner = crossCompanyEditUser($cv, ['edit-penawaran']);
    $offer = crossCompanyEditOffer($cv, $owner, 'Penawaran CV Sebelum Pindah');

    $owner->update(['company_id' => $pt->id]);

    $this->actingAs($owner)
        ->get(route('penawaran.edit', $offer))
        ->assertOk();

    $this->actingAs($owner)
        ->put(route('penawaran.update', $offer), [
            'judul' => 'Penawaran CV Setelah Diedit Pemilik',
            'catatan' => 'Diedit setelah pemilik pindah ke PT.',
        ])
        ->assertRedirect(route('penawaran.show', $offer));

    $this->assertDatabaseHas('penawaran', [
        'id' => $offer->id,
        'company_id' => $cv->id,
        'id_user' => $owner->id,
        'judul' => 'Penawaran CV Setelah Diedit Pemilik',
        'catatan' => 'Diedit setelah pemilik pindah ke PT.',
    ]);
});

test('moved owner without edit permission remains blocked by route middleware', function () {
    $cv = Company::create(['code' => 'NO-PERM-CV', 'name' => 'No Permission CV']);
    $pt = Company::create(['code' => 'NO-PERM-PT', 'name' => 'No Permission PT']);
    $owner = crossCompanyEditUser($cv, []);
    $offer = crossCompanyEditOffer($cv, $owner, 'Penawaran Tanpa Permission');

    $owner->update(['company_id' => $pt->id]);

    $this->actingAs($owner)
        ->get(route('penawaran.edit', $offer))
        ->assertForbidden();
});

test('company visibility does not let a non owner edit a cross company quotation', function () {
    $cv = Company::create(['code' => 'SHARED-CV', 'name' => 'Shared CV']);
    $pt = Company::create(['code' => 'SHARED-PT', 'name' => 'Shared PT']);
    $owner = User::factory()->create(['company_id' => $cv->id]);
    $viewer = crossCompanyEditUser($pt, ['edit-penawaran']);
    $offer = crossCompanyEditOffer($cv, $owner, 'Penawaran Dibagikan ke PT');
    $offer->sharedCompanies()->attach($pt->id);

    $this->actingAs($viewer)
        ->get(route('penawaran.edit', $offer))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->put(route('penawaran.update', $offer), ['judul' => 'Tidak Boleh Berubah'])
        ->assertForbidden();

    expect($offer->fresh()->judul)->toBe('Penawaran Dibagikan ke PT');
});

test('moved owner cannot destroy or request deletion of their old quotation', function () {
    $cv = Company::create(['code' => 'DELETE-CV', 'name' => 'Delete CV']);
    $pt = Company::create(['code' => 'DELETE-PT', 'name' => 'Delete PT']);
    $owner = crossCompanyEditUser($cv, ['edit-penawaran', 'delete-penawaran']);
    $offer = crossCompanyEditOffer($cv, $owner, 'Penawaran CV Tidak Boleh Dihapus dari PT');

    $owner->update(['company_id' => $pt->id]);

    $this->actingAs($owner)
        ->delete(route('penawaran.destroy', $offer))
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('penawaran.request.delete', $offer))
        ->assertForbidden();

    $this->assertDatabaseHas('penawaran', ['id' => $offer->id]);
});
