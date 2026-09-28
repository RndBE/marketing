<?php

use App\Models\Company;
use App\Models\Penawaran;
use App\Models\PenawaranSignature;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\TandaTanganDokumen;
use Illuminate\Support\Facades\Storage;

// Kejadian 25 September 2026: TTD dihapus dari satu penawaran hasil duplikasi,
// berkasnya ikut terhapus, dan TTD di profil serta 140 penawaran lain hilang.
test('menghapus TTD satu penawaran tidak menghapus berkas yang masih dipakai record lain', function () {
    Storage::fake('public');
    Storage::disk('public')->put('signatures/bersama.png', 'ttd');

    $company = Company::create(['code' => 'TTD', 'name' => 'PT Uji TTD']);
    $role = Role::create(['name' => 'Editor TTD', 'slug' => 'editor-ttd-'.uniqid()]);
    $role->permissions()->attach(Permission::firstOrCreate(['slug' => 'edit-penawaran'], ['name' => 'Edit Penawaran', 'group' => 'Penawaran'])->id);
    $user = User::factory()->create(['company_id' => $company->id, 'ttd' => 'signatures/bersama.png']);
    $user->roles()->attach($role->id);

    [$asli, $duplikat] = collect(['Asli', 'Duplikat'])->map(function ($judul) use ($company, $user) {
        $penawaran = Penawaran::create(['company_id' => $company->id, 'id_user' => $user->id, 'judul' => $judul]);
        $signature = PenawaranSignature::create(['penawaran_id' => $penawaran->id, 'urutan' => 1, 'nama' => $user->name, 'ttd_path' => 'signatures/bersama.png']);

        return [$penawaran, $signature];
    })->all();

    foreach ([$duplikat, $asli] as [$penawaran, $signature]) {
        $this->actingAs($user)->delete(route('penawaran.signatures.ttd.delete', [$penawaran, $signature]))->assertRedirect();

        expect($signature->fresh()->ttd_path)->toBeNull();
        // Profil user masih memakainya.
        Storage::disk('public')->assertExists('signatures/bersama.png');
    }

    $user->forceFill(['ttd' => null])->save();
    TandaTanganDokumen::hapusBerkasJikaTakDipakai('signatures/bersama.png');

    Storage::disk('public')->assertMissing('signatures/bersama.png');
});
