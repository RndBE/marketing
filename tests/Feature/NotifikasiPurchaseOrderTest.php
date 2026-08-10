<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\User;
use App\Models\UsulanPenawaran;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function poUser(Company $company, string $label): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'PO Notif '.$label,
        'slug' => 'po-notif-'.strtolower($label).'-'.uniqid(),
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

/** Permintaan yang penawarannya sudah disetujui, siap dibuatkan PO. */
function usulanSiapPo(User $pembeli, User $penjual, string $reference): UsulanPenawaran
{
    return UsulanPenawaran::create([
        'company_id' => $pembeli->company_id,
        'target_company_id' => $penjual->company_id,
        'judul' => 'Permintaan '.$reference,
        'jenis_transaksi' => 'barang',
        'status' => 'ditanggapi',
        'penawaran_status' => 'accepted',
        'created_by' => $pembeli->id,
    ]);
}

/** @return array<int, string|null> */
function jenisNotifikasiPo(User $user): array
{
    return $user->notifications()
        ->get()
        ->map(fn ($item) => data_get($item->data, 'jenis'))
        ->all();
}

test('purchase order yang diunggah pembeli mengabari perusahaan penjual', function () {
    Storage::fake('local');
    $cv = Company::create(['code' => 'CV-PO', 'name' => 'CV PO']);
    $pt = Company::create(['code' => 'PT-PO', 'name' => 'PT PO']);
    $pembeli = poUser($cv, 'CV');
    $penjual = poUser($pt, 'PT');
    $usulan = usulanSiapPo($pembeli, $penjual, 'PO');

    $this->actingAs($pembeli)
        ->post(route('purchase-orders.store'), [
            'usulan_id' => $usulan->id,
            'judul' => 'PO Perangkat',
            'supplier_nama' => $pt->name,
            'tgl_po' => '2026-08-10',
            'status' => 'submitted',
            'jenis_transaksi' => 'barang',
            'total' => 5000000,
            'po_file' => UploadedFile::fake()->create('po.pdf', 30, 'application/pdf'),
        ])
        ->assertRedirect();

    expect(jenisNotifikasiPo($penjual))->toContain('po_dikirim')
        // Pengunggahnya sendiri tidak perlu dikabari.
        ->and(jenisNotifikasiPo($pembeli))->toBeEmpty();

    expect(data_get($penjual->notifications()->first()->data, 'pesan'))->toContain('CV PO');
});

test('verifikasi po dan seluruh tahap termin mengabari perusahaan lawan', function () {
    Storage::fake('local');
    $cv = Company::create(['code' => 'CV-TRM', 'name' => 'CV Termin']);
    $pt = Company::create(['code' => 'PT-TRM', 'name' => 'PT Termin']);
    $pembeli = poUser($cv, 'CV');
    $penjual = poUser($pt, 'PT');
    $usulan = usulanSiapPo($pembeli, $penjual, 'TERMIN');

    $this->actingAs($pembeli)->post(route('purchase-orders.store'), [
        'usulan_id' => $usulan->id,
        'judul' => 'PO Termin',
        'supplier_nama' => $pt->name,
        'tgl_po' => '2026-08-10',
        'status' => 'submitted',
        'jenis_transaksi' => 'barang',
        'total' => 2000000,
        'po_file' => UploadedFile::fake()->create('po.pdf', 30, 'application/pdf'),
    ])->assertRedirect();

    $po = PurchaseOrder::query()->where('judul', 'PO Termin')->firstOrFail();

    // PO disetujui penjual -> pembeli dikabari, jadwal termin terbentuk.
    $this->actingAs($penjual)->post(route('purchase-orders.verify', $po), [
        'decision' => 'approved',
        'default_term_count' => 2,
        'first_due_date' => '2026-09-10',
    ])->assertRedirect();

    expect(jenisNotifikasiPo($pembeli))->toContain('po_diverifikasi');

    $term = $po->terms()->orderBy('pembayaran_ke')->firstOrFail();

    // Invoice diterbitkan penjual -> pembeli dikabari.
    $this->actingAs($penjual)->put(route('purchase-orders.terms.billing.update', [$po, $term]), [
        'tanggal_jatuh_tempo' => '2026-09-10',
        'nilai_tagihan' => $term->nilai_tagihan,
        'nomor_invoice' => 'INV-001',
        'tanggal_invoice' => '2026-08-15',
    ])->assertRedirect();

    expect(jenisNotifikasiPo($pembeli))->toContain('invoice_diterbitkan');
    $jumlahSetelahInvoice = count(jenisNotifikasiPo($pembeli));

    // Menyunting invoice yang sama tidak menambah notifikasi baru.
    $this->actingAs($penjual)->put(route('purchase-orders.terms.billing.update', [$po, $term]), [
        'tanggal_jatuh_tempo' => '2026-09-20',
        'nilai_tagihan' => $term->nilai_tagihan,
        'nomor_invoice' => 'INV-001-REV',
        'tanggal_invoice' => '2026-08-16',
    ])->assertRedirect();

    expect(jenisNotifikasiPo($pembeli))->toHaveCount($jumlahSetelahInvoice);

    // Pelunasan dicatat penjual -> pembeli dikabari.
    $this->actingAs($penjual)->put(route('purchase-orders.terms.payment.update', [$po, $term]), [
        'tanggal_bayar' => '2026-09-18',
        'nilai_dibayar' => $term->nilai_tagihan,
        'bukti_bayar_file' => UploadedFile::fake()->create('bukti.pdf', 20, 'application/pdf'),
    ])->assertRedirect();

    expect($term->refresh()->status)->toBe('paid')
        ->and(jenisNotifikasiPo($pembeli))->toContain('pembayaran_dicatat');

    // Seluruh kabar tahap 3 dan 4 mengarah ke pembeli, bukan ke penjual yang mencatat.
    expect(jenisNotifikasiPo($penjual))->toBe(['po_dikirim']);
});

test('po pelanggan luar tidak mengabari siapa pun', function () {
    Storage::fake('local');
    $pt = Company::create(['code' => 'PT-LUAR', 'name' => 'PT Luar']);
    $penjual = poUser($pt, 'PT');
    $rekan = poUser($pt, 'PT2');

    $this->actingAs($penjual)
        ->post(route('purchase-orders.store'), [
            'sumber' => 'pelanggan_luar',
            'pembeli_nama' => 'PT Pelanggan Luar',
            'total' => 1500000,
            'po_file' => UploadedFile::fake()->create('po-luar.pdf', 30, 'application/pdf'),
        ])
        ->assertRedirect();

    // Lawan transaksinya tidak ada di dalam sistem, jadi tidak ada yang dikabari.
    expect(jenisNotifikasiPo($penjual))->toBeEmpty()
        ->and(jenisNotifikasiPo($rekan))->toBeEmpty();
});
