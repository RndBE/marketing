<?php

namespace App\Http\Controllers;

use App\Services\Inventory\HargaModalClient;
use App\Services\Inventory\SaringanHargaModal;
use App\Services\Inventory\TabHargaModal;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class HargaModalController extends Controller
{
    public function __construct(private readonly HargaModalClient $client) {}

    /**
     * Harga modal milik pengguna yang sedang login, satu tab per kunjungan.
     *
     * Emailnya selalu diambil dari sesi, tidak pernah dari query string dan tidak
     * pernah ditulis mati. Kalau satu email dipatok di sini, semua orang yang
     * membuka halaman ini akan melihat data orang yang sama tanpa pernah dicek
     * haknya di inventory.
     *
     * Tab berpindah lewat tautan biasa, bukan pemanggilan dari browser, supaya
     * kunci API tetap tinggal di server.
     */
    public function index(Request $request)
    {
        $tab = TabHargaModal::dariPermintaan($request->query('tab'));
        $hanyaTersedia = $request->boolean('hanya_tersedia');
        $saringan = SaringanHargaModal::dari($request);

        $hasil = $this->client->untukEmail($request->user()->email, $tab, $hanyaTersedia);

        // Saring dulu atas seluruh baris tab, baru dipenggal jadi halaman.
        $tersaring = $saringan->saring($hasil->baris, $tab);

        return view('harga_modal.index', [
            'hasil' => $hasil,
            'tab' => $tab,
            'hanyaTersedia' => $hanyaTersedia,
            'saringan' => $saringan,
            'halaman' => $this->penggal($request, $tersaring),
            'jumlahTersaring' => count($tersaring),
            'email' => $request->user()->email,
        ]);
    }

    /**
     * Bahan yang dipakai satu batch produksi -- tujuan tombol "Lihat Bahan".
     *
     * Panggilannya tetap dari server, bukan dari browser: kalau halaman ini
     * memanggil inventory lewat JavaScript, X-API-KEY harus ikut ke sisi klien dan
     * seluruh HPP terbuka bagi siapa pun yang membuka DevTools.
     */
    public function rincian(Request $request)
    {
        $tipe = TabHargaModal::tryFrom((string) $request->query('tipe'));
        $produksiId = trim((string) $request->query('produksi_id'));
        $fragmen = $request->boolean('fragmen');

        // Rincian hanya ada untuk unit hasil produksi. Tautan tanpa produksi_id
        // seharusnya tidak pernah terbentuk -- tombolnya dinonaktifkan -- jadi yang
        // sampai ke sini hanya URL yang disunting tangan.
        if ($tipe === null || ! $tipe->berbasisUnit() || $produksiId === '') {
            // Permintaan fragmen tidak boleh diarahkan: modal akan menelan seluruh
            // halaman tujuan sebagai isinya. Status galat saja, biar modal bicara.
            return $fragmen
                ? response('Tautan rincian tidak lengkap.', 422)
                : redirect()->route('harga-modal.index');
        }

        $hasil = $this->client->rincianBahan($request->user()->email, $tipe, $produksiId);

        $data = [
            'hasil' => $hasil,
            'tipe' => $tipe,
            'produksiId' => $produksiId,
            'kodeProduksi' => trim((string) $request->query('kode', '')),
            'namaProduk' => trim((string) $request->query('nama', '')),
            'halaman' => $this->penggal($request, $hasil->baris),
            'email' => $request->user()->email,
            // Di dalam modal, blok tiga angka itu menempel di atas badan yang bergulir.
            'lengket' => $fragmen,
        ];

        // Badan modal dan isi halaman penuh dirender dari berkas yang sama.
        return $fragmen
            ? view('harga_modal.partials.rincian_isi', $data)
            : view('harga_modal.rincian', $data);
    }

    /**
     * Memenggal baris jadi satu halaman.
     *
     * Barisnya sudah ada di memori, jadi yang dihemat di sini bukan panggilan ke
     * inventory melainkan HTML yang dikirim ke browser: tab Bahan bisa berisi
     * ribuan bahan, dan merendernya sekaligus membuat halaman berat dibuka.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function penggal(Request $request, array $baris): LengthAwarePaginator
    {
        $perHalaman = SaringanHargaModal::PER_HALAMAN;
        $total = count($baris);
        $terakhir = max(1, (int) ceil($total / $perHalaman));

        // Nomor halaman di luar jangkauan dijepit, supaya ?halaman=999 menampilkan
        // halaman terakhir alih-alih tabel kosong tanpa penjelasan.
        $sekarang = min(max(1, (int) $request->query('halaman', 1)), $terakhir);

        return new LengthAwarePaginator(
            array_slice($baris, ($sekarang - 1) * $perHalaman, $perHalaman),
            $total,
            $perHalaman,
            $sekarang,
            [
                'path' => $request->url(),
                'pageName' => 'halaman',
                // `fragmen` dilepas supaya tautan halaman tetap tautan yang wajar
                // kalau dibuka langsung; JavaScript modal menambahkannya sendiri.
                'query' => Arr::except($request->query(), ['halaman', 'fragmen']),
            ],
        );
    }
}
