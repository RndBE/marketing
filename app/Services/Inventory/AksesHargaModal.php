<?php

namespace App\Services\Inventory;

use App\Models\User;
use App\Services\PerusahaanAktif;

/**
 * Siapa yang boleh membuka halaman Harga Modal.
 *
 * Dua syarat, keduanya harus terpenuhi:
 *
 *   1. punya izin `view-harga-modal` -- diberikan per role lewat Kelola Roles
 *   2. perusahaannya termasuk yang diizinkan melihat harga modal
 *
 * Syarat kedua ada karena harga modal hanya berlaku untuk sebagian perusahaan.
 * Izin per role tidak bisa menyatakannya: satu role yang sama dipakai orang di
 * perusahaan berbeda, jadi mencentang izinnya akan membuka halaman itu untuk
 * semuanya sekaligus.
 *
 * Jawaban kelas ini dipakai bersama oleh middleware rute dan menu sidebar, supaya
 * menu tidak pernah menawarkan halaman yang ujungnya ditolak.
 *
 * Ini lapis pertama. Inventory tetap memeriksa hak per email dan bisa membalas
 * 403 sekalipun kedua syarat di sini lolos.
 */
final class AksesHargaModal
{
    public static function boleh(?User $user): bool
    {
        if ($user === null || ! $user->hasPermission('view-harga-modal')) {
            return false;
        }

        $diizinkan = self::perusahaanDiizinkan();

        // Daftar kosong berarti pembatasan perusahaan dimatikan; izin saja yang berlaku.
        if ($diizinkan === []) {
            return true;
        }

        $kode = PerusahaanAktif::untuk($user)?->code;

        return $kode !== null && in_array(mb_strtoupper(trim($kode)), $diizinkan, true);
    }

    /**
     * Kode perusahaan yang boleh melihat harga modal, sudah diseragamkan huruf besar.
     *
     * @return array<int, string>
     */
    public static function perusahaanDiizinkan(): array
    {
        $kode = array_map(
            fn ($nilai) => mb_strtoupper(trim((string) $nilai)),
            (array) config('services.inventory.perusahaan', []),
        );

        return array_values(array_filter($kode, fn (string $nilai) => $nilai !== ''));
    }
}
