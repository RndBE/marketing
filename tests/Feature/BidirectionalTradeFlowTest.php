<?php

use App\Models\Company;
use App\Models\Penawaran;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\User;
use App\Models\UsulanPenawaran;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function tradeFlowUser(Company $company, string $label): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Trade Flow '.$label,
        'slug' => 'trade-flow-'.strtolower($label).'-'.uniqid(),
    ]);
    $slugs = [
        'view-usulan', 'create-usulan', 'edit-usulan', 'respond-usulan',
        'view-penawaran', 'create-penawaran', 'edit-penawaran',
        'view-purchase-order', 'create-purchase-order',
    ];

    foreach ($slugs as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Trade Flow']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }
    $user->roles()->attach($role->id);

    return $user;
}

function executeTradeFlow($test, User $buyer, User $seller, string $reference): PurchaseOrder
{
    $test->actingAs($buyer)
        ->get(route('penawaran-harga.create'))
        ->assertOk()
        ->assertSee('Detail / Spesifikasi per Poin')
        ->assertSee('+ Tambah Poin');

    $test->actingAs($buyer)
        ->post(route('penawaran-harga.store'), [
            'target_company_id' => $seller->company_id,
            'judul' => 'Permintaan '.$reference,
            'jenis_transaksi' => 'jasa',
            'deskripsi' => 'Permintaan harga dua arah '.$reference,
            'tanggal_dibutuhkan' => '2026-12-01',
            'status' => 'menunggu',
            'items_present' => 1,
            'item_judul' => ['Jasa Implementasi'],
            'item_poin' => [[
                'Konfigurasi server '.$reference,
                'Instalasi dan pengujian di lokasi',
            ]],
            'item_qty' => [1],
            'item_satuan' => ['paket'],
            'item_harga' => [0],
            'item_tipe' => ['custom'],
            'item_product_id' => [''],
            'attachments' => [UploadedFile::fake()->create('spesifikasi-'.$reference.'.pdf', 30, 'application/pdf')],
            'attachment_types' => ['dokumen'],
        ])
        ->assertRedirect();

    $request = UsulanPenawaran::query()->where('judul', 'Permintaan '.$reference)->firstOrFail();
    expect((int) $request->company_id)->toBe((int) $buyer->company_id)
        ->and((int) $request->target_company_id)->toBe((int) $seller->company_id)
        ->and($request->items()->firstOrFail()->catatan)->toBe(
            "Konfigurasi server {$reference}\nInstalasi dan pengujian di lokasi"
        );
    $test->actingAs($buyer)
        ->get(route('penawaran-harga.edit', $request))
        ->assertOk()
        ->assertSee('Konfigurasi server '.$reference)
        ->assertSee('Instalasi dan pengujian di lokasi')
        ->assertSee('+ Tambah Poin');
    $test->actingAs($buyer)
        ->get(route('penawaran-harga.index', ['direction' => 'outgoing']))
        ->assertOk()
        ->assertSee('Permintaan '.$reference)
        ->assertSee('Keluar · Anda pembeli');
    $test->actingAs($seller)
        ->get(route('penawaran-harga.index', ['direction' => 'incoming']))
        ->assertOk()
        ->assertSee('Permintaan '.$reference)
        ->assertSee('Masuk · Anda penjual');
    $test->actingAs($seller)
        ->get(route('penawaran-harga.show', $request))
        ->assertOk()
        ->assertSee('Anda bertindak sebagai penjual')
        ->assertSee('Langkah berikutnya: Tanggapi dan buat penawaran');
    $attachment = $request->attachments()->firstOrFail();
    $test->actingAs($seller)
        ->get(route('penawaran-harga.attachments.download', [$request, $attachment]))
        ->assertDownload('spesifikasi-'.$reference.'.pdf');

    $test->actingAs($seller)
        ->post(route('penawaran-harga.tanggapi', $request), [
            'tanggapan' => 'Permintaan diterima dan penawaran disiapkan.',
            'status' => 'disetujui',
            'penawaran_action' => 'from_usulan',
        ])
        ->assertRedirect(route('penawaran-harga.quotation.show', $request));

    $request->refresh()->load('penawaran.items.details');
    expect((int) $request->penawaran->company_id)->toBe((int) $seller->company_id)
        ->and($request->penawaran->items->first()->catatan)->toBe(
            "Konfigurasi server {$reference}\nInstalasi dan pengujian di lokasi"
        );
    $test->actingAs($seller)
        ->get(route('penawaran-harga.quotation.show', $request))
        ->assertOk()
        ->assertSee('Penawaran Harga dari Permohonan')
        ->assertSee('Dokumen khusus alur Permohonan Harga')
        ->assertSee('Item dan Harga')
        ->assertSee('Simpan Penawaran Harga');

    $test->actingAs($seller)
        ->get(route('penawaran.show', $request->penawaran))
        ->assertRedirect(route('penawaran-harga.quotation.show', $request));
    $test->actingAs($seller)
        ->get(route('penawaran.pdf', $request->penawaran->pdfRouteKey()))
        ->assertRedirect(route('penawaran-harga.quotation.pdf', $request));

    $test->actingAs($buyer)
        ->get(route('penawaran-harga.quotation.show', $request))
        ->assertForbidden();
    Penawaran::create([
        'company_id' => $seller->company_id,
        'id_user' => $seller->id,
        'judul' => 'Penawaran Mandiri '.$reference,
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    $test->actingAs($seller)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertDontSee('Permintaan '.$reference)
        ->assertDontSee('Dari Permohonan Harga')
        ->assertSee('Penawaran Mandiri '.$reference);
    $quotationItem = $request->penawaran->items->first();
    $test->actingAs($seller)
        ->put(route('penawaran-harga.quotation.update', $request), [
            'tanggal_penawaran' => '2026-08-05',
            'nama_pekerjaan' => 'Implementasi '.$reference,
            'valid_until' => '2026-09-05',
            'items' => [
                $quotationItem->id => [
                    'judul' => $quotationItem->judul,
                    'catatan' => $quotationItem->catatan,
                    'qty' => 1,
                    'satuan' => 'paket',
                    'unit_price' => 100000000,
                ],
            ],
            'discount_enabled' => 0,
            'tax_enabled' => 0,
            'signature_name' => $seller->name,
            'signature_position' => 'Penanggung Jawab',
            'signature_city' => 'Yogyakarta',
            'signature_date' => '2026-08-05',
        ])
        ->assertRedirect(route('penawaran-harga.quotation.show', $request));

    $request->refresh()->load('penawaran.items.details');
    expect($request->penawaran->calcGrandTotal())->toBe(100000000);

    $test->actingAs($seller)
        ->post(route('penawaran-harga.kirim-penawaran', $request))
        ->assertRedirect();
    expect($request->refresh()->penawaran_status)->toBe('sent');

    $test->actingAs($buyer)
        ->get(route('penawaran-harga.show', $request))
        ->assertOk()
        ->assertSee('Anda bertindak sebagai pembeli')
        ->assertSee('Langkah berikutnya: Periksa dan putuskan penawaran');
    $test->actingAs($buyer)
        ->get(route('penawaran-harga.quotation.show', $request))
        ->assertOk()
        ->assertSee('Penawaran Harga dari Permohonan')
        ->assertSee('Mode lihat')
        ->assertDontSee('Simpan Penawaran Harga');

    $test->actingAs($buyer)
        ->post(route('penawaran-harga.tanggapi-penawaran', $request), [
            'action' => 'accepted',
        ])
        ->assertRedirect();
    expect($request->refresh()->penawaran_status)->toBe('accepted');

    $test->actingAs($buyer)
        ->post(route('purchase-orders.store'), [
            'usulan_id' => $request->id,
            'nomor_po' => 'PO-'.$reference,
            'judul' => 'PO '.$reference,
            'supplier_nama' => $seller->company->name,
            'tgl_po' => '2026-08-05',
            'status' => 'submitted',
            'jenis_transaksi' => 'jasa',
            'total' => 100000000,
            'po_file' => UploadedFile::fake()->create('po-'.$reference.'.pdf', 50, 'application/pdf'),
        ])
        ->assertRedirect();

    $po = PurchaseOrder::query()->where('nomor_po', 'PO-'.$reference)->firstOrFail();
    expect((int) $po->company_id)->toBe((int) $buyer->company_id)
        ->and((int) $po->supplier_company_id)->toBe((int) $seller->company_id)
        ->and($po->status)->toBe('submitted');
    $test->actingAs($buyer)
        ->get(route('purchase-orders.index', ['direction' => 'outgoing']))
        ->assertOk()
        ->assertSee('PO-'.$reference)
        ->assertSee('PO Keluar · Anda pembeli');
    $test->actingAs($seller)
        ->get(route('purchase-orders.index', ['direction' => 'incoming']))
        ->assertOk()
        ->assertSee('PO-'.$reference)
        ->assertSee('PO Masuk · Anda penjual');
    $test->actingAs($seller)
        ->get(route('purchase-orders.show', $po))
        ->assertOk()
        ->assertSee('Langkah berikutnya: Verifikasi PO masuk');

    $test->actingAs($seller)
        ->post(route('purchase-orders.verify', $po), [
            'decision' => 'approved',
            'default_term_count' => 5,
            'first_due_date' => '2026-09-01',
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    $po->refresh();
    expect($po->status)->toBe('approved')
        ->and($po->terms()->count())->toBe(5)
        ->and((float) $po->terms()->sum('nilai_tagihan'))->toBe(100000000.0);

    $term = $po->terms()->orderBy('pembayaran_ke')->firstOrFail();
    $test->actingAs($buyer)
        ->put(route('purchase-orders.terms.billing.update', [$po, $term]), [
            'tanggal_jatuh_tempo' => '2026-09-01',
            'nilai_tagihan' => 20000000,
        ])
        ->assertForbidden();

    $test->actingAs($seller)
        ->put(route('purchase-orders.terms.billing.update', [$po, $term]), [
            'tanggal_jatuh_tempo' => '2026-09-01',
            'nilai_tagihan' => 20000000,
            'nomor_invoice' => 'INV-'.$reference.'-01',
            'tanggal_invoice' => '2026-08-20',
            'invoice_file' => UploadedFile::fake()->create('invoice.pdf', 50, 'application/pdf'),
            'nomor_faktur' => 'FP-'.$reference.'-01',
            'faktur_file' => UploadedFile::fake()->create('faktur.pdf', 50, 'application/pdf'),
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    // Penjual mengerjakan seluruh pencatatan termin; kedua kartu jadi tugasnya.
    $test->actingAs($seller)
        ->get(route('purchase-orders.show', $po))
        ->assertOk()
        ->assertSee('Invoice & Faktur', false)
        ->assertSee('Pembayaran & PPh', false)
        ->assertSee('Anda bertindak sebagai penjual')
        ->assertSee('Tugas Anda')
        ->assertDontSee('Oleh pembeli');

    // Pembeli hanya melihat: kedua kartu ditandai dikerjakan penjual.
    $test->actingAs($buyer)
        ->get(route('purchase-orders.show', $po))
        ->assertOk()
        ->assertSee('Pembayaran & PPh', false)
        ->assertSee('Anda bertindak sebagai pembeli')
        ->assertSee('Oleh penjual')
        ->assertDontSee('Tugas Anda');

    $test->actingAs($buyer)
        ->put(route('purchase-orders.terms.payment.update', [$po, $term]), [
            'tanggal_bayar' => '2026-08-25',
            'nilai_dibayar' => 19600000,
        ])
        ->assertForbidden();

    $test->actingAs($seller)
        ->put(route('purchase-orders.terms.payment.update', [$po, $term]), [
            'tanggal_bayar' => '2026-08-25',
            'nilai_dibayar' => 19600000,
            'bukti_bayar_file' => UploadedFile::fake()->create('bukti-bayar.pdf', 50, 'application/pdf'),
            'jenis_pph' => 'pph_23',
            'nilai_pph' => 400000,
            'bukti_potong_pph_file' => UploadedFile::fake()->create('bupot.pdf', 50, 'application/pdf'),
        ])
        ->assertRedirect(route('purchase-orders.show', $po));

    // Langsung lunas, tanpa singgah di status menunggu verifikasi.
    expect($term->refresh()->status)->toBe('paid');

    return $po;
}

test('request price form handles null flashed item input', function () {
    $buyerCompany = Company::create(['code' => 'BUYER-NULL', 'name' => 'Buyer Null Input']);
    Company::create(['code' => 'SELLER-NULL', 'name' => 'Seller Null Input']);
    $buyer = tradeFlowUser($buyerCompany, 'NULL-INPUT');

    $this->actingAs($buyer)
        ->withSession(['_old_input' => [
            'item_judul' => null,
            'jenis_transaksi' => 'barang',
            'deskripsi' => 'Kebutuhan uji Blade',
        ]])
        ->get(route('penawaran-harga.create'))
        ->assertOk()
        ->assertSee('Buat Usulan Penawaran')
        ->assertSee('value="barang" selected>Barang</option>', false)
        ->assertSee('value="jasa"', false)
        ->assertSee('>Jasa</option>', false)
        ->assertSee('value="campuran"', false)
        ->assertSee('>Barang + Jasa</option>', false)
        ->assertSee('Kebutuhan uji Blade')
        ->assertDontSee("{{ old('deskripsi') }}", false);
});

test('the same paperless trade flow works from CV to PT and from PT to CV', function () {
    Storage::fake('local');
    $cv = Company::create(['code' => 'CV-TEST', 'name' => 'CV Arta Test']);
    $pt = Company::create(['code' => 'PT-TEST', 'name' => 'PT Mitra Test']);
    $cvUser = tradeFlowUser($cv, 'CV');
    $ptUser = tradeFlowUser($pt, 'PT');

    $cvToPt = executeTradeFlow($this, $cvUser, $ptUser, 'CV-PT');
    $ptToCv = executeTradeFlow($this, $ptUser, $cvUser, 'PT-CV');

    expect((int) $cvToPt->company_id)->toBe((int) $cv->id)
        ->and((int) $cvToPt->supplier_company_id)->toBe((int) $pt->id)
        ->and((int) $ptToCv->company_id)->toBe((int) $pt->id)
        ->and((int) $ptToCv->supplier_company_id)->toBe((int) $cv->id);
});
