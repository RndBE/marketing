<?php

use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UsulanPenawaran;

function usulanInternalCompany(string $code): Company
{
    return Company::firstOrCreate(
        ['code' => $code],
        ['name' => 'Usulan Internal Company '.$code]
    );
}

function usulanInternalViewer(Company $company, array $permissionSlugs = ['view-all-penawaran']): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Usulan Internal Tester '.uniqid(),
        'slug' => 'usulan-internal-tester-'.uniqid(),
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

function usulanInternalOffer(Company $company, User $owner, string $title, int $seq): Penawaran
{
    $docNumber = DocNumber::create([
        'company_id' => $company->id,
        'prefix' => 'SPH04',
        'doc_type' => 'penawaran',
        'user_code' => $company->code,
        'seq' => $seq,
        'month' => 7,
        'year' => 2026,
        'doc_no' => sprintf('%03d/SPH04/%s/VII/2026', $seq, $company->code),
    ]);

    return Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $owner->id,
        'doc_number_id' => $docNumber->id,
        'judul' => $title,
        'instansi_tujuan' => 'Instansi Test',
        'nama_pekerjaan' => 'Pekerjaan Test',
        'lokasi_pekerjaan' => 'Yogyakarta',
        'tanggal_penawaran' => '2026-07-13',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
}

function usulanInternalRequest(Company $company, User $creator, Penawaran $penawaran, ?Company $target = null): UsulanPenawaran
{
    return UsulanPenawaran::create([
        'company_id' => $company->id,
        'target_company_id' => $target?->id,
        'judul' => $penawaran->judul,
        'jenis_transaksi' => 'barang',
        'status' => 'disetujui',
        'penawaran_id' => $penawaran->id,
        'penawaran_status' => 'none',
        'created_by' => $creator->id,
    ]);
}

test('penawaran from internal usulan opens on the general penawaran page', function () {
    $company = usulanInternalCompany('INT-AS');
    $viewer = usulanInternalViewer($company);
    $offer = usulanInternalOffer($company, $viewer, 'Penawaran Usulan Internal', 190);
    usulanInternalRequest($company, $viewer, $offer);

    $this->actingAs($viewer)
        ->get(route('penawaran.show', $offer))
        ->assertOk()
        ->assertSee('Penawaran Usulan Internal');
});

test('penawaran from internal usulan stays listed in daftar penawaran', function () {
    $company = usulanInternalCompany('INT-LIST');
    $viewer = usulanInternalViewer($company);
    $offer = usulanInternalOffer($company, $viewer, 'Penawaran Internal Terdaftar', 191);
    usulanInternalRequest($company, $viewer, $offer);

    $this->actingAs($viewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSee('Penawaran Internal Terdaftar');
});

test('penawaran from permohonan harga still redirects to its own quotation page', function () {
    $company = usulanInternalCompany('PH-AS');
    $target = usulanInternalCompany('PH-ATC');
    $viewer = usulanInternalViewer($company);
    $offer = usulanInternalOffer($company, $viewer, 'Penawaran Permohonan Harga', 192);
    $usulan = usulanInternalRequest($company, $viewer, $offer, $target);

    $this->actingAs($viewer)
        ->get(route('penawaran.show', $offer))
        ->assertRedirect(route('penawaran-harga.quotation.show', $usulan));

    $this->actingAs($viewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertDontSee('Penawaran Permohonan Harga');
});

test('penawaran pdf from internal usulan is not redirected away', function () {
    $company = usulanInternalCompany('INT-PDF');
    $viewer = usulanInternalViewer($company);
    $offer = usulanInternalOffer($company, $viewer, 'Penawaran Internal PDF', 193);
    usulanInternalRequest($company, $viewer, $offer);

    $this->actingAs($viewer)
        ->get(route('penawaran.pdf', $offer))
        ->assertOk();
});
