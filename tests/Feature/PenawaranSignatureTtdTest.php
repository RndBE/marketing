<?php

use App\Models\Company;
use App\Models\Penawaran;
use App\Models\PenawaranSignature;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function penawaranSignatureTtdEditor(Company $company): User
{
    $role = Role::create([
        'name' => 'Penawaran TTD Editor',
        'slug' => 'penawaran-ttd-editor-' . uniqid(),
    ]);
    $permission = Permission::firstOrCreate(
        ['slug' => 'edit-penawaran'],
        ['name' => 'Edit Penawaran', 'group' => 'Penawaran']
    );
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    $user = User::factory()->create(['company_id' => $company->id]);
    $user->roles()->attach($role->id);

    return $user;
}

test('penawaran signature ttd image can be deleted without deleting signature data', function () {
    Storage::fake('public');

    $company = Company::create([
        'code' => 'SIG',
        'name' => 'Signature Test Company',
    ]);
    $user = penawaranSignatureTtdEditor($company);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'judul' => 'Penawaran Signature Test',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    $signature = PenawaranSignature::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'nama' => 'Afif Faishahuda',
        'jabatan' => 'Corporate Account Manager',
        'kota' => 'Sleman',
        'tanggal' => '2026-07-22',
        'ttd_path' => 'penawaran/ttd/current-signature.png',
    ]);

    Storage::disk('public')->put($signature->ttd_path, 'fake signature image');

    $response = $this->actingAs($user)
        ->delete(route('penawaran.signatures.ttd.delete', [$penawaran, $signature]));

    $response->assertRedirect(route('penawaran.show', $penawaran));
    Storage::disk('public')->assertMissing('penawaran/ttd/current-signature.png');

    $signature->refresh();
    expect($signature->exists)->toBeTrue()
        ->and($signature->ttd_path)->toBeNull()
        ->and($signature->nama)->toBe('Afif Faishahuda')
        ->and($signature->jabatan)->toBe('Corporate Account Manager');
});
