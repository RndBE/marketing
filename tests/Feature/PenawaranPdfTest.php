<?php

use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\PenawaranAttachment;
use App\Models\User;
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
