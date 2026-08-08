<?php

namespace App\Services\Inventory;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Menerjemahkan badan JSON dari inventory menjadi bentuk yang dipakai Blade,
 * sekaligus melebur baris kembar pada tab berbasis unit (lihat gabungkan()).
 *
 * SATU-SATUNYA tempat nama bidang milik inventory disebut. Kalau kontraknya
 * bergeser, yang berubah cuma peta di bawah -- controller, view, dan test tidak
 * ikut tersentuh.
 */
final class HargaModalPayload
{
    /**
     * Kolom untuk tab Produk Jadi dan Produk Setengah Jadi.
     *
     * Bidang harga dipatok tepat ke `harga_modal_satuan`, tanpa alias. Nama lain
     * seperti `harga_modal`, `hpp`, atau `unit_price` sengaja tidak diterima:
     * kalau suatu saat inventory mengirim salah satunya dengan arti berbeda,
     * halaman HPP lebih baik menampilkan "-" daripada angka yang keliru.
     */
    private const BIDANG_UNIT = [
        'nama_produk' => ['nama_produk', 'nama'],
        'kode_produksi' => ['kode_produksi'],
        'kode_unit' => ['kode_unit'],
        'serial' => ['serial_number'],
        'stok_sisa' => ['stok_sisa', 'sisa_stok', 'stok'],
        'harga_modal' => ['harga_modal_satuan'],
        'sumber' => ['sumber'],
        // Dipakai tombol "Lihat Bahan". Kosong berarti rinciannya memang tidak ada
        // di sistem, bukan berarti gagal dibaca.
        'produksi_id' => ['produksi_id'],
        // Gambar unit diambil browser langsung dari Google, bukan dari server
        // inventory -- beda dengan gambar di tab Bahan.
        'gambar_url' => ['gambar_url'],
        'link_gambar' => ['link_gambar'],
    ];

    /**
     * Kolom untuk tab Bahan. Tidak ada kode_produksi maupun serial_number di sini,
     * tapi ada tiga angka harga yang ketiganya ditampilkan: harga beli terakhir,
     * rata-rata tertimbang, dan total nilai persediaan.
     */
    private const BIDANG_BAHAN = [
        'nama_produk' => ['nama_bahan', 'nama_produk', 'nama'],
        'gambar_url' => ['gambar_url'],
        'stok_sisa' => ['stok_sisa', 'sisa_stok', 'stok'],
        'harga_modal' => ['harga_modal_satuan'],
        'harga_rata2' => ['harga_modal_rata2'],
        'nilai_persediaan' => ['nilai_persediaan'],
        'sumber' => ['sumber'],
    ];

    /**
     * Kolom untuk endpoint /rincian: pemakaian bahan pada satu batch produksi.
     *
     * Bentuknya lain sama sekali dari tab Bahan. Tidak ada stok, rata-rata
     * tertimbang, nilai persediaan, maupun sumber di sini -- yang ada kuantitas
     * pakai, harga satuan saat produksi, dan subtotalnya.
     */
    private const BIDANG_RINCIAN = [
        'nama' => ['nama'],
        'kode' => ['kode'],
        'jenis' => ['jenis'],
        'batch' => ['batch'],
        'qty' => ['qty'],
        'harga_satuan' => ['harga_satuan'],
        'sub_total' => ['sub_total'],
        'gambar_url' => ['gambar_url'],
    ];

    /**
     * Angka tingkat atas pada jawaban /rincian.
     *
     * Ketiganya ditampilkan berdampingan di kepala modal supaya hubungannya
     * terlihat: total biaya bahan dibagi jumlah produksi menghasilkan harga modal
     * per unit yang sama dengan yang tampil di tab.
     */
    private const BIDANG_RINGKAS_RINCIAN = [
        'jml_produksi' => ['jml_produksi'],
        'total_biaya_bahan' => ['total_biaya_bahan'],
        'harga_modal_satuan' => ['harga_modal_satuan'],
    ];

    /**
     * Ambang selisih harga terakhir terhadap rata-rata tertimbang yang dianggap
     * lebar. Di atas angka ini, memakai satu harga untuk mewakili keduanya sudah
     * bisa meleset jauh.
     */
    public const AMBANG_SIMPANGAN = 0.2;

    private const BIDANG_DIAMBIL_PADA = ['diambil_pada', 'fetched_at'];

    /** Baris bisa datang langsung sebagai array, atau dibungkus salah satu kunci ini. */
    private const PEMBUNGKUS_BARIS = ['baris', 'rincian', 'items', 'data'];

    /** Kunci yang dicari saat sebuah bidang ternyata berupa objek, bukan nilai tunggal. */
    private const KUNCI_DALAM = ['nama', 'name', 'kode', 'code', 'label', 'value', 'text'];

    /** Awalan alamat pratinjau Google Drive yang bisa disematkan di iframe. */
    private const PRATINJAU_DRIVE = 'https://drive.google.com/file/d/';

    /**
     * Kolom yang nilainya dikumpulkan saat baris kembar dilebur jadi satu.
     *
     * produksi_id ikut di sini karena unit dengan nama dan harga sama bisa berasal
     * dari batch produksi berbeda. Yang pertama dipakai tombol "Lihat Bahan", dan
     * cacah sisanya ditampilkan supaya tidak ada yang menyangka rinciannya
     * mencakup seluruh unit di baris itu.
     */
    private const KOLOM_DILEBUR = ['kode_produksi', 'serial', 'sumber', 'produksi_id'];

    /** @param  array<string, mixed>  $badan */
    private function __construct(
        private readonly array $badan,
        private readonly BentukHargaModal $bentuk,
        private readonly string $kunciBaris,
    ) {}

    /** @param  array<string, mixed>  $badan */
    public static function dari(array $badan, BentukHargaModal $bentuk, string $kunciBaris): self
    {
        // Inventory boleh saja membungkus muatannya di dalam `data`.
        if (! array_key_exists($kunciBaris, $badan) && isset($badan['data']) && is_array($badan['data'])) {
            $badan = $badan['data'];
        }

        return new self($badan, $bentuk, $kunciBaris);
    }

    /**
     * Melaporkan nama bidang mana yang tercocokkan untuk tiap kolom internal.
     * Dipakai perkakas diagnosa CLI saat sebuah kolom tampil "-" terus-menerus,
     * supaya ketahuan apakah nama bidangnya yang meleset atau inventory memang
     * tidak mengirim bidang itu.
     *
     * @param  array<string, mixed>  $sumber
     * @return array<string, ?string> nama internal => nama bidang yang cocok
     */
    public static function diagnosaBaris(array $sumber, BentukHargaModal $bentuk): array
    {
        return self::cocokkan($sumber, self::petaBaris($bentuk));
    }

    /** @return array<string, array<int, string>> */
    private static function petaBaris(BentukHargaModal $bentuk): array
    {
        return match ($bentuk) {
            BentukHargaModal::Unit => self::BIDANG_UNIT,
            BentukHargaModal::Bahan => self::BIDANG_BAHAN,
            BentukHargaModal::Rincian => self::BIDANG_RINCIAN,
        };
    }

    /**
     * @param  array<string, mixed>  $sumber
     * @param  array<string, array<int, string>>  $peta
     * @return array<string, ?string>
     */
    private static function cocokkan(array $sumber, array $peta): array
    {
        $hasil = [];

        foreach ($peta as $internal => $kandidat) {
            $hasil[$internal] = null;

            foreach ($kandidat as $nama) {
                if (array_key_exists($nama, $sumber) && $sumber[$nama] !== null && $sumber[$nama] !== '') {
                    $hasil[$internal] = $nama;
                    break;
                }
            }
        }

        return $hasil;
    }

    public function jadiHasil(): HasilHargaModal
    {
        $peta = self::petaBaris($this->bentuk);

        $baris = array_map(
            fn (array $item): array => $this->rapikan($this->cocokkanNilai($item, $peta)),
            array_values(array_filter($this->barisMentah(), 'is_array')),
        );

        // Hanya baris unit yang dilebur; bahan dan rincian sudah unik per baris.
        if ($this->bentuk === BentukHargaModal::Unit) {
            $baris = $this->gabungkan($baris);
        }

        return HasilHargaModal::sukses(
            $this->bentuk,
            $baris,
            $this->bentuk === BentukHargaModal::Rincian
                ? $this->cocokkanNilai($this->badan, self::BIDANG_RINGKAS_RINCIAN)
                : [],
            $this->diambilPada(),
        );
    }

    /**
     * @param  array<string, mixed>  $baris
     * @return array<string, mixed>
     */
    private function rapikan(array $baris): array
    {
        return match ($this->bentuk) {
            BentukHargaModal::Unit => $this->rapikanUnit($baris),
            BentukHargaModal::Bahan => $this->rapikanBahan($baris),
            BentukHargaModal::Rincian => $this->rapikanRincian($baris),
        };
    }

    /**
     * @param  array<string, mixed>  $baris
     * @return array<string, mixed>
     */
    private function rapikanRincian(array $baris): array
    {
        $baris['gambar_url'] = $this->tautanGambar($baris['gambar_url'] ?? null);

        return $baris;
    }

    /**
     * Kode produksi kosong bukan berarti unitnya tanpa identitas: data sebelum
     * alur QC memang belum punya kode produksi, tapi kode unitnya selalu terisi
     * dan sudah memuat kodenya. Lebih baik menampilkan itu daripada sel kosong.
     *
     * @param  array<string, mixed>  $baris
     * @return array<string, mixed>
     */
    private function rapikanUnit(array $baris): array
    {
        $baris['kode_dari_unit'] = ($baris['kode_produksi'] ?? null) === null
            && ($baris['kode_unit'] ?? null) !== null;

        $baris['kode_produksi'] ??= $baris['kode_unit'] ?? null;

        $baris['gambar_url'] = $this->tautanGambar($baris['gambar_url'] ?? null);
        $baris['link_gambar'] = $this->tautanLuar($baris['link_gambar'] ?? null);
        $baris['gambar_sematan'] = $this->sematanDrive($baris['link_gambar']);

        return $baris;
    }

    /**
     * Alamat pratinjau Drive untuk disematkan di iframe.
     *
     * Host diperiksa lebih dulu, bukan cuma polanya: URL mana pun bisa kebetulan
     * memuat "/d/", dan menyematkan host yang tidak mendukung penyematan hanya
     * menghasilkan bingkai kosong tanpa pesan apa pun. Yang bukan Drive
     * dikembalikan null, dan pratinjaunya memakai jalur lain.
     */
    private function sematanDrive(?string $tautan): ?string
    {
        if ($tautan === null || mb_strtolower((string) parse_url($tautan, PHP_URL_HOST)) !== 'drive.google.com') {
            return null;
        }

        // Bentuk yang lazim: /file/d/<ID>/view
        if (preg_match('~/d/([A-Za-z0-9_-]+)~', $tautan, $cocok) === 1) {
            return self::PRATINJAU_DRIVE.$cocok[1].'/preview';
        }

        // Bentuk lama: /open?id=<ID>
        parse_str((string) parse_url($tautan, PHP_URL_QUERY), $kueri);
        $id = $kueri['id'] ?? null;

        return is_string($id) && preg_match('~^[A-Za-z0-9_-]+$~', $id) === 1
            ? self::PRATINJAU_DRIVE.$id.'/preview'
            : null;
    }

    /**
     * Selisih harga beli terakhir terhadap rata-rata tertimbang dihitung sekali di
     * sini, supaya tabel, saringan, dan hitungan di atas tabel memakai satu
     * definisi yang sama, bukan tiga rumus yang bisa berbeda diam-diam.
     *
     * @param  array<string, mixed>  $baris
     * @return array<string, mixed>
     */
    private function rapikanBahan(array $baris): array
    {
        $baris['gambar_url'] = $this->tautanGambar($baris['gambar_url'] ?? null);

        $satuan = $this->angka($baris['harga_modal'] ?? null);
        $rata2 = $this->angka($baris['harga_rata2'] ?? null);

        // Bertanda, bukan nilai mutlak: positif berarti pembelian terakhir lebih
        // mahal daripada rata-rata stok yang ada, negatif berarti lebih murah.
        // Arahnya ikut ditampilkan, karena "berselisih 26%" saja tidak memberi tahu
        // apakah biayanya sedang naik atau turun.
        //
        // Pembaginya rata-rata tertimbang, jadi angkanya dibaca sebagai "sekian
        // persen terhadap rata-rata".
        $baris['selisih_rata2'] = ($satuan !== null && $rata2 !== null && $rata2 > 0)
            ? ($satuan - $rata2) / $rata2
            : null;

        $baris['menyimpang'] = $baris['selisih_rata2'] !== null
            && abs($baris['selisih_rata2']) >= self::AMBANG_SIMPANGAN;

        $baris['berstok'] = ($this->angka($baris['stok_sisa'] ?? null) ?? 0) > 0;

        return $baris;
    }

    /** @return array<mixed> */
    private function barisMentah(): array
    {
        $isi = $this->badan[$this->kunciBaris] ?? null;

        if (is_array($isi)) {
            return $this->bukaPembungkus($isi);
        }

        // Jawaban per-tab boleh saja langsung menaruh daftar barisnya di akar.
        return $this->bukaPembungkus($this->badan);
    }

    /**
     * @param  array<mixed>  $isi
     * @return array<mixed>
     */
    private function bukaPembungkus(array $isi): array
    {
        foreach (self::PEMBUNGKUS_BARIS as $kunci) {
            if (isset($isi[$kunci]) && is_array($isi[$kunci])) {
                return $isi[$kunci];
            }
        }

        return array_is_list($isi) ? $isi : [];
    }

    /**
     * Melebur baris kembar supaya tabelnya tidak jadi daftar berulang.
     *
     * Kuncinya nama produk + harga modal. Nama sama dengan harga sama berarti
     * satu baris; nama sama dengan harga berbeda tetap tampil sendiri-sendiri,
     * karena justru selisih harga itulah yang perlu dilihat sebelum marketing
     * memakai satu angka untuk semua unit.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return array<int, array<string, mixed>>
     */
    private function gabungkan(array $baris): array
    {
        $kelompok = [];

        foreach ($baris as $urutan => $item) {
            $kunci = $this->kunciGabung($item, $urutan);

            if (! isset($kelompok[$kunci])) {
                $kelompok[$kunci] = [
                    'induk' => $item,
                    'cacah' => 0,
                    'stok' => [],
                    'unik' => array_fill_keys(self::KOLOM_DILEBUR, []),
                ];
            }

            $kelompok[$kunci]['cacah']++;

            $stok = $this->angka($item['stok_sisa'] ?? null);

            if ($stok !== null) {
                $kelompok[$kunci]['stok'][] = $stok;
            }

            foreach (self::KOLOM_DILEBUR as $kolom) {
                $nilai = $item[$kolom] ?? null;

                if ($nilai !== null && $nilai !== '') {
                    // Kunci string sekaligus membuang nilai kembar.
                    $kelompok[$kunci]['unik'][$kolom][(string) $nilai] = $nilai;
                }
            }
        }

        return array_values(array_map(function (array $isi): array {
            $hasil = $isi['induk'];
            $hasil['digabung'] = $isi['cacah'];

            // Stok dari baris yang dilebur dijumlahkan; kalau tidak ada satu pun
            // angka yang bisa dipakai, nilai asli dibiarkan apa adanya.
            if ($isi['stok'] !== []) {
                $hasil['stok_sisa'] = array_sum($isi['stok']);
            }

            foreach (self::KOLOM_DILEBUR as $kolom) {
                $unik = array_values($isi['unik'][$kolom]);
                $hasil[$kolom] = $unik[0] ?? null;
                $hasil[$kolom.'_lain'] = max(0, count($unik) - 1);
            }

            return $hasil;
        }, $kelompok));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function kunciGabung(array $item, int $urutan): string
    {
        $nama = mb_strtolower(trim((string) ($item['nama_produk'] ?? '')));

        // Baris tanpa nama produk tidak bisa dipastikan kembar, jadi tidak pernah
        // dilebur -- lebih baik tampil dobel daripada diam-diam menghilang.
        if ($nama === '') {
            return 'tanpa-nama:'.$urutan;
        }

        $harga = $this->angka($item['harga_modal'] ?? null);

        // Harga non-angka dibandingkan sebagai teks supaya dua nilai yang tidak
        // terbaca tidak diperlakukan seolah-olah sama besar.
        $hargaKunci = $harga !== null
            ? number_format($harga, 4, '.', '')
            : 'teks:'.mb_strtolower(trim((string) ($item['harga_modal'] ?? '')));

        return $nama.'|'.$hargaKunci;
    }

    /**
     * @param  array<string, mixed>  $sumber
     * @param  array<string, array<int, string>>  $peta
     * @return array<string, mixed>
     */
    private function cocokkanNilai(array $sumber, array $peta): array
    {
        $hasil = [];

        foreach ($peta as $internal => $kandidat) {
            $hasil[$internal] = null;

            foreach ($kandidat as $nama) {
                if (! array_key_exists($nama, $sumber)) {
                    continue;
                }

                $nilai = $this->skalar($sumber[$nama]);

                if ($nilai !== null && $nilai !== '') {
                    $hasil[$internal] = $nilai;
                    break;
                }
            }
        }

        return $hasil;
    }

    /**
     * Memastikan nilai yang keluar dari pemetaan selalu bisa dicetak.
     *
     * Sebagian bidang datang sebagai objek, misalnya `jenis` atau `batch` yang
     * berbentuk {id, nama}. Diteruskan apa adanya ke Blade, nilai seperti itu
     * membuat htmlspecialchars() melempar dan seluruh halaman jadi 500 -- satu
     * bidang bersarang menjatuhkan tabelnya.
     *
     * Kalau objeknya membawa nama atau kode di dalam, itu yang dipakai. Kalau
     * tidak, hasilnya null dan selnya menampilkan "-": lebih baik satu sel kosong
     * daripada menebak isi yang salah.
     */
    private function skalar(mixed $nilai): string|int|float|bool|null
    {
        if (is_scalar($nilai)) {
            return $nilai;
        }

        if (is_object($nilai)) {
            $nilai = (array) $nilai;
        }

        if (! is_array($nilai)) {
            return null;
        }

        foreach (self::KUNCI_DALAM as $kunci) {
            if (isset($nilai[$kunci]) && is_scalar($nilai[$kunci])) {
                return $nilai[$kunci];
            }
        }

        return null;
    }

    private function angka(mixed $nilai): ?float
    {
        return is_numeric($nilai) ? (float) $nilai : null;
    }

    /**
     * Menyiapkan gambar_url untuk dipasang sebagai atribut src.
     *
     * Hanya http dan https yang diloloskan. Nilai bawaan inventory memang berupa
     * URL, tapi karena isinya berakhir di dalam atribut HTML, skema lain seperti
     * javascript: atau data: ditolak di sini alih-alih dipercaya begitu saja.
     * Yang gagal menjadi null, dan tabel menampilkan placeholder.
     *
     * Jalur relatif seperti /storage/bahan/1.png disambungkan ke alamat inventory,
     * karena tanpa itu gambarnya tidak akan pernah termuat dari CRM.
     */
    private function tautanGambar(mixed $nilai): ?string
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        $nilai = trim($nilai);

        // Jalur relatif satu garis miring; `//host` sengaja tidak termasuk supaya
        // tidak ada host lain yang bisa diselipkan tanpa skema.
        if (str_starts_with($nilai, '/') && ! str_starts_with($nilai, '//')) {
            $induk = rtrim((string) config('services.inventory.base_url'), '/');

            return $induk === '' ? null : $induk.$nilai;
        }

        return $this->tautanLuar($nilai);
    }

    /**
     * Tautan yang dipasang sebagai href, tanpa penyambungan jalur relatif.
     *
     * Pengetatannya lebih penting di sini daripada di src: `javascript:` pada href
     * benar-benar dijalankan browser saat diklik, sementara pada src tidak. Yang
     * tidak berskema http/https menjadi null, dan gambarnya tampil tanpa tautan.
     */
    private function tautanLuar(mixed $nilai): ?string
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        $nilai = trim($nilai);
        $skema = mb_strtolower((string) parse_url($nilai, PHP_URL_SCHEME));

        return in_array($skema, ['http', 'https'], true) ? $nilai : null;
    }

    private function diambilPada(): ?CarbonInterface
    {
        foreach (self::BIDANG_DIAMBIL_PADA as $nama) {
            if (empty($this->badan[$nama]) || ! is_string($this->badan[$nama])) {
                continue;
            }

            try {
                return Carbon::parse($this->badan[$nama]);
            } catch (\Throwable) {
                // Stempel waktu yang tidak terbaca lebih baik disembunyikan daripada
                // ditampilkan salah -- view akan diam soal kesegaran data.
                return null;
            }
        }

        return null;
    }
}
