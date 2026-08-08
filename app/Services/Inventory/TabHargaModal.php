<?php

namespace App\Services\Inventory;

/**
 * Tab pada halaman Harga Modal, sekaligus nilai parameter `tab` yang dikirim ke
 * inventory.
 *
 * Satu tab satu panggilan. Sebelum ada parameter ini, sekali buka halaman
 * menarik 556 KB padahal yang dibaca cuma satu tab.
 */
enum TabHargaModal: string
{
    case ProdukJadi = 'produk-jadi';
    case SetengahJadi = 'setengah-jadi';
    case Bahan = 'bahan';

    /** Nilai dari query string; apa pun yang tidak dikenali jatuh ke tab pertama. */
    public static function dariPermintaan(mixed $nilai): self
    {
        return is_string($nilai) ? (self::tryFrom($nilai) ?? self::ProdukJadi) : self::ProdukJadi;
    }

    public function label(): string
    {
        return match ($this) {
            self::ProdukJadi => 'Produk Jadi',
            self::SetengahJadi => 'Produk Setengah Jadi',
            self::Bahan => 'Bahan',
        };
    }

    /** Kunci tempat inventory menaruh baris tab ini di dalam badan jawaban. */
    public function kunciBaris(): string
    {
        return match ($this) {
            self::ProdukJadi => 'produk_jadi',
            self::SetengahJadi => 'produk_setengah_jadi',
            self::Bahan => 'bahan',
        };
    }

    /**
     * Hanya tab berbasis unit yang baris kembarnya dilebur. Bahan sudah unik per
     * baris, jadi meleburnya justru berisiko menyatukan dua bahan berbeda yang
     * kebetulan senama.
     */
    public function berbasisUnit(): bool
    {
        return $this !== self::Bahan;
    }
}
