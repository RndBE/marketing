<?php

use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

function penawaranIndexCompany(string $code): Company
{
    return Company::firstOrCreate(
        ['code' => $code],
        ['name' => 'Penawaran Company ' . $code]
    );
}

function penawaranIndexUserWithPermissions(Company $company, array $permissionSlugs): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Penawaran Index Tester ' . uniqid(),
        'slug' => 'penawaran-index-tester-' . uniqid(),
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

function penawaranIndexCreateOffer(Company $company, User $owner, string $title, int $year, int $month, int $seq): Penawaran
{
    $docNumber = DocNumber::create([
        'company_id' => $company->id,
        'prefix' => 'PNW',
        'doc_type' => 'penawaran',
        'user_code' => 'USR',
        'seq' => $seq,
        'month' => $month,
        'year' => $year,
        'doc_no' => sprintf('%03d/USR/%s/%02d/%d', $seq, $company->code, $month, $year),
    ]);

    return Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $owner->id,
        'doc_number_id' => $docNumber->id,
        'judul' => $title,
        'instansi_tujuan' => 'Instansi Test',
        'nama_pekerjaan' => 'Pekerjaan Test',
        'lokasi_pekerjaan' => 'Yogyakarta',
        'tanggal_penawaran' => "{$year}-{$month}-01",
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
}

test('user with view all penawaran can see all companies and filter by company', function () {
    $cv = penawaranIndexCompany('AS');
    $pt = penawaranIndexCompany('ATC');
    $cvOwner = User::factory()->create(['company_id' => $cv->id]);
    $ptViewer = penawaranIndexUserWithPermissions($pt, ['view-all-penawaran']);

    penawaranIndexCreateOffer($cv, $cvOwner, 'Penawaran CV Terlihat', 2026, 7, 1);
    penawaranIndexCreateOffer($pt, $ptViewer, 'Penawaran PT Terlihat', 2026, 7, 2);

    $this->actingAs($ptViewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSee('Penawaran CV Terlihat')
        ->assertSee('Penawaran PT Terlihat');

    $this->actingAs($ptViewer)
        ->get(route('penawaran.index', ['company_id' => $pt->id]))
        ->assertOk()
        ->assertDontSee('Penawaran CV Terlihat')
        ->assertSee('Penawaran PT Terlihat');
});

test('penawaran index orders newest document number first by year month and sequence', function () {
    $company = penawaranIndexCompany('ORDER-AS');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);

    penawaranIndexCreateOffer($company, $viewer, 'Penawaran Lama Seq Besar', 2025, 12, 999);
    penawaranIndexCreateOffer($company, $viewer, 'Penawaran Baru Seq Kecil', 2026, 1, 1);

    $this->actingAs($viewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'Penawaran Baru Seq Kecil',
            'Penawaran Lama Seq Besar',
        ]);
});

test('penawaran index prioritizes logged in users company before other companies', function () {
    $cv = penawaranIndexCompany('PRIO-AS');
    $pt = penawaranIndexCompany('PRIO-ATC');
    $cvOwner = User::factory()->create(['company_id' => $cv->id]);
    $ptViewer = penawaranIndexUserWithPermissions($pt, ['view-all-penawaran']);

    penawaranIndexCreateOffer($cv, $cvOwner, 'Penawaran AS Nomor Lebih Baru', 2026, 7, 99);
    penawaranIndexCreateOffer($pt, $ptViewer, 'Penawaran PT Nomor Lebih Lama', 2026, 7, 1);

    $this->actingAs($ptViewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'Penawaran PT Nomor Lebih Lama',
            'Penawaran AS Nomor Lebih Baru',
        ]);

    $this->actingAs($ptViewer)
        ->get(route('penawaran.index', ['company_id' => $cv->id]))
        ->assertOk()
        ->assertDontSee('Penawaran PT Nomor Lebih Lama')
        ->assertSee('Penawaran AS Nomor Lebih Baru');
});

test('penawaran detail lets logout form submit normally instead of ajax', function () {
    $company = penawaranIndexCompany('LOGOUT-AS');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);
    $penawaran = penawaranIndexCreateOffer($company, $viewer, 'Penawaran Logout Normal', 2026, 7, 1);

    $this->actingAs($viewer)
        ->get(route('penawaran.show', $penawaran))
        ->assertOk()
        ->assertSee("formAction.includes('/logout')", false);
});
