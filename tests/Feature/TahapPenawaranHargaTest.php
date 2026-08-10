<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\User;
use App\Models\UsulanPenawaran;
use App\Services\TahapPenawaranHarga;

function tahapPasangan(): array
{
    $cv = Company::create(['code' => 'CV-TAHAP', 'name' => 'CV Tahap']);
    $pt = Company::create(['code' => 'PT-TAHAP', 'name' => 'PT Tahap']);
    $pembuat = User::factory()->create(['company_id' => $cv->id]);

    $usulan = UsulanPenawaran::create([
        'company_id' => $cv->id,
        'target_company_id' => $pt->id,
        'judul' => 'Permintaan Tahap',
        'jenis_transaksi' => 'barang',
        'status' => 'draft',
        'created_by' => $pembuat->id,
    ]);

    return [$cv, $pt, $usulan, $pembuat];
}

test('ringkasan tahap mengikuti posisi berkas di alur dagang', function () {
    [$cv, $pt, $usulan, $pembuat] = tahapPasangan();

    expect(TahapPenawaranHarga::ringkas($usulan))
        ->toMatchArray(['nomor' => 1, 'total' => 4, 'label' => 'Draft, belum dikirim', 'tone' => 'current']);

    $usulan->update(['status' => 'menunggu']);
    expect(TahapPenawaranHarga::ringkas($usulan->fresh()))
        ->toMatchArray(['nomor' => 1, 'label' => 'Menunggu tanggapan penjual']);

    $usulan->update(['status' => 'disetujui', 'penawaran_status' => 'draft']);
    expect(TahapPenawaranHarga::ringkas($usulan->fresh()))
        ->toMatchArray(['nomor' => 2, 'label' => 'Draft penawaran', 'tone' => 'warning']);

    $usulan->update(['status' => 'ditanggapi', 'penawaran_status' => 'sent']);
    expect(TahapPenawaranHarga::ringkas($usulan->fresh()))
        ->toMatchArray(['nomor' => 2, 'label' => 'Menunggu keputusan pembeli', 'tone' => 'current']);

    $usulan->update(['penawaran_status' => 'revision_requested']);
    expect(TahapPenawaranHarga::ringkas($usulan->fresh()))
        ->toMatchArray(['nomor' => 2, 'label' => 'Revisi diminta', 'tone' => 'warning']);

    // Penawaran beres, PO belum diunggah: tahap bergeser ke Purchase Order.
    $usulan->update(['penawaran_status' => 'accepted']);
    expect(TahapPenawaranHarga::ringkas($usulan->fresh()))
        ->toMatchArray(['nomor' => 3, 'label' => 'Belum diunggah', 'tone' => 'pending']);

    $po = PurchaseOrder::create([
        'company_id' => $cv->id,
        'supplier_company_id' => $pt->id,
        'usulan_id' => $usulan->id,
        'judul' => $usulan->judul,
        'supplier_nama' => $pt->name,
        'tgl_po' => '2026-08-10',
        'user_id' => $pembuat->id,
        'status' => 'submitted',
    ]);
    expect(TahapPenawaranHarga::ringkas($usulan->fresh()))
        ->toMatchArray(['nomor' => 3, 'label' => 'Menunggu verifikasi penjual', 'tone' => 'current']);

    $po->update(['status' => 'approved']);
    expect(TahapPenawaranHarga::ringkas($usulan->fresh()))
        ->toMatchArray(['nomor' => 4, 'label' => 'Termin aktif', 'tone' => 'current']);
});

test('permintaan yang ditolak berhenti di tahap satu', function () {
    [, , $usulan] = tahapPasangan();

    $usulan->update(['status' => 'ditolak']);

    expect(TahapPenawaranHarga::ringkas($usulan->fresh()))
        ->toMatchArray(['nomor' => 1, 'label' => 'Ditolak penjual', 'tone' => 'danger']);
});

test('daftar penawaran harga menampilkan tahap tiap baris', function () {
    [$cv, , $usulan, $user] = tahapPasangan();
    $usulan->update(['status' => 'ditanggapi', 'penawaran_status' => 'sent']);

    $role = Role::create(['name' => 'Tahap Viewer', 'slug' => 'tahap-viewer-'.uniqid()]);
    $permission = Permission::firstOrCreate(
        ['slug' => 'view-usulan'],
        ['name' => 'view-usulan', 'group' => 'Trade Flow']
    );
    $role->permissions()->syncWithoutDetaching([$permission->id]);
    $user->roles()->attach($role->id);

    $this->actingAs($user)
        ->get(route('penawaran-harga.index'))
        ->assertOk()
        ->assertSee('Status & Tahap')
        ->assertSee('Tahap 2/4 · Menunggu keputusan pembeli');
});
