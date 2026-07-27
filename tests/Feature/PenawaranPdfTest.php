<?php

use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\PenawaranAttachment;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
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
        ->and($html)->toContain('aa.')
        ->and($html)->not()->toContain('<ol');

    $pdfContent = Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();

    expect(penawaranPdfTestPageSizes($pdfContent))->not()->toBeEmpty();
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
