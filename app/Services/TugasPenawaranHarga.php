<?php

namespace App\Services;

use App\Models\UsulanPenawaran;
use Illuminate\Database\Eloquent\Builder;

/**
 * Berapa berkas Penawaran Harga yang bolanya ada di tangan perusahaan ini.
 *
 * Dipakai untuk penanda merah di sidebar, jadi isinya khusus yang bisa
 * ditindaklanjuti sendiri -- bukan yang sedang ditunggu jawabannya dari lawan.
 * Dengan begitu penandanya padam begitu pekerjaannya dikerjakan.
 */
class TugasPenawaranHarga
{
    public static function jumlah(?int $companyId): int
    {
        if (! $companyId) {
            return 0;
        }

        return UsulanPenawaran::query()
            // Usulan internal tanpa perusahaan tujuan bukan urusan modul ini.
            ->whereNotNull('target_company_id')
            ->where(function (Builder $query) use ($companyId) {
                $query
                    // Kita penjual: permintaan masuk yang belum ditanggapi.
                    ->where(fn (Builder $q) => $q
                        ->where('target_company_id', $companyId)
                        ->where('status', 'menunggu'))
                    // Kita pembeli: penawaran sudah dikirim, keputusannya di kita.
                    ->orWhere(fn (Builder $q) => $q
                        ->where('company_id', $companyId)
                        ->where('penawaran_status', 'sent'))
                    // Kita penjual: penawarannya masih draft atau diminta direvisi,
                    // dua-duanya menunggu kita rapikan lalu kirim.
                    ->orWhere(fn (Builder $q) => $q
                        ->where('target_company_id', $companyId)
                        ->whereIn('penawaran_status', ['draft', 'revision_requested']));
            })
            ->count();
    }
}
