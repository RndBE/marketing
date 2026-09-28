<?php

namespace App\Services;

use App\Models\AlurPenawaran;
use App\Models\Approval;
use App\Models\ApprovalStep;
use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Komponen;
use App\Models\Penawaran;
use App\Models\PenawaranCover;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
use App\Models\PenawaranSignature;
use App\Models\PenawaranTerm;
use App\Models\PenawaranTermTemplate;
use App\Models\PenawaranValidity;
use App\Models\Pic;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya jalan penawaran dibuat dan item bundle ditambahkan.
 *
 * Dipakai form web dan MCP penawaran sekaligus, supaya penawaran dari mana pun
 * lahir dengan kelengkapan yang sama persis: nomor dokumen, cover, masa berlaku,
 * alur approval, syarat bawaan, dan tanda tangan.
 */
final class PenyusunPenawaran
{
    /**
     * Semua kunci opsional. Tanpa `diskon`, `pajak_persen`, dan `items` hasilnya
     * sama dengan penawaran kosong yang dibuat dari form web.
     *
     * @param  array<string, mixed>  $data
     */
    public function buat(User $user, int $companyId, array $data): Penawaran
    {
        return DB::transaction(function () use ($user, $companyId, $data) {
            $company = Company::find($companyId);

            if (! empty($data['id_pic'])) {
                Pic::findOrFail($data['id_pic']);
            }

            $docNumber = $this->nomorDokumen($companyId, $user->id);

            $penawaran = Penawaran::create([
                'company_id' => $companyId,
                'id_pic' => $data['id_pic'] ?? null,
                'id_user' => $user->id,
                'doc_number_id' => $docNumber->id,
                'approval_id' => null,
                'date_created' => now()->timestamp,
                'date_updated' => now()->timestamp,
                'judul' => $data['judul'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'instansi_tujuan' => $data['instansi_tujuan'] ?? null,
                'nama_pekerjaan' => $data['nama_pekerjaan'] ?? null,
                'lokasi_pekerjaan' => $data['lokasi_pekerjaan'] ?? null,
                'tanggal_penawaran' => $data['tanggal_penawaran'] ?? null,
                'discount_enabled' => ! empty($data['diskon']),
                'discount_type' => $data['diskon']['tipe'] ?? null,
                'discount_value' => $data['diskon']['nilai'] ?? null,
                'tax_enabled' => isset($data['pajak_persen']),
                'tax_rate' => $data['pajak_persen'] ?? null,
            ]);

            PenawaranCover::create([
                'penawaran_id' => $penawaran->id,
                'judul_cover' => 'Dokumen Penawaran',
                'subjudul' => $penawaran->judul,
                'perusahaan_nama' => $company?->name ?? 'CV. ARTA SOLUSINDO',
                'perusahaan_alamat' => $company?->address,
                'perusahaan_email' => $company?->email,
                'perusahaan_telp' => $company?->phone,
                'logo_path' => $company?->logo_path,
            ]);

            PenawaranValidity::create([
                'penawaran_id' => $penawaran->id,
                'mulai' => now()->toDateString(),
                'sampai' => now()->addDays(30)->toDateString(),
                'berlaku_hari' => 30,
                'keterangan' => 'Penawaran berlaku 30 hari.',
            ]);

            $alur = AlurPenawaran::where('berlaku_untuk', 'penawaran')
                ->where('company_id', $companyId)
                ->where('status', 'aktif')
                ->with(['langkah' => fn ($q) => $q->orderBy('no_langkah')])
                ->first();

            if (! $alur || $alur->langkah->isEmpty()) {
                // DomainException, bukan Exception biasa: MCP meneruskan pesan
                // ini apa adanya ke pengguna, sedangkan galat database tidak.
                throw new \DomainException('Alur penawaran aktif belum dibuat');
            }

            $firstStep = $alur->langkah->first()->no_langkah;

            $approval = Approval::create([
                'status' => 'menunggu',
                'current_step' => $firstStep,
                'module' => 'penawaran',
                'ref_id' => $penawaran->id,
            ]);

            foreach ($alur->langkah as $step) {
                ApprovalStep::create([
                    'approval_id' => $approval->id,
                    'step_order' => $step->no_langkah,
                    'step_name' => $step->nama_langkah,
                    'user_id' => $step->user_id,
                    'harus_semua' => $step->harus_semua,
                    'status' => 'menunggu',
                    'akses_approve' => [
                        'user_id' => (int) ($step->user_id ?: $penawaran->id_user),
                        'ref_penawaran' => (int) $penawaran->id,
                    ],
                ]);
            }

            $penawaran->update([
                'approval_id' => $approval->id,
                'status' => 'menunggu_approval',
            ]);

            $templates = PenawaranTermTemplate::query()
                ->whereNull('parent_id')
                ->orderBy('urutan')
                ->orderBy('id')
                ->with(['children'])
                ->get();

            foreach ($templates as $t) {
                $this->cloneTemplateTerm($penawaran->id, $t, null);
            }

            $roleNames = $user->roles->pluck('name')->implode(', ');

            PenawaranSignature::create([
                'penawaran_id' => $penawaran->id,
                'urutan' => 1,
                'nama' => $user->name,
                'jabatan' => $roleNames ?: 'Staff',
                'kota' => 'Sleman',
                'tanggal' => now()->toDateString(),
                'ttd_path' => $user->ttd,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $this->tambahItem($penawaran, $item);
            }

            return $penawaran;
        });
    }

    /**
     * Tambah satu item di urutan terakhir.
     *
     * Dengan `bundle_id` item bertipe bundle dan rinciannya disalin dari price list,
     * kecuali `rincian` diisi sendiri. Tanpa `bundle_id` item bertipe custom dan
     * `rincian` wajib. Rincian boleh menyebut `komponen_id` saja; nama, spesifikasi,
     * satuan, dan harga yang kosong diambil dari katalog komponen.
     *
     * @param  array<string, mixed>  $data
     */
    public function tambahItem(Penawaran $penawaran, array $data): PenawaranItem
    {
        $product = ! empty($data['bundle_id'])
            ? Product::with('details')->findOrFail($data['bundle_id'])
            : null;

        $item = PenawaranItem::create([
            'penawaran_id' => $penawaran->id,
            'product_id' => $product?->id,
            'tipe' => $product ? 'bundle' : 'custom',
            'urutan' => (int) PenawaranItem::where('penawaran_id', $penawaran->id)->max('urutan') + 1,
            'judul' => ($data['judul'] ?? null) ?: ($product->nama ?? 'Bundle'),
            'catatan' => $data['catatan'] ?? null,
            'qty' => (float) (($data['qty'] ?? null) ?: 1),
            'satuan' => $data['satuan'] ?? ($product ? $product->satuan : 'ls'),
            'subtotal' => 0,
            'markup' => (float) (($data['markup'] ?? null) ?: 1),
            'discount_enabled' => ! empty($data['diskon']),
            'discount_type' => $data['diskon']['tipe'] ?? null,
            'discount_value' => $data['diskon']['nilai'] ?? null,
        ]);

        $rincian = $data['rincian'] ?? $product?->details->map(fn ($pd) => [
            'nama' => $pd->nama,
            'spesifikasi' => $pd->spesifikasi,
            'qty' => $pd->qty,
            'satuan' => $pd->satuan,
            'harga' => $pd->harga,
        ])->all() ?? [];

        foreach (array_values($rincian) as $i => $detail) {
            $detail = array_filter($detail, fn ($value) => $value !== null);

            if (! empty($detail['komponen_id'])) {
                $komponen = Komponen::findOrFail($detail['komponen_id']);
                $detail += $komponen->only(['nama', 'spesifikasi', 'satuan', 'harga']);
            }

            $qty = (float) ($detail['qty'] ?? 1);
            $harga = (int) ($detail['harga'] ?? 0);

            PenawaranItemDetail::create([
                'penawaran_item_id' => $item->id,
                'urutan' => $i + 1,
                'nama' => $detail['nama'] ?? null,
                'spesifikasi' => $detail['spesifikasi'] ?? null,
                'qty' => $qty,
                'satuan' => $detail['satuan'] ?? null,
                'harga' => $harga,
                'subtotal' => (int) round($qty * $harga),
            ]);
        }

        $item->load('details');
        $item->subtotal = $item->calcSubtotal();
        $item->save();

        return $item;
    }

    /**
     * Nomor berikutnya dalam urutan perusahaan: 001/SPH05/AS/VI/2026.
     */
    public function nomorDokumen(int $companyId, int $userId): DocNumber
    {
        return DB::transaction(function () use ($companyId, $userId) {
            $now = Carbon::now();
            $company = Company::find($companyId);
            $companyCode = strtoupper((string) ($company?->code ?: 'COMP'));

            $romawi = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

            // Dikunci supaya dua penawaran yang dibuat bersamaan -- dari web dan MCP,
            // misalnya -- tidak berebut nomor yang sama lalu salah satunya gagal.
            $last = DocNumber::where('company_id', $companyId)
                ->orderByDesc('seq')
                ->lockForUpdate()
                ->first();
            $seq = $last ? $last->seq + 1 : 1;

            $userCode = 'SPH'.str_pad((string) $userId, 2, '0', STR_PAD_LEFT);

            $docNo = str_pad($seq, 3, '0', STR_PAD_LEFT)
                ."/{$userCode}/{$companyCode}/{$romawi[$now->month]}/{$now->year}";

            return DocNumber::create([
                'company_id' => $companyId,
                'prefix' => $userCode,
                'seq' => $seq,
                'month' => $now->month,
                'year' => $now->year,
                'doc_no' => $docNo,
            ]);
        });
    }

    private function cloneTemplateTerm(int $penawaranId, $template, ?int $parentId): void
    {
        $new = PenawaranTerm::create([
            'penawaran_id' => $penawaranId,
            'parent_id' => $parentId,
            'urutan' => (int) ($template->urutan ?? 1),
            'judul' => $template->judul,
            'isi' => $template->isi,
        ]);

        foreach ($template->children ?? collect() as $c) {
            $this->cloneTemplateTerm($penawaranId, $c, $new->id);
        }
    }
}
