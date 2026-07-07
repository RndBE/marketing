<?php

use App\Models\AlurPenawaran;
use App\Models\Company;
use App\Models\DocNumber;
use App\Models\LangkahAlurPenawaran;
use App\Models\Penawaran;
use App\Models\PenawaranCover;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

function duplicateAdoptCompany(string $code, array $attrs = []): Company
{
    return Company::firstOrCreate(
        ['code' => $code],
        array_merge(['name' => 'Company ' . $code], $attrs)
    );
}

function duplicateAdoptUser(Company $company, array $permissionSlugs = []): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Dup Adopt Tester ' . uniqid(),
        'slug' => 'dup-adopt-tester-' . uniqid(),
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

function duplicateAdoptActiveAlur(Company $company, User $approver): void
{
    $alur = AlurPenawaran::create([
        'company_id' => $company->id,
        'nama' => 'Alur ' . $company->code,
        'berlaku_untuk' => 'penawaran',
        'status' => 'aktif',
        'dibuat_oleh' => $approver->id,
    ]);

    LangkahAlurPenawaran::create([
        'alur_penawaran_id' => $alur->id,
        'no_langkah' => 1,
        'nama_langkah' => 'Approve',
        'user_id' => $approver->id,
        'harus_semua' => 0,
    ]);
}

test('CV user duplicating a shared PT penawaran adopts it into CV (nomor & kop)', function () {
    $pt = duplicateAdoptCompany('DUP-PT', ['name' => 'PT Sumber Asli']);
    $cv = duplicateAdoptCompany('DUP-CV', [
        'name' => 'CV Penyalin Mandiri',
        'address' => 'Jl. CV No. 1',
        'email' => 'cv@example.com',
        'phone' => '0812345678',
        'logo_path' => 'logos/cv.png',
    ]);

    $ptOwner = User::factory()->create(['company_id' => $pt->id]);
    $cvUser = duplicateAdoptUser($cv, ['create-penawaran']);

    // Alur aktif milik CV supaya duplikasi bisa membangun approval baru.
    duplicateAdoptActiveAlur($cv, $cvUser);

    // Penawaran milik PT, dibagikan (visible) ke CV.
    $ptDoc = DocNumber::create([
        'company_id' => $pt->id,
        'prefix' => 'SPH01',
        'seq' => 7,
        'month' => 7,
        'year' => 2026,
        'doc_no' => '007/SPH01/DUP-PT/VII/2026',
    ]);

    $source = Penawaran::create([
        'company_id' => $pt->id,
        'id_user' => $ptOwner->id,
        'doc_number_id' => $ptDoc->id,
        'judul' => 'Penawaran Asli PT',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    $source->sharedCompanies()->attach($cv->id);

    PenawaranCover::create([
        'penawaran_id' => $source->id,
        'judul_cover' => 'Proposal Jaringan',
        'subjudul' => 'Subjudul Asli',
        'perusahaan_nama' => 'PT Sumber Asli',
        'perusahaan_alamat' => 'Alamat PT',
        'perusahaan_email' => 'pt@example.com',
        'perusahaan_telp' => '0800000000',
        'logo_path' => 'logos/pt.png',
    ]);

    $this->actingAs($cvUser)
        ->post(route('penawaran.duplicate', $source))
        ->assertRedirect(route('penawaran.index'));

    $copy = Penawaran::where('id', '!=', $source->id)->latest('id')->first();

    // Diadopsi oleh CV: kepemilikan pindah ke CV & user penyalin.
    expect((int) $copy->company_id)->toBe((int) $cv->id)
        ->and((int) $copy->id_user)->toBe((int) $cvUser->id);

    // Nomor baru ikut urutan CV (seq mulai dari 1, kode perusahaan CV).
    $copyDoc = DocNumber::find($copy->doc_number_id);
    expect((int) $copyDoc->company_id)->toBe((int) $cv->id)
        ->and($copyDoc->seq)->toBe(1)
        ->and($copyDoc->doc_no)->toContain('DUP-CV');

    // Kop/cover memakai identitas CV, tetapi isi cover dipertahankan.
    $copyCover = PenawaranCover::where('penawaran_id', $copy->id)->first();
    expect($copyCover->perusahaan_nama)->toBe('CV Penyalin Mandiri')
        ->and($copyCover->logo_path)->toBe('logos/cv.png')
        ->and($copyCover->judul_cover)->toBe('Proposal Jaringan');

    // Sumber PT tidak berubah.
    expect((int) $source->fresh()->company_id)->toBe((int) $pt->id);
});

test('same-company duplicate keeps the original company and copies cover as-is', function () {
    $cv = duplicateAdoptCompany('DUP-SAME', ['name' => 'CV Sama', 'logo_path' => 'logos/same.png']);
    $user = duplicateAdoptUser($cv, ['create-penawaran']);
    duplicateAdoptActiveAlur($cv, $user);

    $doc = DocNumber::create([
        'company_id' => $cv->id,
        'prefix' => 'SPH01',
        'seq' => 3,
        'month' => 7,
        'year' => 2026,
        'doc_no' => '003/SPH01/DUP-SAME/VII/2026',
    ]);

    $source = Penawaran::create([
        'company_id' => $cv->id,
        'id_user' => $user->id,
        'doc_number_id' => $doc->id,
        'judul' => 'Penawaran Sendiri',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);

    PenawaranCover::create([
        'penawaran_id' => $source->id,
        'judul_cover' => 'Cover Asli',
        'perusahaan_nama' => 'CV Sama',
        'logo_path' => 'logos/same.png',
    ]);

    $this->actingAs($user)
        ->post(route('penawaran.duplicate', $source))
        ->assertRedirect(route('penawaran.index'));

    $copy = Penawaran::where('id', '!=', $source->id)->latest('id')->first();

    expect((int) $copy->company_id)->toBe((int) $cv->id);
    $copyCover = PenawaranCover::where('penawaran_id', $copy->id)->first();
    expect($copyCover->perusahaan_nama)->toBe('CV Sama')
        ->and($copyCover->judul_cover)->toBe('Cover Asli');
});
