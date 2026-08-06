<?php

use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\PenawaranAttachment;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
use App\Models\PenawaranSignature;
use App\Models\PenawaranTerm;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\StreamedResponse;

function penawaranPdfTestPageSizes(string $pdfContent): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'penawaran_pdf_test_');
    file_put_contents($tmp, $pdfContent);

    try {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($tmp);
        $sizes = [];

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);

            $sizes[] = [
                'orientation' => $size['orientation'],
                'width' => round((float) $size['width'], 2),
                'height' => round((float) $size['height'], 2),
            ];
        }

        return $sizes;
    } finally {
        @unlink($tmp);
    }
}

function penawaranPdfTestResponseContent($response): string
{
    return $response->baseResponse instanceof StreamedResponse
        ? $response->streamedContent()
        : $response->getContent();
}

test('penawaran pdf route accepts a document number route key', function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);

    $company = Company::firstOrCreate(
        ['code' => 'PDF-KEY'],
        ['name' => 'PDF Route Key Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $docNumber = DocNumber::create([
        'company_id' => $company->id,
        'prefix' => 'SPH02',
        'seq' => 57,
        'month' => 3,
        'year' => 2026,
        'doc_no' => '057/SPH02/AS/III/2026',
    ]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'doc_number_id' => $docNumber->id,
        'judul' => 'Penawaran Route Key',
        'instansi_tujuan' => 'Instansi Test',
        'nama_pekerjaan' => 'Pekerjaan Test',
        'lokasi_pekerjaan' => 'Yogyakarta',
        'tanggal_penawaran' => '2026-03-19',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    PenawaranItem::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'judul' => 'Item Test',
        'qty' => 1,
        'satuan' => 'Paket',
        'subtotal' => 1000000,
    ]);

    expect($penawaran->load('docNumber')->pdfRouteKey())->toBe('057-SPH02-AS-III-2026');

    $response = $this->actingAs($user)
        ->get(route('penawaran.pdf', $penawaran->pdfRouteKey()));

    $response->assertOk();

    expect(penawaranPdfTestResponseContent($response))->toStartWith('%PDF');
});

test('penawaran pdf keeps numeric term order when there are more than nine terms', function () {
    $company = Company::firstOrCreate(
        ['code' => 'PDF-TERM-ORDER'],
        ['name' => 'PDF Term Order Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'judul' => 'Penawaran Term Order PDF',
        'instansi_tujuan' => 'Instansi Test',
        'tanggal_penawaran' => '2026-07-27',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);

    foreach (range(1, 12) as $urutan) {
        PenawaranTerm::create([
            'penawaran_id' => $penawaran->id,
            'urutan' => $urutan,
            'isi' => sprintf('Keterangan urutan %02d', $urutan),
        ]);
    }

    $penawaran->load([
        'docNumber',
        'cover',
        'company',
        'validity',
        'terms',
        'user.roles',
        'signatures',
        'items.details',
    ]);

    $html = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-TERM-ORDER-TEST',
        'total' => 0,
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    $positions = collect(range(1, 12))
        ->map(fn($urutan) => strpos($html, sprintf('Keterangan urutan %02d', $urutan)))
        ->all();

    expect($positions)
        ->not->toContain(false)
        ->toBe(collect($positions)->sort()->values()->all());
});

test('penawaran pdf embeds signature ttd images from public storage', function () {
    Storage::fake('public');

    $company = Company::firstOrCreate(
        ['code' => 'PDF-TTD'],
        ['name' => 'PDF Signature Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'judul' => 'Penawaran Signature PDF',
        'instansi_tujuan' => 'Instansi Test',
        'tanggal_penawaran' => '2026-07-27',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    PenawaranItem::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'judul' => 'Item Test',
        'qty' => 1,
        'satuan' => 'Lot',
        'subtotal' => 750000,
    ]);
    PenawaranSignature::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'nama' => 'Afif Faishahuda',
        'jabatan' => 'Corporate Account Manager',
        'kota' => 'Sleman',
        'tanggal' => '2026-07-27',
        'ttd_path' => 'penawaran/ttd/afif.png',
    ]);

    Storage::disk('public')->put(
        'penawaran/ttd/afif.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
    );

    $penawaran->load([
        'docNumber',
        'cover',
        'company',
        'validity',
        'terms',
        'user.roles',
        'signatures',
        'items.details',
    ]);

    $html = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-TTD-TEST',
        'total' => 750000,
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    expect($html)
        ->toContain('Afif Faishahuda')
        ->toContain('<img src=');

    expect(
        str_contains($html, 'data:image/png;base64,') ||
        str_contains($html, '/storage/penawaran/ttd/afif.png')
    )->toBeTrue();
});

test('penawaran pdf falls back to storage url when signature file is not local', function () {
    Storage::fake('public');
    config(['app.url' => 'https://marketing.test']);

    $company = Company::firstOrCreate(
        ['code' => 'PDF-TTD-URL'],
        ['name' => 'PDF Signature URL Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'judul' => 'Penawaran Signature URL PDF',
        'instansi_tujuan' => 'Instansi Test',
        'tanggal_penawaran' => '2026-07-27',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    PenawaranSignature::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'nama' => 'Afif Faishahuda',
        'jabatan' => 'Corporate Account Manager',
        'kota' => 'Sleman',
        'tanggal' => '2026-07-27',
        'ttd_path' => 'signatures/missing-afif.png',
    ]);

    $penawaran->load([
        'docNumber',
        'cover',
        'company',
        'validity',
        'terms',
        'user.roles',
        'signatures',
        'items.details',
    ]);

    $html = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-TTD-URL-TEST',
        'total' => 0,
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    expect($html)
        ->toContain('Afif Faishahuda')
        ->toContain('/storage/signatures/missing-afif.png');
});

test('penawaran pdf raises akhmad zaeni signature slightly', function () {
    Storage::fake('public');

    $company = Company::firstOrCreate(
        ['code' => 'PDF-TTD-AKHMAD'],
        ['name' => 'PDF Signature Akhmad Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'judul' => 'Penawaran Signature Akhmad PDF',
        'instansi_tujuan' => 'Instansi Test',
        'tanggal_penawaran' => '2026-07-28',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    PenawaranSignature::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'nama' => 'Akhmad Zaeni Mustofa',
        'jabatan' => 'Business Development',
        'kota' => 'Sleman',
        'tanggal' => '2026-07-28',
        'ttd_path' => 'penawaran/ttd/akhmad.png',
    ]);

    $penawaran->load([
        'docNumber',
        'cover',
        'company',
        'validity',
        'terms',
        'user.roles',
        'signatures',
        'items.details',
    ]);

    $html = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-TTD-AKHMAD-TEST',
        'total' => 0,
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    expect($html)
        ->toContain('Akhmad Zaeni Mustofa')
        ->toContain('bottom:14px;');
});

test('penawaran pdf breaks long item details into separate table rows', function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);

    $company = Company::firstOrCreate(
        ['code' => 'PDF-LONG'],
        ['name' => 'PDF Long Detail Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $docNumber = DocNumber::create([
        'company_id' => $company->id,
        'prefix' => 'LONG',
        'seq' => 27,
        'month' => 7,
        'year' => 2026,
        'doc_no' => '027/LONG/TEST/VII/2026',
    ]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'doc_number_id' => $docNumber->id,
        'judul' => 'Penawaran Detail Panjang',
        'instansi_tujuan' => 'Instansi Test',
        'nama_pekerjaan' => 'Pekerjaan Detail Panjang',
        'lokasi_pekerjaan' => 'Yogyakarta',
        'tanggal_penawaran' => '2026-07-24',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    $item = PenawaranItem::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'judul' => 'Automatic Water Level Recorder',
        'qty' => 1,
        'satuan' => 'paket',
        'subtotal' => 1000000,
    ]);

    foreach (range(1, 30) as $index) {
        PenawaranItemDetail::create([
            'penawaran_item_id' => $item->id,
            'urutan' => $index,
            'nama' => 'Long Specification Detail ' . $index,
            'spesifikasi' => str_repeat('Weather station technical specification with installation notes. ', 10),
            'qty' => 1,
            'harga' => 10000,
            'subtotal' => 10000,
        ]);
    }

    $penawaran->load([
        'docNumber',
        'cover',
        'company',
        'validity',
        'terms',
        'user.roles',
        'signatures',
        'items.details',
    ]);

    $html = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => $docNumber->doc_no,
        'total' => 1000000,
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    expect(substr_count($html, 'class="item-detail-row'))->toBe(30)
        ->and(substr_count($html, 'class="item-detail-row item-detail-last"'))->toBe(1)
        ->and($html)->toContain('class="item-page-break-row"')
        ->and($html)->toContain('aa.')
        ->and($html)->not()->toContain('10.000')
        ->and($html)->not()->toContain('<ol');

    $pricelistHtml = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => $docNumber->doc_no,
        'total' => 1000000,
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => true,
    ])->render();

    expect($pricelistHtml)->toContain('10.000');

    $pdfContent = Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();

    expect(penawaranPdfTestPageSizes($pdfContent))->not()->toBeEmpty();
});

test('penawaran pdf does not add manual page break borders for short details', function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);

    $company = Company::firstOrCreate(
        ['code' => 'PDF-SHORT'],
        ['name' => 'PDF Short Detail Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $docNumber = DocNumber::create([
        'company_id' => $company->id,
        'prefix' => 'SHORT',
        'seq' => 153,
        'month' => 6,
        'year' => 2026,
        'doc_no' => '153/SHORT/TEST/VI/2026',
    ]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'doc_number_id' => $docNumber->id,
        'judul' => 'Penawaran Detail Pendek',
        'instansi_tujuan' => 'Instansi Test',
        'tanggal_penawaran' => '2026-06-10',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    $item = PenawaranItem::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'judul' => 'Instalasi Perangkat AWLR',
        'qty' => 1,
        'satuan' => 'lot',
        'subtotal' => 22000000,
    ]);

    foreach (range(1, 12) as $index) {
        PenawaranItemDetail::create([
            'penawaran_item_id' => $item->id,
            'urutan' => $index,
            'nama' => 'Detail pendek ' . $index,
            'qty' => 1,
            'satuan' => 'lot',
            'harga' => $index === 1 ? 22000000 : 0,
            'subtotal' => $index === 1 ? 22000000 : 0,
        ]);
    }

    $penawaran->load([
        'docNumber',
        'cover',
        'company',
        'validity',
        'terms',
        'user.roles',
        'signatures',
        'items.details',
    ]);

    $html = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => $docNumber->doc_no,
        'total' => 22000000,
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    expect($html)
        ->not()->toContain('class="item-page-break-row"')
        ->and(substr_count($html, '22.000.000'))->toBe(3);
});

test('penawaran pdf shows a per item discount column that marks undiscounted items', function () {
    $company = Company::firstOrCreate(
        ['code' => 'PDF-ITEM-DISC'],
        ['name' => 'PDF Item Discount Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'judul' => 'Penawaran Diskon Per Item',
        'instansi_tujuan' => 'Instansi Test',
        'tanggal_penawaran' => '2026-08-06',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);

    $discounted = PenawaranItem::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'judul' => 'Pompa Air',
        'qty' => 2,
        'satuan' => 'unit',
        'subtotal' => 5000000,
        'discount_enabled' => true,
        'discount_type' => 'percent',
        'discount_value' => 10,
    ]);
    PenawaranItemDetail::create([
        'penawaran_item_id' => $discounted->id,
        'urutan' => 1,
        'nama' => 'Pompa Air Sentrifugal',
        'qty' => 1,
        'satuan' => 'unit',
        'harga' => 5000000,
        'subtotal' => 5000000,
    ]);

    $plain = PenawaranItem::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 2,
        'judul' => 'Instalasi',
        'qty' => 1,
        'satuan' => 'lot',
        'subtotal' => 2000000,
    ]);
    PenawaranItemDetail::create([
        'penawaran_item_id' => $plain->id,
        'urutan' => 1,
        'nama' => 'Pemasangan di lokasi',
        'qty' => 1,
        'satuan' => 'lot',
        'harga' => 2000000,
        'subtotal' => 2000000,
    ]);

    $kop = [
        'logo' => public_path('images/logo_arsol.png'),
        'stamp' => public_path('images/cap_arsol.png'),
        'nama' => $company->name,
        'alamat' => 'Alamat Test',
        'telp' => '000',
        'email' => 'test@example.com',
    ];

    $penawaran->load([
        'docNumber',
        'cover',
        'company',
        'validity',
        'terms',
        'user.roles',
        'signatures',
        'items.details',
    ]);

    $html = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-ITEM-DISC-TEST',
        'total' => 11000000,
        'kop' => $kop,
        'pricelistMode' => false,
    ])->render();

    expect($html)
        ->toContain('>Diskon</th>')
        ->toContain('10%')
        // Item tanpa diskon ditandai strip, bukan dibiarkan kosong.
        ->toContain('<span class="muted">-</span>')
        ->toContain('colspan="3"')
        // 5.000.000 x 2 = 10.000.000, diskon 10% -> total item 9.000.000.
        ->toContain('9.000.000')
        // Jumlah keseluruhan: 9.000.000 + 2.000.000.
        ->toContain('11.000.000');

    // Tanpa diskon per item, tabel tetap memakai lima kolom seperti sebelumnya.
    $discounted->forceFill(['discount_enabled' => false])->save();
    $penawaran->load('items.details');

    $plainHtml = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-ITEM-DISC-TEST',
        'total' => 12000000,
        'kop' => $kop,
        'pricelistMode' => false,
    ])->render();

    expect($plainHtml)
        ->not()->toContain('>Diskon</th>')
        ->toContain('12.000.000');
});

test('penawaran pdf totals reconcile item discount and global discount separately', function () {
    $company = Company::firstOrCreate(
        ['code' => 'PDF-TOTAL-DISC'],
        ['name' => 'PDF Total Discount Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'judul' => 'Penawaran Rekap Diskon',
        'instansi_tujuan' => 'Instansi Test',
        'tanggal_penawaran' => '2026-08-06',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
        'discount_enabled' => true,
        'discount_type' => 'percent',
        'discount_value' => 5,
        'tax_enabled' => true,
        'tax_rate' => 11,
    ]);

    $rows = [
        ['Pompa Air', 2, 5000000, true],
        ['Instalasi', 1, 2000000, false],
    ];

    foreach ($rows as $i => [$judul, $qty, $harga, $withDiscount]) {
        $item = PenawaranItem::create([
            'penawaran_id' => $penawaran->id,
            'urutan' => $i + 1,
            'judul' => $judul,
            'qty' => $qty,
            'satuan' => 'unit',
            'subtotal' => $harga,
            'discount_enabled' => $withDiscount,
            'discount_type' => $withDiscount ? 'percent' : null,
            'discount_value' => $withDiscount ? 10 : null,
        ]);
        PenawaranItemDetail::create([
            'penawaran_item_id' => $item->id,
            'urutan' => 1,
            'nama' => $judul . ' lengkap',
            'qty' => 1,
            'satuan' => 'unit',
            'harga' => $harga,
            'subtotal' => $harga,
        ]);
    }

    $penawaran->load([
        'docNumber',
        'cover',
        'company',
        'validity',
        'terms',
        'user.roles',
        'signatures',
        'items.details',
    ]);

    $html = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-TOTAL-DISC-TEST',
        'total' => $penawaran->calcGrandTotal(),
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    // Rantai lengkap: 12.000.000 - 1.000.000 (diskon item) - 550.000 (diskon global 5%)
    // = 10.450.000, lalu PPN 11% = 1.149.500, total 11.599.500.
    expect($html)
        ->toContain('Harga Sebelum Diskon')
        ->toContain('12.000.000')
        ->toContain('Diskon (%) Instalasi')
        ->toContain('1.000.000')
        ->toContain('Diskon Tambahan (5%)')
        ->toContain('550.000')
        ->toContain('Harga Sebelum Pajak')
        ->toContain('10.450.000')
        ->toContain('1.149.500')
        ->toContain('11.599.500');

    expect($penawaran->calcGrandTotal())->toBe(11599500);

    // Diskon global saja: label lama "Diskon (5%)" harus tetap dipakai supaya
    // dokumen yang belum pernah pakai diskon per item tidak berubah bunyinya.
    PenawaranItem::where('penawaran_id', $penawaran->id)->update(['discount_enabled' => false]);
    $penawaran->load('items.details');

    $globalOnlyHtml = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-TOTAL-DISC-TEST',
        'total' => $penawaran->calcGrandTotal(),
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    expect($globalOnlyHtml)
        ->toContain('Diskon (5%)')
        ->not()->toContain('Diskon Tambahan')
        ->not()->toContain('Diskon Alat')
        ->not()->toContain('Diskon (%) Instalasi')
        ->toContain('Harga Sebelum Pajak');

    // Tanpa diskon sama sekali, blok total kembali ke bentuk lama.
    $penawaran->forceFill(['discount_enabled' => false])->save();
    $penawaran->load('items.details');

    $plainHtml = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-TOTAL-DISC-TEST',
        'total' => $penawaran->calcGrandTotal(),
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    expect($plainHtml)
        ->not()->toContain('Harga Sebelum Diskon')
        ->not()->toContain('Diskon Alat')
        ->not()->toContain('Diskon (%) Instalasi')
        ->not()->toContain('Harga Sebelum Pajak')
        ->toContain('12.000.000');
});

test('penawaran pdf labels nominal item discounts as alat and percentage ones as instalasi', function () {
    $company = Company::firstOrCreate(
        ['code' => 'PDF-MIX-DISC'],
        ['name' => 'PDF Mixed Discount Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'judul' => 'Penawaran Diskon Campuran',
        'instansi_tujuan' => 'Instansi Test',
        'tanggal_penawaran' => '2026-08-06',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
        'tax_enabled' => true,
        'tax_rate' => 11,
    ]);

    $rows = [
        ['Instalasi dan Commissioning', 2, 24500000, 'percent', 10],
        ['Rain Gauge Tipping Bucket', 3, 8750000, 'fixed', 1500000],
        ['Pekerjaan Sipil', 1, 12000000, null, null],
    ];

    foreach ($rows as $i => [$judul, $qty, $harga, $type, $value]) {
        $item = PenawaranItem::create([
            'penawaran_id' => $penawaran->id,
            'urutan' => $i + 1,
            'judul' => $judul,
            'qty' => $qty,
            'satuan' => 'unit',
            'subtotal' => $harga,
            'discount_enabled' => $type !== null,
            'discount_type' => $type,
            'discount_value' => $value,
        ]);
        PenawaranItemDetail::create([
            'penawaran_item_id' => $item->id,
            'urutan' => 1,
            'nama' => $judul . ' lengkap',
            'qty' => 1,
            'satuan' => 'unit',
            'harga' => $harga,
            'subtotal' => $harga,
        ]);
    }

    $penawaran->load([
        'docNumber',
        'cover',
        'company',
        'validity',
        'terms',
        'user.roles',
        'signatures',
        'items.details',
    ]);

    $html = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-MIX-DISC-TEST',
        'total' => $penawaran->calcGrandTotal(),
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    // Kotor 87.250.000 - 1.500.000 (nominal) - 4.900.000 (persen) = 80.850.000,
    // PPN 11% = 8.893.500, total 89.743.500.
    expect($html)
        ->toContain('Diskon Alat')
        ->toContain('Diskon (%) Instalasi')
        ->not()->toContain('Diskon Item')
        ->toContain('87.250.000')
        ->toContain('1.500.000')
        ->toContain('80.850.000')
        ->toContain('89.743.500');

    // Nilai rupiah diskon persen muncul dua kali: di kolom item dan di baris rekap.
    expect(substr_count($html, '4.900.000'))->toBeGreaterThanOrEqual(2);

    expect($penawaran->calcGrandTotal())->toBe(89743500);

    // Kalau tipe nominal tidak dipakai, baris "Diskon Alat" ikut hilang.
    PenawaranItem::where('penawaran_id', $penawaran->id)
        ->where('discount_type', 'fixed')
        ->update(['discount_enabled' => false]);
    $penawaran->load('items.details');

    $singleTypeHtml = view('documents.penawaran_full', [
        'penawaran' => $penawaran,
        'docNo' => 'PNW-MIX-DISC-TEST',
        'total' => $penawaran->calcGrandTotal(),
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => public_path('images/cap_arsol.png'),
            'nama' => $company->name,
            'alamat' => 'Alamat Test',
            'telp' => '000',
            'email' => 'test@example.com',
        ],
        'pricelistMode' => false,
    ])->render();

    expect($singleTypeHtml)
        ->toContain('Diskon (%) Instalasi')
        ->not()->toContain('Diskon Alat');
});

test('penawaran pdf appends suket pp 55 as the last page', function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
    Storage::fake('public');

    $company = Company::firstOrCreate(
        ['code' => 'PDF-TEST'],
        ['name' => 'PDF Test Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $docNumber = DocNumber::create([
        'company_id' => $company->id,
        'prefix' => 'PDF',
        'seq' => 1,
        'month' => 6,
        'year' => 2026,
        'doc_no' => '001/PDF/TEST/VI/2026',
    ]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'doc_number_id' => $docNumber->id,
        'judul' => 'Penawaran Test PDF',
        'instansi_tujuan' => 'Instansi Test',
        'nama_pekerjaan' => 'Pekerjaan Test',
        'lokasi_pekerjaan' => 'Yogyakarta',
        'tanggal_penawaran' => '2026-06-19',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);

    Storage::disk('public')->makeDirectory('penawaran/lampiran');
    $attachmentPath = 'penawaran/lampiran/kotak-test.pdf';
    $fullAttachmentPath = Storage::disk('public')->path($attachmentPath);

    $attachmentPdf = new Fpdi('P', 'mm', [100, 100]);
    $attachmentPdf->AddPage();
    $attachmentPdf->SetFont('Arial', '', 10);
    $attachmentPdf->Cell(40, 10, 'Lampiran test');
    $attachmentPdf->Output('F', $fullAttachmentPath);

    PenawaranAttachment::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'judul' => 'Lampiran Kotak Test',
        'file_path' => $attachmentPath,
        'mime' => 'application/pdf',
        'size' => filesize($fullAttachmentPath),
    ]);

    $response = $this->actingAs($user)
        ->get(route('penawaran.pdf', $penawaran));

    $response->assertOk();

    $outputSizes = penawaranPdfTestPageSizes(penawaranPdfTestResponseContent($response));
    $suketSizes = penawaranPdfTestPageSizes(file_get_contents(base_path('Suket PP 55 Tahun 2022 - CV Arta Solusindo.pdf')));

    expect($outputSizes)->not()->toBeEmpty()
        ->and($outputSizes[array_key_last($outputSizes)])->toBe($suketSizes[0]);
});

test('penawaran pdf includes a compressed (PDF 1.5+) brochure attachment', function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
    Storage::fake('public');

    $company = Company::firstOrCreate(
        ['code' => 'PDF-CMP'],
        ['name' => 'PDF Compressed Company']
    );
    $user = User::factory()->create(['company_id' => $company->id]);
    $docNumber = DocNumber::create([
        'company_id' => $company->id,
        'prefix' => 'CMP',
        'seq' => 1,
        'month' => 6,
        'year' => 2026,
        'doc_no' => '002/CMP/TEST/VI/2026',
    ]);
    $penawaran = Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $user->id,
        'doc_number_id' => $docNumber->id,
        'judul' => 'Penawaran Brosur Terkompresi',
        'instansi_tujuan' => 'Instansi Test',
        'nama_pekerjaan' => 'Pekerjaan Test',
        'lokasi_pekerjaan' => 'Yogyakarta',
        'tanggal_penawaran' => '2026-06-19',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);

    // A real-world brochure: PDF 1.5 with a compressed cross-reference stream.
    // The free FPDI parser cannot read this, which is why it silently vanished
    // from the merged output. Page is a 200x200pt square (~70.56mm).
    Storage::disk('public')->makeDirectory('penawaran/lampiran');
    $attachmentPath = 'penawaran/lampiran/brosur-terkompresi.pdf';
    Storage::disk('public')->put(
        $attachmentPath,
        file_get_contents(base_path('tests/fixtures/compressed-brochure.pdf'))
    );

    PenawaranAttachment::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'judul' => 'Brosur Produk',
        'file_path' => $attachmentPath,
        'mime' => 'application/pdf',
        'size' => Storage::disk('public')->size($attachmentPath),
    ]);

    $response = $this->actingAs($user)
        ->get(route('penawaran.pdf', $penawaran));

    $response->assertOk();

    $outputSizes = penawaranPdfTestPageSizes(penawaranPdfTestResponseContent($response));

    // The 200x200pt (≈70.56mm square) brochure page must survive into the output.
    $hasBrochurePage = collect($outputSizes)->contains(
        fn ($size) => abs($size['width'] - 70.56) < 1 && abs($size['height'] - 70.56) < 1
    );

    expect($hasBrochurePage)->toBeTrue(
        'Compressed brochure attachment did not appear in the merged PDF'
    );
});
