<?php

namespace App\Mcp\Tools;

use App\Models\Company;
use App\Services\PenyusunPenawaran;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Buat penawaran baru lengkap dengan itemnya dalam satu langkah. Hanya panggil setelah pengguna menyetujui draft. Penawaran langsung masuk alur approval; hasilnya nomor dokumen, total, dan link.')]
class BuatPenawaran extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        $diskon = fn () => $schema->object([
            'tipe' => $schema->string()->enum(['percent', 'fixed'])->required(),
            'nilai' => $schema->number()->min(0)->description('Persen untuk percent, rupiah untuk fixed.')->required(),
        ]);

        return [
            'judul' => $schema->string()->description('Judul penawaran, mis. "Pengadaan Perangkat AWLR Sumur Pantau".')->required(),
            'id_pic' => $schema->integer()->description('Dari `cari-pic`. Kosongkan kalau belum ada.'),
            'instansi_tujuan' => $schema->string(),
            'nama_pekerjaan' => $schema->string(),
            'lokasi_pekerjaan' => $schema->string(),
            'tanggal_penawaran' => $schema->string()->format('date')->description('YYYY-MM-DD.'),
            'catatan' => $schema->string(),
            'perusahaan' => $schema->string()->description('Kode perusahaan penerbit (AS/ATC). Hanya admin yang boleh memilih; bawaan: perusahaan pengguna.'),
            'diskon' => $diskon()->description('Diskon keseluruhan penawaran.'),
            'pajak_persen' => $schema->number()->min(0)->max(100)->description('Mis. 11 untuk PPN 11%. Kosongkan kalau harga belum termasuk pajak.'),
            'items' => $schema->array()->min(1)->items($schema->object([
                'bundle_id' => $schema->integer()->description('Dari `cari-bundle`. Kosongkan untuk item custom.'),
                'judul' => $schema->string()->description('Wajib untuk item custom; bawaan untuk bundle: nama bundle.'),
                'catatan' => $schema->string(),
                'qty' => $schema->number()->min(0.01)->default(1),
                'satuan' => $schema->string(),
                'markup' => $schema->number()->min(0.01)->max(99)->description('Pengali harga item, 1 = tanpa markup.'),
                'diskon' => $diskon(),
                'rincian' => $schema->array()->min(1)->description('Wajib untuk item custom. Untuk bundle, isi hanya kalau rincian bawaan perlu diganti.')->items($schema->object([
                    'komponen_id' => $schema->integer()->description('Dari `cari-komponen`; nama, spesifikasi, satuan, dan harga yang kosong diambil dari katalog.'),
                    'nama' => $schema->string(),
                    'spesifikasi' => $schema->string(),
                    'qty' => $schema->number()->min(0.01)->required(),
                    'satuan' => $schema->string(),
                    'harga' => $schema->integer()->min(0)->description('Harga satuan rupiah.'),
                ])),
            ]))->required(),
        ];
    }

    public function handle(Request $request, PenyusunPenawaran $penyusun): Response|ResponseFactory
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'id_pic' => ['nullable', 'integer', 'exists:pics,id'],
            'instansi_tujuan' => ['nullable', 'string', 'max:255'],
            'nama_pekerjaan' => ['nullable', 'string', 'max:255'],
            'lokasi_pekerjaan' => ['nullable', 'string', 'max:255'],
            'tanggal_penawaran' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
            'perusahaan' => ['nullable', 'string', 'exists:companies,code'],
            'diskon' => ['nullable', 'array'],
            'diskon.tipe' => ['required_with:diskon', 'in:percent,fixed'],
            'diskon.nilai' => ['required_with:diskon', 'numeric', 'min:0'],
            'pajak_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.bundle_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.judul' => ['required_without:items.*.bundle_id', 'nullable', 'string', 'max:255'],
            'items.*.catatan' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.satuan' => ['nullable', 'string', 'max:50'],
            'items.*.markup' => ['nullable', 'numeric', 'min:0.01', 'max:99'],
            'items.*.diskon' => ['nullable', 'array'],
            'items.*.diskon.tipe' => ['required_with:items.*.diskon', 'in:percent,fixed'],
            'items.*.diskon.nilai' => ['required_with:items.*.diskon', 'numeric', 'min:0'],
            'items.*.rincian' => ['required_without:items.*.bundle_id', 'nullable', 'array', 'min:1', 'max:100'],
            'items.*.rincian.*.komponen_id' => ['nullable', 'integer', 'exists:komponen,id'],
            'items.*.rincian.*.nama' => ['required_without:items.*.rincian.*.komponen_id', 'nullable', 'string', 'max:255'],
            'items.*.rincian.*.spesifikasi' => ['nullable', 'string'],
            'items.*.rincian.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.rincian.*.satuan' => ['nullable', 'string', 'max:50'],
            'items.*.rincian.*.harga' => ['required_without:items.*.rincian.*.komponen_id', 'nullable', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $companyId = (int) $user->company_id;

        if (! empty($data['perusahaan'])) {
            $company = Company::where('code', $data['perusahaan'])->first();

            if ($company->id !== $companyId && ! $user->hasRole('admin')) {
                return Response::error('Hanya admin yang boleh membuat penawaran untuk perusahaan lain.');
            }

            $companyId = $company->id;
        }

        if ($companyId <= 0) {
            return Response::error('Perusahaan penerbit belum jelas. Sebutkan kodenya (AS/ATC).');
        }

        try {
            $penawaran = $penyusun->buat($user, $companyId, $data);
        } catch (\DomainException $e) {
            return Response::error($e->getMessage());
        }

        $penawaran->load(['docNumber', 'company', 'items.details']);

        return Response::structured([
            'id' => $penawaran->id,
            'no_dokumen' => $penawaran->docNumber?->doc_no,
            'perusahaan' => $penawaran->company?->code,
            'jumlah_item' => $penawaran->items->count(),
            'subtotal_item' => $penawaran->calcItemsSubtotal(),
            'diskon' => $penawaran->calcDiscountAmount(),
            'dpp' => $penawaran->calcDppTotal(),
            'pajak' => $penawaran->calcTaxAmount(),
            'total' => $penawaran->calcGrandTotal(),
            'status' => 'Menunggu approval',
            'url' => route('penawaran.show', $penawaran),
            'url_pdf' => route('penawaran.pdf', $penawaran->pdfRouteKey()),
        ]);
    }
}
