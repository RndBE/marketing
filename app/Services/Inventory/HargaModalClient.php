<?php

namespace App\Services\Inventory;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya jalan CRM menanyakan harga modal ke inventory.
 *
 * Dipanggil dari sisi server. X-API-KEY tidak pernah ikut ke browser, dan badan
 * jawabannya -- yang isinya HPP -- tidak pernah masuk log.
 */
class HargaModalClient
{
    private const JALUR = '/api/crm/harga-modal';

    private const JALUR_RINCIAN = '/api/crm/harga-modal/rincian';

    private const PREFIKS_CACHE = 'inventory:harga-modal:';

    /** Pagar keras: sesingkat apa pun kebutuhannya, cache tidak boleh jadi salinan HPP yang awet. */
    private const CACHE_MAKSIMUM_DETIK = 300;

    /**
     * Satu tab satu panggilan: yang ditarik hanya data tab yang sedang dibuka.
     *
     * $hanyaTersedia hanya dikirim kalau benar-benar diminta. Sebagai bawaan,
     * filter itu justru mengosongkan tab Produk Jadi -- stoknya sedang nol untuk
     * seluruh unit, padahal harga modalnya tetap perlu dilihat.
     */
    public function untukEmail(string $email, TabHargaModal $tab, bool $hanyaTersedia = false): HasilHargaModal
    {
        $parameter = ['email' => $email, 'tab' => $tab->value];

        // Dikirim hanya kalau memang diminta. Ikut mengirimnya sebagai bawaan
        // membuat tab yang stoknya sedang nol tampak kosong sama sekali.
        if ($hanyaTersedia) {
            $parameter['hanya_tersedia'] = 1;
        }

        return $this->ambil(
            self::JALUR,
            $parameter,
            $tab->berbasisUnit() ? BentukHargaModal::Unit : BentukHargaModal::Bahan,
            $tab->kunciBaris(),
            [mb_strtolower(trim($email)), $tab->value, $hanyaTersedia ? 'tersedia' : 'semua'],
        );
    }

    /**
     * Bahan yang dipakai satu batch produksi, untuk tombol "Lihat Bahan".
     *
     * Jawabannya punya bentuknya sendiri -- kuantitas pakai, harga satuan saat
     * produksi, subtotal -- bukan bentuk tab Bahan. `tipe` yang dikirim memakai
     * kosakata yang sama dengan parameter `tab`.
     */
    public function rincianBahan(string $email, TabHargaModal $tipe, string $produksiId): HasilHargaModal
    {
        return $this->ambil(
            self::JALUR_RINCIAN,
            ['email' => $email, 'tipe' => $tipe->value, 'produksi_id' => $produksiId],
            // Peta bidang mengikuti bentuk jawabannya, bukan tipe yang diminta.
            BentukHargaModal::Rincian,
            'rincian',
            [mb_strtolower(trim($email)), 'rincian', $tipe->value, $produksiId],
        );
    }

    /**
     * Alur bersama kedua endpoint: periksa konfigurasi, coba cache, panggil,
     * petakan, dan terjemahkan kegagalannya.
     *
     * @param  array<string, mixed>  $parameter
     * @param  array<int, string>  $bahanKunci  penyusun kunci cache
     */
    private function ambil(
        string $jalur,
        array $parameter,
        BentukHargaModal $bentuk,
        string $kunciBaris,
        array $bahanKunci,
    ): HasilHargaModal {
        if (($belumSiap = $this->periksaKonfigurasi($bentuk)) !== null) {
            return $belumSiap;
        }

        $ttl = $this->ttlCache();
        $kunci = self::PREFIKS_CACHE.sha1(implode('|', $bahanKunci));

        if ($ttl > 0) {
            $tersimpan = $this->penyimpanan()->get($kunci);

            if (is_array($tersimpan)) {
                return HargaModalPayload::dari($tersimpan, $bentuk, $kunciBaris)->jadiHasil();
            }
        }

        [$status, $badan, $galat] = $this->panggil($jalur, $parameter);

        if ($status === 200 && is_array($badan)) {
            if ($ttl > 0) {
                $this->penyimpanan()->put($kunci, $badan, $ttl);
            }

            return HargaModalPayload::dari($badan, $bentuk, $kunciBaris)->jadiHasil();
        }

        return $this->terjemahkanKegagalan($bentuk, $status, $galat);
    }

    /**
     * Jawaban mentah dari inventory, tanpa lewat pemetaan bidang.
     *
     * Hanya untuk perkakas diagnosa di baris perintah. Halaman web tidak memakai
     * ini -- yang dipakai selalu untukEmail(), yang sudah dinormalkan.
     *
     * @return array{0: ?int, 1: ?array<string, mixed>, 2: ?string}
     */
    public function mentah(string $email, TabHargaModal $tab, bool $hanyaTersedia = false): array
    {
        $bentuk = $tab->berbasisUnit() ? BentukHargaModal::Unit : BentukHargaModal::Bahan;

        if (($belumSiap = $this->periksaKonfigurasi($bentuk)) !== null) {
            return [null, null, $belumSiap->pesan];
        }

        $parameter = ['email' => $email, 'tab' => $tab->value];

        if ($hanyaTersedia) {
            $parameter['hanya_tersedia'] = 1;
        }

        return $this->panggil(self::JALUR, $parameter);
    }

    /**
     * Jawaban mentah endpoint rincian, juga hanya untuk perkakas diagnosa.
     *
     * @return array{0: ?int, 1: ?array<string, mixed>, 2: ?string}
     */
    public function mentahRincian(string $email, TabHargaModal $tipe, string $produksiId): array
    {
        if (($belumSiap = $this->periksaKonfigurasi(BentukHargaModal::Rincian)) !== null) {
            return [null, null, $belumSiap->pesan];
        }

        return $this->panggil(self::JALUR_RINCIAN, [
            'email' => $email,
            'tipe' => $tipe->value,
            'produksi_id' => $produksiId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $parameter
     * @return array{0: ?int, 1: ?array<string, mixed>, 2: ?string}
     */
    private function panggil(string $jalur, array $parameter): array
    {
        $alamat = rtrim((string) config('services.inventory.base_url'), '/').$jalur;
        $tenggat = $this->tenggat();
        $email = (string) ($parameter['email'] ?? '');

        try {
            $respons = Http::withHeaders([
                'X-API-KEY' => (string) config('services.inventory.api_key'),
                'Accept' => 'application/json',
            ])
                ->timeout($tenggat)
                ->connectTimeout(min(5, $tenggat))
                ->get($alamat, $parameter);

            $badan = $respons->json();

            return [$respons->status(), is_array($badan) ? $badan : null, null];
        } catch (ConnectionException $e) {
            // Pesan pengecualian saja: badan jawaban tidak pernah ikut ke log.
            Log::warning('Harga modal: inventory tidak terjangkau.', [
                'email' => $email,
                'pesan' => $e->getMessage(),
            ]);

            return [null, null, 'koneksi'];
        } catch (\Throwable $e) {
            Log::warning('Harga modal: panggilan ke inventory gagal.', [
                'email' => $email,
                'pesan' => $e->getMessage(),
            ]);

            return [null, null, 'tak-terduga'];
        }
    }

    /**
     * Tiap status punya arti yang berbeda bagi orang yang membaca layar, jadi
     * jangan diratakan jadi "error". 403 berarti haknya kurang, 404 berarti
     * datanya belum ada, 401 dan 503 berarti yang rusak bukan penggunanya.
     */
    private function terjemahkanKegagalan(BentukHargaModal $bentuk, ?int $status, ?string $galat): HasilHargaModal
    {
        if ($status === 403) {
            return HasilHargaModal::gagal($bentuk, 403, 'Anda tidak punya akses harga modal.', HasilHargaModal::JENIS_AKSES);
        }

        if ($status === 404) {
            return HasilHargaModal::gagal($bentuk, 404, 'Email Anda belum terdaftar di inventory.', HasilHargaModal::JENIS_TIDAK_TERDAFTAR);
        }

        if ($status === 401) {
            return HasilHargaModal::gagal(
                $bentuk,
                401,
                'Inventory menolak kunci API CRM (401). Ini masalah konfigurasi di sisi server, bukan akun Anda. Hubungi tim inventory untuk memeriksa CRM_API_KEY.',
                HasilHargaModal::JENIS_TEKNIS,
            );
        }

        if ($status === 503) {
            return HasilHargaModal::gagal(
                $bentuk,
                503,
                'Layanan inventory sedang tidak tersedia (503). Data harga modal belum bisa diambil, coba lagi beberapa saat lagi.',
                HasilHargaModal::JENIS_TEKNIS,
            );
        }

        if ($galat === 'koneksi') {
            return HasilHargaModal::gagal(
                $bentuk,
                null,
                'Inventory tidak menjawab dalam '.$this->tenggat().' detik. Jaringan atau server inventory sedang bermasalah.',
                HasilHargaModal::JENIS_TEKNIS,
            );
        }

        return HasilHargaModal::gagal(
            $bentuk,
            $status,
            $status === null
                ? 'Panggilan ke inventory gagal sebelum sempat dijawab. Periksa log aplikasi untuk detailnya.'
                : 'Inventory membalas dengan status '.$status.' yang tidak dikenali. Periksa log aplikasi untuk detailnya.',
            HasilHargaModal::JENIS_TEKNIS,
        );
    }

    private function periksaKonfigurasi(BentukHargaModal $bentuk): ?HasilHargaModal
    {
        $kosong = [];

        foreach (['base_url' => 'INVENTORY_BASE_URL', 'api_key' => 'CRM_API_KEY'] as $kunci => $env) {
            if (trim((string) config('services.inventory.'.$kunci)) === '') {
                $kosong[] = $env;
            }
        }

        if ($kosong === []) {
            return null;
        }

        return HasilHargaModal::gagal(
            $bentuk,
            null,
            'Integrasi inventory belum dikonfigurasi: '.implode(' dan ', $kosong).' masih kosong di server CRM.',
            HasilHargaModal::JENIS_TEKNIS,
        );
    }

    private function tenggat(): int
    {
        return max(1, (int) config('services.inventory.timeout', 10));
    }

    private function ttlCache(): int
    {
        $ttl = (int) config('services.inventory.harga_modal_cache_ttl', 0);

        return max(0, min($ttl, self::CACHE_MAKSIMUM_DETIK));
    }

    private function penyimpanan(): CacheRepository
    {
        $store = config('services.inventory.harga_modal_cache_store');

        return $store ? Cache::store($store) : Cache::store();
    }
}
