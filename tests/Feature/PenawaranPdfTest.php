<?php

use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\PenawaranAttachment;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
use App\Models\PenawaranSignature;
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
