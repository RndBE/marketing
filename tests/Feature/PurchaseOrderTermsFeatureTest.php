<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function purchaseOrderUser(array $permissionSlugs): User
{
    $company = Company::firstOrCreate(
        ['code' => 'PO-TERM-CO'],
        ['name' => 'PO Term Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'PO Term Tester '.uniqid(),
        'slug' => 'po-term-tester-'.uniqid(),
    ]);

    foreach ($permissionSlugs as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Purchase Order']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $user->roles()->attach($role->id);

    return $user;
}

test('purchase order stores transaction type and uploaded document', function () {
    Storage::fake('local');
    $user = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);

    $this->actingAs($user)
        ->post(route('purchase-orders.store'), [
            'judul' => 'Pengadaan dan Instalasi Sensor',
            'supplier_nama' => 'PT Vendor Teknologi',
            'tgl_po' => '2026-08-05',
            'status' => 'approved',
            'jenis_transaksi' => 'campuran',
            'total' => 100000000,
            'po_file' => UploadedFile::fake()->create('po-customer.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $po = PurchaseOrder::firstOrFail();

    expect($po->jenis_transaksi)->toBe('campuran');
    expect($po->po_file_path)->not()->toBeNull();
    Storage::disk('local')->assertExists($po->po_file_path);

    $this->actingAs($user)
        ->get(route('purchase-orders.document.download', $po))
        ->assertDownload();
});

test('user can add flexible terms but their total cannot exceed the purchase order', function () {
    $user = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);
    $po = PurchaseOrder::create([
        'company_id' => $user->company_id,
        'nomor_po' => 'PO-TERM-001',
        'judul' => 'Pekerjaan Jasa',
        'supplier_nama' => 'PT Jasa',
        'tgl_po' => '2026-08-05',
        'status' => 'approved',
        'jenis_transaksi' => 'jasa',
        'total' => 100000000,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('purchase-orders.terms.store', $po), [
            'tanggal_jatuh_tempo' => '2026-08-10',
            'nilai_tagihan' => 60000000,
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $this->actingAs($user)
        ->post(route('purchase-orders.terms.store', $po), [
            'tanggal_jatuh_tempo' => '2026-09-10',
            'nilai_tagihan' => 40000000,
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $this->actingAs($user)
        ->from(route('purchase-orders.show', $po))
        ->post(route('purchase-orders.terms.store', $po), [
            'tanggal_jatuh_tempo' => '2026-10-10',
            'nilai_tagihan' => 1,
        ])
        ->assertRedirect(route('purchase-orders.show', $po))
        ->assertSessionHasErrors('nilai_tagihan');

    expect($po->terms()->count())->toBe(2);
    expect((float) $po->terms()->sum('nilai_tagihan'))->toBe(100000000.0);
    expect($po->terms()->pluck('pembayaran_ke')->all())->toBe([1, 2]);
});

test('invoice tax and payment documents can complete a term', function () {
    Storage::fake('local');
    $user = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);
    $po = PurchaseOrder::create([
        'company_id' => $user->company_id,
        'nomor_po' => 'PO-TERM-002',
        'judul' => 'Konsultasi Sistem',
        'supplier_nama' => 'PT Konsultan',
        'tgl_po' => '2026-08-05',
        'status' => 'approved',
        'jenis_transaksi' => 'jasa',
        'total' => 50000000,
        'user_id' => $user->id,
    ]);
    $term = $po->terms()->create([
        'pembayaran_ke' => 1,
        'tanggal_jatuh_tempo' => '2026-08-20',
        'nilai_tagihan' => 50000000,
    ]);

    $this->actingAs($user)
        ->put(route('purchase-orders.terms.update', [$po, $term]), [
            'tanggal_jatuh_tempo' => '2026-08-20',
            'nilai_tagihan' => 50000000,
            'nomor_invoice' => 'INV/2026/001',
            'tanggal_invoice' => '2026-08-06',
            'invoice_file' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
            'nomor_faktur' => 'FP-001',
            'faktur_file' => UploadedFile::fake()->create('faktur.pdf', 100, 'application/pdf'),
            'tanggal_bayar' => '2026-08-10',
            'nilai_dibayar' => 49000000,
            'bukti_bayar_file' => UploadedFile::fake()->image('bukti-bayar.jpg'),
            'jenis_pph' => 'pph_23',
            'nilai_pph' => 1000000,
            'bukti_potong_pph_file' => UploadedFile::fake()->create('bupot.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $term->refresh();

    expect($term->status)->toBe('paid');
    expect($term->nilai_pelunasan)->toBe(50000000.0);
    expect($term->sisa_tagihan)->toBe(0.0);

    Storage::disk('local')->assertExists($term->invoice_path);
    Storage::disk('local')->assertExists($term->faktur_path);
    Storage::disk('local')->assertExists($term->bukti_bayar_path);
    Storage::disk('local')->assertExists($term->bukti_potong_pph_path);

    $this->actingAs($user)
        ->get(route('purchase-orders.show', $po))
        ->assertOk()
        ->assertSee('Jadwal Termin Pembayaran')
        ->assertSee('INV/2026/001');

    $this->actingAs($user)
        ->get(route('purchase-orders.terms.documents.download', [$po, $term, 'invoice']))
        ->assertDownload();
});

test('any term without documents or payment can be deleted and the rest are renumbered', function () {
    $user = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);
    $po = PurchaseOrder::create([
        'company_id' => $user->company_id,
        'nomor_po' => 'PO-TERM-DEL',
        'judul' => 'Pekerjaan Jasa',
        'supplier_nama' => 'PT Jasa',
        'tgl_po' => '2026-08-05',
        'status' => 'approved',
        'jenis_transaksi' => 'jasa',
        'total' => 50000000,
        'user_id' => $user->id,
    ]);

    foreach (range(1, 5) as $number) {
        $po->terms()->create([
            'pembayaran_ke' => $number,
            'tanggal_jatuh_tempo' => '2026-0'.(7 + $number > 9 ? 9 : 7 + $number).'-10',
            'nilai_tagihan' => 10000000,
            'status' => 'draft',
        ]);
    }

    // Termin di tengah jadwal, bukan yang terakhir -- dulu aturannya melarang ini.
    $middle = $po->terms()->where('pembayaran_ke', 2)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('purchase-orders.terms.destroy', [$po, $middle]))
        ->assertRedirect(route('purchase-orders.show', $po))
        ->assertSessionHasNoErrors();

    // Nomor dirapatkan, tidak menyisakan lubang di urutan.
    expect($po->terms()->orderBy('pembayaran_ke')->pluck('pembayaran_ke')->all())->toBe([1, 2, 3, 4]);

    // Pembagi label ikut menyusut: "Pembayaran ke-1 dari 4".
    expect($po->fresh()->jumlahTerminLabel())->toBe(4);

    $this->actingAs($user)
        ->get(route('purchase-orders.show', $po))
        ->assertOk()
        ->assertSee('Pembayaran ke-1 dari 4')
        ->assertDontSee('dari 5');

    // ...dan ikut bertambah lagi saat jadwalnya diperpanjang.
    $po->terms()->create([
        'pembayaran_ke' => 5,
        'tanggal_jatuh_tempo' => '2026-12-10',
        'nilai_tagihan' => 10000000,
        'status' => 'draft',
    ]);

    expect($po->fresh()->jumlahTerminLabel())->toBe(5);
});

test('a term that already has a payment cannot be deleted', function () {
    $user = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);
    $po = PurchaseOrder::create([
        'company_id' => $user->company_id,
        'nomor_po' => 'PO-TERM-KEEP',
        'judul' => 'Pekerjaan Jasa',
        'supplier_nama' => 'PT Jasa',
        'tgl_po' => '2026-08-05',
        'status' => 'approved',
        'jenis_transaksi' => 'jasa',
        'total' => 20000000,
        'user_id' => $user->id,
    ]);
    $paid = $po->terms()->create([
        'pembayaran_ke' => 1,
        'tanggal_jatuh_tempo' => '2026-09-10',
        'nilai_tagihan' => 20000000,
        'nilai_dibayar' => 20000000,
        'status' => 'paid',
    ]);

    $this->actingAs($user)
        ->from(route('purchase-orders.show', $po))
        ->delete(route('purchase-orders.terms.destroy', [$po, $paid]))
        ->assertSessionHasErrors('termin');

    expect($po->terms()->count())->toBe(1);
});

test('approving a purchase order builds the agreed number of terms', function () {
    $buyerCompany = Company::firstOrCreate(['code' => 'PO-BUYER'], ['name' => 'PT Pembeli']);
    $seller = purchaseOrderUser(['view-purchase-order', 'create-purchase-order', 'verify-purchase-order']);
    $po = PurchaseOrder::create([
        'company_id' => $buyerCompany->id,
        'supplier_company_id' => $seller->company_id,
        'nomor_po' => 'PO-TERM-PLAN',
        'judul' => 'Pengadaan Barang',
        'supplier_nama' => 'PT Penjual',
        'tgl_po' => '2026-08-05',
        'status' => 'submitted',
        'jenis_transaksi' => 'barang',
        'total' => 30000000,
        'user_id' => $seller->id,
    ]);

    $this->actingAs($seller)
        ->post(route('purchase-orders.verify', $po), [
            'decision' => 'approved',
            'default_term_count' => 3,
            'first_due_date' => '2026-09-01',
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $po->refresh();

    expect($po->terms()->count())->toBe(3)
        ->and($po->jumlahTerminLabel())->toBe(3)
        ->and((float) $po->terms()->sum('nilai_tagihan'))->toBe(30000000.0);
});

test('term amount inputs step in whole rupiah and never render cents', function () {
    $buyerCompany = Company::firstOrCreate(['code' => 'PO-CENTS'], ['name' => 'PT Pembeli']);
    $seller = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);
    $po = PurchaseOrder::create([
        'company_id' => $buyerCompany->id,
        'supplier_company_id' => $seller->company_id,
        'nomor_po' => 'PO-CENTS-1',
        'judul' => 'Pengadaan Barang',
        'supplier_nama' => 'PT Penjual',
        'tgl_po' => '2026-08-05',
        'status' => 'approved',
        'jenis_transaksi' => 'barang',
        'total' => 100000000,
        'user_id' => $seller->id,
    ]);
    // Pembagian 100jt ke 3 termin menyisakan sen pada nilai tagihan.
    $po->terms()->create([
        'pembayaran_ke' => 1,
        'tanggal_jatuh_tempo' => '2026-09-01',
        'nilai_tagihan' => 33333333.33,
        'status' => 'draft',
    ]);

    $html = $this->actingAs($seller)->get(route('purchase-orders.show', $po))->assertOk()->getContent();

    // Nilai uang termin dalam rupiah utuh: input melangkah 1 dan tidak menampilkan sen.
    foreach (['nilai_tagihan', 'nilai_dibayar', 'nilai_pph'] as $field) {
        expect($html)->toMatch('/name="'.$field.'"[^>]*step="1"/');
    }

    expect($html)->not()->toContain('33333333.33');
});

test('term amounts are stored as whole rupiah', function () {
    $buyerCompany = Company::firstOrCreate(['code' => 'PO-ROUND'], ['name' => 'PT Pembeli']);
    $seller = purchaseOrderUser(['view-purchase-order', 'create-purchase-order', 'verify-purchase-order']);
    $po = PurchaseOrder::create([
        'company_id' => $buyerCompany->id,
        'supplier_company_id' => $seller->company_id,
        'nomor_po' => 'PO-ROUND-1',
        'judul' => 'Pengadaan Barang',
        'supplier_nama' => 'PT Penjual',
        'tgl_po' => '2026-08-05',
        'status' => 'submitted',
        'jenis_transaksi' => 'barang',
        'total' => 100000000,
        'user_id' => $seller->id,
    ]);

    // 100jt dibagi 3 -- pembagian yang dulu menghasilkan 33.333.333,33.
    $this->actingAs($seller)
        ->post(route('purchase-orders.verify', $po), [
            'decision' => 'approved',
            'default_term_count' => 3,
            'first_due_date' => '2026-09-01',
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $amounts = $po->terms()->orderBy('pembayaran_ke')->pluck('nilai_tagihan')->map(fn ($v) => (float) $v)->all();

    foreach ($amounts as $amount) {
        expect($amount)->toBe(round($amount));
    }

    // Sisa pembagian ditaruh di termin terakhir agar jumlahnya tetap pas.
    expect($amounts)->toBe([33333333.0, 33333333.0, 33333334.0])
        ->and(array_sum($amounts))->toBe(100000000.0);

    // Sen yang dikirim dari form ikut dibulatkan saat disimpan.
    $term = $po->terms()->orderBy('pembayaran_ke')->firstOrFail();
    $this->actingAs($seller)
        ->put(route('purchase-orders.terms.billing.update', [$po, $term]), [
            'tanggal_jatuh_tempo' => '2026-09-01',
            'nilai_tagihan' => 33333333.49,
            'nomor_invoice' => 'INV-ROUND-1',
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    expect((float) $term->refresh()->nilai_tagihan)->toBe(33333333.0);
});

test('recap counts payments recorded before the verification step was removed', function () {
    $buyerCompany = Company::firstOrCreate(['code' => 'PO-LEGACY-VERIF'], ['name' => 'PT Pembeli']);
    $seller = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);
    $po = PurchaseOrder::create([
        'company_id' => $buyerCompany->id,
        'supplier_company_id' => $seller->company_id,
        'nomor_po' => 'PO-LEGACY-VERIF-1',
        'judul' => 'Pengadaan Barang',
        'supplier_nama' => 'PT Penjual',
        'tgl_po' => '2026-08-05',
        'status' => 'approved',
        'jenis_transaksi' => 'barang',
        'total' => 20000000,
        'user_id' => $seller->id,
    ]);
    // Termin peninggalan alur lama: sudah dibayar penuh tapi belum sempat diverifikasi.
    $term = $po->terms()->create([
        'pembayaran_ke' => 1,
        'tanggal_jatuh_tempo' => '2026-09-01',
        'nilai_tagihan' => 20000000,
        'nomor_invoice' => 'INV-LAMA-1',
        'nilai_dibayar' => 20000000,
        'payment_verification_status' => 'pending',
        'status' => 'awaiting_verification',
    ]);

    $po->load('terms');

    // Status termin dan rekap PO harus sepakat: lunas berarti sisa pembayaran nol.
    expect($term->calculateStatus())->toBe('paid')
        ->and($po->total_pelunasan)->toBe(20000000.0)
        ->and($po->sisa_pembayaran)->toBe(0.0);
});

test('add term form stays visible for the seller even when nothing is left to schedule', function () {
    $buyerCompany = Company::firstOrCreate(['code' => 'PO-ADDFORM'], ['name' => 'PT Pembeli']);
    $seller = purchaseOrderUser(['view-purchase-order', 'create-purchase-order', 'verify-purchase-order']);
    $po = PurchaseOrder::create([
        'company_id' => $buyerCompany->id,
        'supplier_company_id' => $seller->company_id,
        'nomor_po' => 'PO-ADDFORM-1',
        'judul' => 'Pengadaan Barang',
        'supplier_nama' => 'PT Penjual',
        'tgl_po' => '2026-08-05',
        'status' => 'submitted',
        'jenis_transaksi' => 'barang',
        'total' => 30000000,
        'user_id' => $seller->id,
    ]);

    // Persetujuan membagi habis nilai PO, jadi tidak ada sisa belum terjadwal.
    $this->actingAs($seller)
        ->post(route('purchase-orders.verify', $po), [
            'decision' => 'approved',
            'default_term_count' => 3,
            'first_due_date' => '2026-09-01',
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    expect($po->refresh()->load('terms')->sisa_belum_terjadwal)->toBe(0.0);

    $html = $this->actingAs($seller)->get(route('purchase-orders.show', $po))->assertOk()->getContent();

    expect($html)
        ->toContain('+ Tambah Termin')
        ->toContain('Seluruh nilai PO sudah terjadwal.');

    // Pagar tetap berlaku: menambah termin saat nilainya sudah habis ditolak server.
    $this->actingAs($seller)
        ->from(route('purchase-orders.show', $po))
        ->post(route('purchase-orders.terms.store', $po), [
            'tanggal_jatuh_tempo' => '2026-12-01',
            'nilai_tagihan' => 1000000,
        ])
        ->assertSessionHasErrors('nilai_tagihan');

    // Setelah satu termin dikecilkan, sisanya bisa dijadwalkan ke termin baru.
    $first = $po->terms()->orderBy('pembayaran_ke')->firstOrFail();
    $this->actingAs($seller)
        ->put(route('purchase-orders.terms.billing.update', [$po, $first]), [
            'tanggal_jatuh_tempo' => '2026-09-01',
            'nilai_tagihan' => 4000000,
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $this->actingAs($seller)
        ->post(route('purchase-orders.terms.store', $po), [
            'tanggal_jatuh_tempo' => '2026-12-01',
            'nilai_tagihan' => 6000000,
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    expect($po->refresh()->terms()->count())->toBe(4)
        ->and((float) $po->terms()->sum('nilai_tagihan'))->toBe(30000000.0);

    // Pembeli tetap tidak melihat form tambah termin.
    $buyer = User::factory()->create(['company_id' => $buyerCompany->id]);
    $buyer->roles()->attach($seller->roles->first()->id);
    $buyerHtml = $this->actingAs($buyer)->get(route('purchase-orders.show', $po))->assertOk()->getContent();
    expect($buyerHtml)->not()->toContain('+ Tambah Termin');
});

test('seller can record a purchase order received from an outside customer', function () {
    Storage::fake('local');
    $seller = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);

    $this->actingAs($seller)
        ->post(route('purchase-orders.store'), [
            'sumber' => 'pelanggan_luar',
            'judul' => 'Pengadaan AWLR Dinas PUPR',
            'pembeli_nama' => 'Dinas PUPR Kabupaten Sleman',
            'pembeli_alamat' => 'Jl. Parasamya, Beran, Sleman',
            'tgl_po' => '2026-08-06',
            'status' => 'draft',
            'jenis_transaksi' => 'barang',
            'total' => 30000000,
            'po_file' => UploadedFile::fake()->create('po-pelanggan.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect();

    $po = PurchaseOrder::where('sumber', 'pelanggan_luar')->firstOrFail();

    expect($po->pembeli_nama)->toBe('Dinas PUPR Kabupaten Sleman')
        ->and($po->supplier_company_id)->toBeNull()
        ->and((int) $po->company_id)->toBe((int) $seller->company_id)
        // Tidak ada pihak lain yang menyetujui, jadi langsung aktif.
        ->and($po->status)->toBe('approved')
        ->and($po->po_file_path)->not()->toBeNull();
    Storage::disk('local')->assertExists($po->po_file_path);

    // Perusahaan pemilik data adalah penjualnya, bukan pembelinya.
    expect($po->isSellerCompany($seller->company_id))->toBeTrue()
        ->and($po->isBuyerCompany($seller->company_id))->toBeFalse();

    // Terhitung PO Masuk, bukan PO Keluar.
    $this->actingAs($seller)
        ->get(route('purchase-orders.index', ['direction' => 'incoming']))
        ->assertOk()
        ->assertSee('Pengadaan AWLR Dinas PUPR');
    $this->actingAs($seller)
        ->get(route('purchase-orders.index', ['direction' => 'outgoing']))
        ->assertOk()
        ->assertDontSee('Pengadaan AWLR Dinas PUPR');

    // Halaman detail menampilkan pelanggan luarnya dan memberi hak penagihan.
    $html = $this->actingAs($seller)->get(route('purchase-orders.show', $po))->assertOk()->getContent();
    expect($html)
        ->toContain('Dinas PUPR Kabupaten Sleman')
        ->toContain('Pembeli (pelanggan luar)')
        ->toContain('+ Tambah Termin');

    // Termin, invoice, dan pembayaran berjalan seperti PO antar perusahaan.
    $this->actingAs($seller)
        ->post(route('purchase-orders.terms.store', $po), [
            'tanggal_jatuh_tempo' => '2026-09-06',
            'nilai_tagihan' => 30000000,
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $term = $po->terms()->firstOrFail();

    $this->actingAs($seller)
        ->put(route('purchase-orders.terms.billing.update', [$po, $term]), [
            'tanggal_jatuh_tempo' => '2026-09-06',
            'nilai_tagihan' => 30000000,
            'nomor_invoice' => 'INV-LUAR-1',
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $this->actingAs($seller)
        ->put(route('purchase-orders.terms.payment.update', [$po, $term]), [
            'tanggal_bayar' => '2026-09-10',
            'nilai_dibayar' => 30000000,
            'bukti_bayar_file' => UploadedFile::fake()->create('bukti.pdf', 40, 'application/pdf'),
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    expect($term->refresh()->status)->toBe('paid')
        ->and($po->refresh()->load('terms')->sisa_pembayaran)->toBe(0.0);
});

test('an outside customer purchase order is hidden from other companies', function () {
    $seller = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);
    $otherCompany = Company::firstOrCreate(['code' => 'PO-OUTSIDER'], ['name' => 'PT Lain']);
    $outsider = User::factory()->create(['company_id' => $otherCompany->id]);
    $outsider->roles()->attach($seller->roles->first()->id);

    $po = PurchaseOrder::create([
        'company_id' => $seller->company_id,
        'nomor_po' => 'PO-LUAR-HIDE',
        'judul' => 'Pengadaan Rahasia',
        'sumber' => 'pelanggan_luar',
        'pembeli_nama' => 'Pelanggan Luar',
        'supplier_nama' => 'Perusahaan Kita',
        'tgl_po' => '2026-08-06',
        'status' => 'approved',
        'jenis_transaksi' => 'barang',
        'total' => 10000000,
        'user_id' => $seller->id,
    ]);

    $this->actingAs($outsider)->get(route('purchase-orders.show', $po))->assertForbidden();
    $this->actingAs($outsider)
        ->get(route('purchase-orders.index'))
        ->assertOk()
        ->assertDontSee('Pengadaan Rahasia');
});

test('outside customer purchase order needs only the file, customer name and value', function () {
    Storage::fake('local');
    $seller = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);

    // Judul, tanggal, jenis transaksi, dan status sengaja tidak dikirim.
    $this->actingAs($seller)
        ->post(route('purchase-orders.store'), [
            'sumber' => 'pelanggan_luar',
            'pembeli_nama' => 'Dinas PUPR Kabupaten Sleman',
            'total' => 30000000,
            'po_file' => UploadedFile::fake()->create('PO-4471-Dinas-PUPR.pdf', 90, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $po = PurchaseOrder::where('sumber', 'pelanggan_luar')->firstOrFail();

    expect($po->judul)->toBe('PO-4471-Dinas-PUPR')       // diambil dari nama berkas
        ->and($po->tgl_po->toDateString())->toBe(now()->toDateString())
        ->and($po->jenis_transaksi)->toBe('barang')
        ->and($po->status)->toBe('approved')
        ->and((float) $po->total)->toBe(30000000.0);

    Storage::disk('local')->assertExists($po->po_file_path);

    // Termin langsung bisa disusun karena nilai PO sudah ada.
    $this->actingAs($seller)
        ->post(route('purchase-orders.terms.store', $po), [
            'tanggal_jatuh_tempo' => '2026-09-06',
            'nilai_tagihan' => 30000000,
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    expect($po->terms()->count())->toBe(1);
});

test('outside customer purchase order rejects a submission without the po file', function () {
    $seller = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);

    $this->actingAs($seller)
        ->from(route('purchase-orders.create'))
        ->post(route('purchase-orders.store'), [
            'sumber' => 'pelanggan_luar',
            'pembeli_nama' => 'Dinas PUPR Kabupaten Sleman',
            'total' => 30000000,
        ])
        ->assertSessionHasErrors('po_file');

    expect(PurchaseOrder::count())->toBe(0);
});

test('supplier purchase order still requires its own fields', function () {
    $buyer = purchaseOrderUser(['view-purchase-order', 'create-purchase-order']);

    // Tanpa 'sumber', form lama tetap menuntut judul dan nama supplier.
    $this->actingAs($buyer)
        ->from(route('purchase-orders.create'))
        ->post(route('purchase-orders.store'), [
            'tgl_po' => '2026-08-06',
            'status' => 'draft',
            'jenis_transaksi' => 'barang',
            'total' => 5000000,
        ])
        ->assertSessionHasErrors(['judul', 'supplier_nama']);
});
