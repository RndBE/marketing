<?php

namespace App\Services\Inventory;

use Illuminate\Http\Request;

/**
 * Pencarian dan saringan untuk tabel Harga Modal.
 *
 * Dijalankan di server atas seluruh baris tab, lalu hasilnya baru dipenggal jadi
 * halaman. Urutannya penting: kalau penyaringan dilakukan di browser sesudah
 * pemenggalan, kotak pencarian hanya akan menjangkau baris yang sedang tampil dan
 * diam-diam melewatkan sisanya.
 */
final class SaringanHargaModal
{
    public const PER_HALAMAN = 50;

    private function __construct(
        public readonly string $cari,
        public readonly string $sumber,
        public readonly bool $hanyaMenyimpang,
    ) {}

    public static function dari(Request $request): self
    {
        return new self(
            trim((string) $request->query('cari', '')),
            trim((string) $request->query('sumber', '')),
            $request->boolean('menyimpang'),
        );
    }

    public function aktif(): bool
    {
        return $this->cari !== '' || $this->sumber !== '' || $this->hanyaMenyimpang;
    }

    /**
     * Parameter saringan yang perlu ikut menempel pada tautan halaman dan tab.
     *
     * @return array<string, string|int>
     */
    public function keKueri(): array
    {
        return array_filter([
            'cari' => $this->cari,
            'sumber' => $this->sumber,
            'menyimpang' => $this->hanyaMenyimpang ? 1 : '',
        ], fn ($nilai) => $nilai !== '' && $nilai !== null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $baris
     * @return array<int, array<string, mixed>>
     */
    public function saring(array $baris, TabHargaModal $tab): array
    {
        if (! $this->aktif()) {
            return $baris;
        }

        $kueri = mb_strtolower($this->cari);

        return array_values(array_filter($baris, function (array $item) use ($kueri, $tab): bool {
            if ($this->sumber !== '' && (string) ($item['sumber'] ?? '') !== $this->sumber) {
                return false;
            }

            // Saringan simpangan hanya bermakna di tab Bahan; tab lain tidak
            // punya rata-rata tertimbang untuk dibandingkan.
            if ($this->hanyaMenyimpang && ! (bool) ($item['menyimpang'] ?? false)) {
                return false;
            }

            return $kueri === '' || str_contains($this->teksCari($item, $tab), $kueri);
        }));
    }

    /** @param  array<string, mixed>  $item */
    private function teksCari(array $item, TabHargaModal $tab): string
    {
        $bagian = $tab->berbasisUnit()
            ? [$item['nama_produk'] ?? null, $item['kode_produksi'] ?? null, $item['serial'] ?? null, $item['sumber'] ?? null]
            : [$item['nama_produk'] ?? null, $item['sumber'] ?? null];

        return mb_strtolower(implode(' ', array_filter($bagian, fn ($nilai) => $nilai !== null && $nilai !== '')));
    }
}
