<?php

namespace App\Services\Inventory;

/**
 * Perhitungan harga jual dari harga modal, memakai margin terhadap harga jual.
 *
 * ACUAN TUNGGAL rumusnya. Versi JavaScript di resources/js/app.js hanya menyalin
 * apa yang ada di sini supaya perhitungannya langsung terasa saat angka diubah;
 * kalau rumusnya berubah, keduanya harus ikut berubah.
 *
 * Bedanya dengan markup penting dan mudah tertukar:
 *
 *   margin 30%  -> jual = modal / (1 - 0,30)  = modal x 1,4286
 *   markup 30%  -> jual = modal x 1,30
 *
 * Modal Rp 12.530.171,82 dengan margin 30% menghasilkan Rp 17.900.245.
 * Dikalikan 1,3 hasilnya Rp 16.289.223, dan marginnya cuma 23% -- tujuh poin di
 * bawah yang dikira. Semuanya dihitung di CRM; inventory tidak menyimpan harga
 * jual sama sekali dan tidak pernah ditulisi.
 */
final class MarginHargaJual
{
    /** Dipakai saat pengguna belum pernah menyetel marginnya sendiri. */
    public const MARGIN_BAWAAN = 30.0;

    /**
     * Harga jual dibulatkan ke atas ke kelipatan ini.
     *
     * Harus ke atas, bukan ke terdekat: membulatkan ke bawah menurunkan margin ke
     * bawah target tanpa ada yang menyadarinya.
     */
    public const KELIPATAN = 1000;

    /** Margin 100% berarti pembagian dengan nol; di atasnya harga jual jadi negatif. */
    private const MARGIN_MAKSIMUM = 99.9;

    public static function hargaJual(?float $modal, ?float $marginPersen): ?float
    {
        if ($modal === null || $modal <= 0 || $marginPersen === null) {
            return null;
        }

        if ($marginPersen < 0 || $marginPersen > self::MARGIN_MAKSIMUM) {
            return null;
        }

        return self::bulatkanKeAtas($modal / (1 - $marginPersen / 100));
    }

    /** Margin yang benar-benar didapat dari sepasang modal dan harga jual. */
    public static function margin(?float $modal, ?float $jual): ?float
    {
        if ($modal === null || $jual === null || $jual <= 0) {
            return null;
        }

        return ($jual - $modal) / $jual * 100;
    }

    public static function bulatkanKeAtas(float $nilai): float
    {
        return ceil($nilai / self::KELIPATAN) * self::KELIPATAN;
    }
}
