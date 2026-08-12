<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\User;
use App\Models\UsulanPenawaran;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function poEditUser(Company $company, string $label): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'PO Edit '.$label,
        'slug' => 'po-edit-'.strtolower($label).'-'.uniqid(),
    ]);

    foreach (['view-usulan', 'create-usulan', 'view-purchase-order', 'create-purchase-order'] as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Trade Flow']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $user->roles()->attach($role->id);

    return $user;
}

/** PO antar perusahaan yang sudah terkirim ke penjual. */
function poEditTerkirim(User $pembeli, User $penjual, string $reference, string $status = 'submitted'): PurchaseOrder
{
    $usulan = UsulanPenawaran::create([
        'company_id' => $pembeli->company_id,
        'target_company_id' => $penjual->company_id,
        'judul' => 'Permintaan '.$reference,
        'jenis_transaksi' => 'barang',
        'status' => 'ditanggapi',
        'penawaran_status' => 'accepted',
        'created_by' => $pembeli->id,
    ]);

    return PurchaseOrder::create([
        'company_id' => $pembeli->company_id,
        'supplier_company_id' => $penjual->company_id,
        'usulan_id' => $usulan->id,
        'nomor_po' => 'PO-'.$reference,
        'judul' => 'PO '.$reference,
        'supplier_nama' => $penjual->company?->name ?? 'Penjual',
        'tgl_po' => '2026-08-01',
        'status' => $status,
        'sumber' => 'internal',
        'jenis_transaksi' => 'barang',
        'total' => 5000000,
        'user_id' => $pembeli->id,
    ]);
}

test('po dari penawaran harga dapat dibuat tanpa dokumen dan berkasnya menyusul lewat edit', function () {
    Storage::fake('local');
    $cv = Company::create(['code' => 'CV-NOF', 'name' => 'CV Tanpa Berkas']);
    $pt = Company::create(['code' => 'PT-NOF', 'name' => 'PT Tanpa Berkas']);
    $pembeli = poEditUser($cv, 'CV');
    $penjual = poEditUser($pt, 'PT');
    $usulan = UsulanPenawaran::create([
        'company_id' => $cv->id,
        'target_company_id' => $pt->id,
        'judul' => 'Permintaan NOF',
        'jenis_transaksi' => 'barang',
        'status' => 'ditanggapi',
        'penawaran_status' => 'accepted',
        'created_by' => $pembeli->id,
    ]);

    $this->actingAs($pembeli)
        ->post(route('purchase-orders.store'), [
            'usulan_id' => $usulan->id,
            'judul' => 'PO Tanpa Berkas',
            'supplier_nama' => $pt->name,
            'tgl_po' => '2026-08-10',
            'status' => 'submitted',
            'jenis_transaksi' => 'barang',
            'total' => 5000000,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $po = PurchaseOrder::where('usulan_id', $usulan->id)->firstOrFail();
    expect($po->po_file_path)->toBeNull()
        ->and($po->status)->toBe('submitted');

    $this->actingAs($pembeli)
        ->put(route('purchase-orders.update', $po), [
            'judul' => 'PO Tanpa Berkas',
            'tgl_po' => '2026-08-10',
            'total' => 5000000,
            'po_file' => UploadedFile::fake()->create('po-menyusul.pdf', 30, 'application/pdf'),
        ])
        ->assertRedirect();

    expect($po->refresh()->po_file_path)->not->toBeNull();
    Storage::disk('local')->assertExists($po->po_file_path);
});

test('pembeli dapat mengubah po yang belum disetujui', function () {
    Storage::fake('local');
    $cv = Company::create(['code' => 'CV-EDT', 'name' => 'CV Edit']);
    $pt = Company::create(['code' => 'PT-EDT', 'name' => 'PT Edit']);
    $pembeli = poEditUser($cv, 'CV');
    $penjual = poEditUser($pt, 'PT');
    $po = poEditTerkirim($pembeli, $penjual, 'EDT');

    $this->actingAs($pembeli)->get(route('purchase-orders.edit', $po))->assertOk();

    $this->actingAs($pembeli)
        ->put(route('purchase-orders.update', $po), [
            'nomor_po' => 'PO-EDT-REV',
            'judul' => 'PO Perangkat Revisi',
            'tgl_po' => '2026-08-05',
            'total' => 7500000,
            'catatan' => 'Nilai dinaikkan setelah adendum.',
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $po->refresh();
    expect($po->judul)->toBe('PO Perangkat Revisi')
        ->and($po->nomor_po)->toBe('PO-EDT-REV')
        ->and((float) $po->total)->toBe(7500000.0)
        // Status PO yang masih menunggu verifikasi tidak ikut bergeser.
        ->and($po->status)->toBe('submitted');
});

test('jenis transaksi dan pemasok po turunan tetap terkunci saat disunting', function () {
    Storage::fake('local');
    $cv = Company::create(['code' => 'CV-LCK', 'name' => 'CV Lock']);
    $pt = Company::create(['code' => 'PT-LCK', 'name' => 'PT Lock']);
    $pembeli = poEditUser($cv, 'CV');
    $penjual = poEditUser($pt, 'PT');
    $po = poEditTerkirim($pembeli, $penjual, 'LCK');

    $this->actingAs($pembeli)
        ->put(route('purchase-orders.update', $po), [
            'judul' => 'PO Lock',
            'tgl_po' => '2026-08-05',
            'total' => 5000000,
            'jenis_transaksi' => 'jasa',
            'supplier_nama' => 'PT Pemasok Lain',
            'status' => 'approved',
        ])
        ->assertRedirect();

    $po->refresh();
    expect($po->jenis_transaksi)->toBe('barang')
        ->and($po->supplier_nama)->toBe('PT Lock')
        ->and($po->status)->toBe('submitted');
});

test('po yang ditolak kembali menunggu verifikasi dan mengabari penjual setelah diperbaiki', function () {
    Storage::fake('local');
    $cv = Company::create(['code' => 'CV-RJK', 'name' => 'CV Tolak']);
    $pt = Company::create(['code' => 'PT-RJK', 'name' => 'PT Tolak']);
    $pembeli = poEditUser($cv, 'CV');
    $penjual = poEditUser($pt, 'PT');
    $po = poEditTerkirim($pembeli, $penjual, 'RJK', 'rejected');
    $po->forceFill([
        'verification_notes' => 'Nilai tidak sesuai penawaran.',
        'verified_by' => $penjual->id,
        'verified_at' => now(),
    ])->save();

    $this->actingAs($pembeli)
        ->put(route('purchase-orders.update', $po), [
            'judul' => 'PO Tolak Revisi',
            'tgl_po' => '2026-08-05',
            'total' => 4000000,
            'po_file' => UploadedFile::fake()->create('po-revisi.pdf', 30, 'application/pdf'),
        ])
        ->assertRedirect();

    $po->refresh();
    expect($po->status)->toBe('submitted')
        ->and($po->verification_notes)->toBeNull()
        ->and($po->verified_by)->toBeNull()
        ->and($po->verified_at)->toBeNull()
        ->and($po->po_file_path)->not->toBeNull();

    $jenis = $penjual->notifications()->get()->map(fn ($item) => data_get($item->data, 'jenis'))->all();
    expect($jenis)->toContain('po_diperbarui');
});

test('po yang sudah disetujui tidak dapat disunting', function () {
    $cv = Company::create(['code' => 'CV-APR', 'name' => 'CV Setuju']);
    $pt = Company::create(['code' => 'PT-APR', 'name' => 'PT Setuju']);
    $pembeli = poEditUser($cv, 'CV');
    $penjual = poEditUser($pt, 'PT');
    $po = poEditTerkirim($pembeli, $penjual, 'APR', 'approved');

    $this->actingAs($pembeli)->get(route('purchase-orders.edit', $po))->assertStatus(422);
    $this->actingAs($pembeli)
        ->put(route('purchase-orders.update', $po), [
            'judul' => 'PO Setuju Revisi',
            'tgl_po' => '2026-08-05',
            'total' => 9000000,
        ])
        ->assertStatus(422);
});

test('penjual tidak dapat menyunting po masuk milik pembeli', function () {
    $cv = Company::create(['code' => 'CV-AKS', 'name' => 'CV Akses']);
    $pt = Company::create(['code' => 'PT-AKS', 'name' => 'PT Akses']);
    $pembeli = poEditUser($cv, 'CV');
    $penjual = poEditUser($pt, 'PT');
    $po = poEditTerkirim($pembeli, $penjual, 'AKS');

    $this->actingAs($penjual)->get(route('purchase-orders.edit', $po))->assertForbidden();
});

test('total po tidak boleh lebih kecil dari termin yang sudah dijadwalkan', function () {
    $cv = Company::create(['code' => 'CV-TRM-E', 'name' => 'CV Termin Edit']);
    $pembeli = poEditUser($cv, 'CV');
    $po = PurchaseOrder::create([
        'company_id' => $cv->id,
        'nomor_po' => 'PO-TRM-E',
        'judul' => 'PO Pemasok Luar',
        'supplier_nama' => 'Toko Luar',
        'tgl_po' => '2026-08-01',
        'status' => 'draft',
        'sumber' => 'internal',
        'jenis_transaksi' => 'barang',
        'total' => 5000000,
        'user_id' => $pembeli->id,
    ]);
    $po->terms()->create([
        'pembayaran_ke' => 1,
        'tanggal_jatuh_tempo' => '2026-09-01',
        'nilai_tagihan' => 4000000,
        'status' => 'draft',
    ]);

    $this->actingAs($pembeli)
        ->put(route('purchase-orders.update', $po), [
            'judul' => 'PO Pemasok Luar',
            'tgl_po' => '2026-08-01',
            'total' => 3000000,
            'jenis_transaksi' => 'barang',
            'supplier_nama' => 'Toko Luar',
            'status' => 'draft',
        ])
        ->assertSessionHasErrors('total');

    expect((float) $po->refresh()->total)->toBe(5000000.0);
});
