<?php

use App\Models\Company;
use App\Models\Penawaran;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\User;
use App\Models\UsulanPenawaran;

function poPdfUser(Company $company, string $label): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'PO Pdf '.$label,
        'slug' => 'po-pdf-'.strtolower($label).'-'.uniqid(),
    ]);

    foreach (['view-purchase-order', 'create-purchase-order'] as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Trade Flow']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $user->roles()->attach($role->id);

    return $user;
}

/** PO dari penawaran berdiskon 25%: 12 unit x Rp 336.000 = Rp 4.032.000 - 25% = Rp 3.024.000. */
function poPdfLengkap(User $pembeli, User $penjual): PurchaseOrder
{
    $penawaran = Penawaran::create([
        'company_id' => $penjual->company_id,
        'id_user' => $penjual->id,
        'judul' => 'Komponen tambahan',
        'discount_enabled' => true,
        'discount_type' => 'percent',
        'discount_value' => 25,
        'tax_enabled' => false,
    ]);
    $item = PenawaranItem::create([
        'penawaran_id' => $penawaran->id,
        'tipe' => 'custom',
        'urutan' => 1,
        'judul' => 'Komponen tambahan',
        'qty' => 12,
        'satuan' => 'Unit',
    ]);
    PenawaranItemDetail::create([
        'penawaran_item_id' => $item->id,
        'urutan' => 1,
        'nama' => 'MCB 4 Ampere 1 phase',
        'qty' => 1,
        'satuan' => 'Unit',
        'harga' => 336000,
    ]);

    $usulan = UsulanPenawaran::create([
        'company_id' => $pembeli->company_id,
        'target_company_id' => $penjual->company_id,
        'judul' => 'Permintaan komponen tambahan',
        'jenis_transaksi' => 'barang',
        'status' => 'ditanggapi',
        'penawaran_status' => 'accepted',
        'penawaran_id' => $penawaran->id,
        'created_by' => $pembeli->id,
    ]);

    return PurchaseOrder::create([
        'company_id' => $pembeli->company_id,
        'supplier_company_id' => $penjual->company_id,
        'usulan_id' => $usulan->id,
        'penawaran_id' => $penawaran->id,
        'nomor_po' => '011/PO-AS/IV/2026',
        'judul' => 'Komponen tambahan',
        'supplier_nama' => $penjual->company?->name ?? 'Penjual',
        'tgl_po' => '2026-05-12',
        'status' => 'approved',
        'sumber' => 'internal',
        'jenis_transaksi' => 'barang',
        'total' => 3024000,
        'catatan' => "Harga FOB Daerah Istimewa Yogyakarta (DIY).\nBarang indent estimasi 2 bulan setelah PO dan DP diterima.",
        'user_id' => $pembeli->id,
    ]);
}

test('pembeli dapat mengunduh PO sebagai PDF pesanan pembelian', function () {
    $cv = Company::create(['code' => 'CV-PDF', 'name' => 'CV Arta Solusindo']);
    $pt = Company::create(['code' => 'PT-PDF', 'name' => 'PT Arta Teknologi Comunindo']);
    $pembeli = poPdfUser($cv, 'CV');
    $penjual = poPdfUser($pt, 'PT');
    $po = poPdfLengkap($pembeli, $penjual);

    $response = $this->actingAs($pembeli)->get(route('purchase-orders.pdf', $po));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

test('penjual juga dapat mengunduh PDF PO yang diterimanya', function () {
    $cv = Company::create(['code' => 'CV-PDF2', 'name' => 'CV Pembeli']);
    $pt = Company::create(['code' => 'PT-PDF2', 'name' => 'PT Penjual']);
    $pembeli = poPdfUser($cv, 'CV2');
    $penjual = poPdfUser($pt, 'PT2');
    $po = poPdfLengkap($pembeli, $penjual);

    $this->actingAs($penjual)->get(route('purchase-orders.pdf', $po))->assertOk();
});

test('perusahaan lain tidak dapat mengunduh PDF PO', function () {
    $cv = Company::create(['code' => 'CV-PDF3', 'name' => 'CV Pembeli Tiga']);
    $pt = Company::create(['code' => 'PT-PDF3', 'name' => 'PT Penjual Tiga']);
    $lain = Company::create(['code' => 'PT-LAIN', 'name' => 'PT Luar']);
    $pembeli = poPdfUser($cv, 'CV3');
    $penjual = poPdfUser($pt, 'PT3');
    $penonton = poPdfUser($lain, 'LAIN');
    $po = poPdfLengkap($pembeli, $penjual);

    $this->actingAs($penonton)->get(route('purchase-orders.pdf', $po))->assertForbidden();
});

test('po pelanggan luar tidak dicetak sistem karena dokumennya berasal dari luar', function () {
    $cv = Company::create(['code' => 'CV-PDF4', 'name' => 'CV Penjual Empat']);
    $penjual = poPdfUser($cv, 'CV4');
    $po = PurchaseOrder::create([
        'company_id' => $cv->id,
        'supplier_company_id' => null,
        'nomor_po' => 'PO-LUAR-1',
        'judul' => 'PO pelanggan luar',
        'pembeli_nama' => 'PT Pelanggan Luar',
        'supplier_nama' => $cv->name,
        'tgl_po' => '2026-05-12',
        'status' => 'approved',
        'sumber' => 'pelanggan_luar',
        'jenis_transaksi' => 'barang',
        'total' => 1000000,
        'user_id' => $penjual->id,
    ]);

    $this->actingAs($penjual)->get(route('purchase-orders.pdf', $po))->assertNotFound();
});

test('rekap harga dokumen mengikuti diskon penawaran dan ditutup nilai PO', function () {
    $cv = Company::create(['code' => 'CV-PDF5', 'name' => 'CV Pembeli Lima']);
    $pt = Company::create(['code' => 'PT-PDF5', 'name' => 'PT Penjual Lima']);
    $pembeli = poPdfUser($cv, 'CV5');
    $penjual = poPdfUser($pt, 'PT5');
    $po = poPdfLengkap($pembeli, $penjual);

    $controller = app(App\Http\Controllers\PurchaseOrderController::class);
    $po->load(['penawaran.items.details']);
    $rows = (fn () => $this->buildPdfRows($po))->call($controller);
    $totals = (fn () => $this->buildPdfTotals($po, $rows))->call($controller);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['jumlah'])->toBe(12.0);
    expect($rows[0]['satuan'])->toBe('Unit');
    expect($rows[0]['harga_satuan'])->toBe(336000.0);
    expect($rows[0]['total'])->toBe(4032000.0);
    expect($rows[0]['rincian'])->toBe(['MCB 4 Ampere 1 phase']);
    expect($totals['lines'])->toBe([
        ['label' => 'Total', 'value' => 4032000.0],
        ['label' => 'Diskon 25%', 'value' => 1008000.0],
    ]);
    expect($totals['final_label'])->toBe('Harga Setelah Diskon');
    expect($totals['final_value'])->toBe(3024000.0);
});

test('keterangan dokumen diisi dari catatan PO, satu baris satu poin', function () {
    $cv = Company::create(['code' => 'CV-PDF7', 'name' => 'CV Pembeli Tujuh']);
    $pt = Company::create(['code' => 'PT-PDF7', 'name' => 'PT Penjual Tujuh']);
    $pembeli = poPdfUser($cv, 'CV7');
    $penjual = poPdfUser($pt, 'PT7');
    $po = poPdfLengkap($pembeli, $penjual);
    $po->penawaran->terms()->create([
        'urutan' => 1,
        'isi' => 'Syarat bawaan penawaran yang tidak dipakai.',
    ]);
    $po->update(['catatan' => "Harga FOB DIY.\n\nMasa pelaksanaan: 12 Mei 2026 s/d 19 Mei 2026."]);

    $controller = app(App\Http\Controllers\PurchaseOrderController::class);
    $po->load('penawaran.terms');
    $notes = (fn () => $this->buildPdfNotes($po))->call($controller);

    expect($notes)->toBe([
        'Harga FOB DIY.',
        'Masa pelaksanaan: 12 Mei 2026 s/d 19 Mei 2026.',
    ]);
});

test('keterangan jatuh ke syarat penawaran saat catatan PO dikosongkan', function () {
    $cv = Company::create(['code' => 'CV-PDF8', 'name' => 'CV Pembeli Delapan']);
    $pt = Company::create(['code' => 'PT-PDF8', 'name' => 'PT Penjual Delapan']);
    $pembeli = poPdfUser($cv, 'CV8');
    $penjual = poPdfUser($pt, 'PT8');
    $po = poPdfLengkap($pembeli, $penjual);
    $po->penawaran->terms()->create([
        'urutan' => 1,
        'judul' => 'Garansi',
        'isi' => 'Peralatan bergaransi 1 tahun.',
    ]);
    $po->update(['catatan' => null]);

    $controller = app(App\Http\Controllers\PurchaseOrderController::class);
    $po->load('penawaran.terms');
    $notes = (fn () => $this->buildPdfNotes($po))->call($controller);

    expect($notes)->toBe([
        'Garansi: Peralatan bergaransi 1 tahun.',
        'Harga belum termasuk pajak.',
    ]);
});

test('form PO mengisi awal catatan dengan syarat penawaran agar tinggal disunting', function () {
    $cv = Company::create(['code' => 'CV-PDF9', 'name' => 'CV Pembeli Sembilan']);
    $pt = Company::create(['code' => 'PT-PDF9', 'name' => 'PT Penjual Sembilan']);
    $pembeli = poPdfUser($cv, 'CV9');
    $penjual = poPdfUser($pt, 'PT9');
    $po = poPdfLengkap($pembeli, $penjual);
    $po->penawaran->terms()->create([
        'urutan' => 1,
        'isi' => 'Barang indent estimasi 2 bulan setelah PO dan DP diterima.',
    ]);
    $po->update(['catatan' => null, 'status' => 'draft']);

    $this->actingAs($pembeli)
        ->get(route('purchase-orders.edit', $po))
        ->assertOk()
        ->assertSee('Barang indent estimasi 2 bulan setelah PO dan DP diterima.', false)
        ->assertSee('Satu baris = satu poin', false);
});

test('po yang sudah disetujui dibuka dalam mode ubah keterangan saja', function () {
    $cv = Company::create(['code' => 'CV-PDF10', 'name' => 'CV Pembeli Sepuluh']);
    $pt = Company::create(['code' => 'PT-PDF10', 'name' => 'PT Penjual Sepuluh']);
    $pembeli = poPdfUser($cv, 'CV10');
    $penjual = poPdfUser($pt, 'PT10');
    $po = poPdfLengkap($pembeli, $penjual);
    expect($po->status)->toBe('approved');

    $this->actingAs($pembeli)
        ->get(route('purchase-orders.show', $po))
        ->assertOk()
        ->assertSee('Keterangan dokumen PDF')
        // Tombol Ubah PO tetap ada walau PO sudah disetujui.
        ->assertSee('Ubah PO');

    $this->actingAs($pembeli)
        ->get(route('purchase-orders.edit', $po))
        ->assertOk()
        ->assertSee('Ubah Keterangan Purchase Order')
        ->assertSee('Simpan Keterangan')
        // Field lain tetap tampil, tapi dimatikan.
        ->assertSee('value="3024000" disabled', false)
        ->assertSee('name="po_file" accept=".pdf,.jpg,.jpeg,.png" disabled', false);
});

test('menyimpan keterangan PO berjalan tidak mengubah nilai maupun tanggalnya', function () {
    $cv = Company::create(['code' => 'CV-PDF11', 'name' => 'CV Pembeli Sebelas']);
    $pt = Company::create(['code' => 'PT-PDF11', 'name' => 'PT Penjual Sebelas']);
    $pembeli = poPdfUser($cv, 'CV11');
    $penjual = poPdfUser($pt, 'PT11');
    $po = poPdfLengkap($pembeli, $penjual);

    $this->actingAs($pembeli)
        ->put(route('purchase-orders.update', $po), [
            'catatan' => "Harga FOB DIY.\nMasa pelaksanaan: 12 Mei 2026 s/d 19 Mei 2026.",
            // Kolom lain sengaja dikirim untuk memastikan nilainya diabaikan.
            'total' => 1,
            'judul' => 'Judul selundupan',
            'tgl_po' => '2020-01-01',
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $po->refresh();
    expect($po->catatan)->toBe("Harga FOB DIY.\nMasa pelaksanaan: 12 Mei 2026 s/d 19 Mei 2026.");
    expect((float) $po->total)->toBe(3024000.0);
    expect($po->judul)->toBe('Komponen tambahan');
    expect($po->tgl_po->format('Y-m-d'))->toBe('2026-05-12');
});

test('penjual tidak dapat menyunting keterangan PO milik pembeli', function () {
    $cv = Company::create(['code' => 'CV-PDF12', 'name' => 'CV Pembeli Dua Belas']);
    $pt = Company::create(['code' => 'PT-PDF12', 'name' => 'PT Penjual Dua Belas']);
    $pembeli = poPdfUser($cv, 'CV12');
    $penjual = poPdfUser($pt, 'PT12');
    $po = poPdfLengkap($pembeli, $penjual);

    $this->actingAs($penjual)
        ->put(route('purchase-orders.update', $po), ['catatan' => 'Diubah penjual.'])
        ->assertForbidden();

    expect($po->fresh()->catatan)->not->toBe('Diubah penjual.');
});

test('po yang dibatalkan tidak dapat diubah sama sekali', function () {
    $cv = Company::create(['code' => 'CV-PDF13', 'name' => 'CV Pembeli Tiga Belas']);
    $pt = Company::create(['code' => 'PT-PDF13', 'name' => 'PT Penjual Tiga Belas']);
    $pembeli = poPdfUser($cv, 'CV13');
    $penjual = poPdfUser($pt, 'PT13');
    $po = poPdfLengkap($pembeli, $penjual);
    $po->update(['status' => 'cancelled']);

    $this->actingAs($pembeli)->get(route('purchase-orders.edit', $po))->assertStatus(422);
    $this->actingAs($pembeli)
        ->put(route('purchase-orders.update', $po), ['catatan' => 'Coba ubah.'])
        ->assertStatus(422);
});

test('po tanpa penawaran tampil sebagai satu baris paket senilai total PO', function () {
    $cv = Company::create(['code' => 'CV-PDF6', 'name' => 'CV Pembeli Enam']);
    $pt = Company::create(['code' => 'PT-PDF6', 'name' => 'PT Penjual Enam']);
    $pembeli = poPdfUser($cv, 'CV6');
    $penjual = poPdfUser($pt, 'PT6');
    $po = PurchaseOrder::create([
        'company_id' => $cv->id,
        'supplier_company_id' => $pt->id,
        'nomor_po' => 'PO-TANPA-PENAWARAN',
        'judul' => 'Pengadaan panel',
        'supplier_nama' => $pt->name,
        'tgl_po' => '2026-05-12',
        'status' => 'approved',
        'sumber' => 'internal',
        'jenis_transaksi' => 'barang',
        'total' => 7500000,
        'user_id' => $pembeli->id,
    ]);

    $controller = app(App\Http\Controllers\PurchaseOrderController::class);
    $rows = (fn () => $this->buildPdfRows($po))->call($controller);
    $totals = (fn () => $this->buildPdfTotals($po, $rows))->call($controller);

    expect($rows)->toBe([[
        'judul' => 'Pengadaan panel',
        'rincian' => [],
        'jumlah' => 1.0,
        'satuan' => 'Paket',
        'harga_satuan' => 7500000.0,
        'total' => 7500000.0,
    ]]);
    expect($totals['final_label'])->toBe('Total Pesanan');
    expect($totals['final_value'])->toBe(7500000.0);

    $this->actingAs($pembeli)->get(route('purchase-orders.pdf', $po))->assertOk();
});
