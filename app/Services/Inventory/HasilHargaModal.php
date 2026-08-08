<?php

namespace App\Services\Inventory;

use Carbon\CarbonInterface;

/**
 * Hasil satu kali tanya harga modal ke inventory, untuk satu tab.
 *
 * Bentuknya sengaja sudah siap pakai oleh Blade: view tidak perlu tahu apa pun
 * soal HTTP, status code, atau nama bidang milik inventory.
 */
final class HasilHargaModal
{
    /** Gagal karena hak akses pengguna, bukan karena sistem. */
    public const JENIS_AKSES = 'akses';

    /** Emailnya sah, tapi belum dikenal di sisi inventory. */
    public const JENIS_TIDAK_TERDAFTAR = 'tidak-terdaftar';

    /** Gangguan teknis: kunci ditolak, layanan mati, jaringan putus. */
    public const JENIS_TEKNIS = 'teknis';

    /**
     * @param  array<int, array<string, mixed>>  $baris
     * @param  array<string, mixed>  $ringkas  angka tingkat atas; hanya terisi untuk rincian
     */
    private function __construct(
        public readonly bool $berhasil,
        public readonly BentukHargaModal $bentuk,
        public readonly ?int $status,
        public readonly ?string $pesan,
        public readonly ?string $jenisPesan,
        public readonly array $baris,
        public readonly array $ringkas,
        public readonly ?CarbonInterface $diambilPada,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $baris
     * @param  array<string, mixed>  $ringkas
     */
    public static function sukses(BentukHargaModal $bentuk, array $baris, array $ringkas, ?CarbonInterface $diambilPada): self
    {
        return new self(true, $bentuk, 200, null, null, $baris, $ringkas, $diambilPada);
    }

    public static function gagal(BentukHargaModal $bentuk, ?int $status, string $pesan, string $jenisPesan): self
    {
        return new self(false, $bentuk, $status, $pesan, $jenisPesan, [], [], null);
    }

    public function kosong(): bool
    {
        return $this->baris === [];
    }

    /**
     * Cacah bahan berstok yang selisih kedua harganya lebar, dihitung atas
     * seluruh baris tab ini -- bukan atas halaman yang sedang tampil. Kalau
     * dihitung per halaman, angkanya berubah-ubah tiap kali orang berpindah
     * halaman dan kehilangan artinya.
     *
     * @return array{berstok: int, menyimpang: int}
     */
    public function ringkasSimpangan(): array
    {
        $berstok = array_filter($this->baris, fn (array $baris) => (bool) ($baris['berstok'] ?? false));

        return [
            'berstok' => count($berstok),
            'menyimpang' => count(array_filter($berstok, fn (array $baris) => (bool) ($baris['menyimpang'] ?? false))),
        ];
    }

    /**
     * Barisnya ada, tapi tidak satu pun membawa angka harga modal.
     *
     * Bedanya penting: tabel yang penuh "-" bisa berarti bidangnya belum
     * tercocokkan di CRM, bisa juga berarti inventory memang tidak mengirimnya.
     * Halaman perlu mengatakan itu, bukan membiarkan orang menebak.
     */
    public function hargaModalTidakAda(): bool
    {
        if ($this->baris === []) {
            return false;
        }

        foreach ($this->baris as $baris) {
            // Ketiganya diperiksa, bukan harga_modal saja: tab Bahan bisa saja hanya
            // membawa rata-rata atau nilai persediaannya.
            foreach (['harga_modal', 'harga_rata2', 'nilai_persediaan'] as $bidang) {
                if (is_numeric($baris[$bidang] ?? null)) {
                    return false;
                }
            }
        }

        return true;
    }
}
