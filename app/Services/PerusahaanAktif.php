<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;

/**
 * Perusahaan yang sedang berlaku bagi seorang pengguna.
 *
 * Admin bisa berpindah perusahaan lewat pemilih di header, jadi yang berlaku
 * baginya adalah pilihan di sesi. Pengguna lain selalu terikat pada perusahaannya
 * sendiri dan tidak punya pemilih itu.
 *
 * Dipisah dari view composer supaya pemeriksaan hak di luar tampilan -- middleware,
 * misalnya -- memakai jawaban yang sama persis, bukan salinan aturannya.
 */
final class PerusahaanAktif
{
    public static function untuk(?User $user): ?Company
    {
        if ($user === null) {
            return null;
        }

        if (! $user->hasRole('admin')) {
            return $user->company;
        }

        $dipilih = (int) session('active_company_id', 0);

        return ($dipilih > 0 ? Company::find($dipilih) : null)
            ?? ($user->company_id ? Company::find((int) $user->company_id) : null)
            ?? Company::query()->orderBy('name')->first();
    }
}
