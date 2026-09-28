<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\BuatPenawaran;
use App\Mcp\Tools\CariBundle;
use App\Mcp\Tools\CariKomponen;
use App\Mcp\Tools\CariPenawaranSerupa;
use App\Mcp\Tools\CariPic;
use Laravel\Mcp\Server;

/**
 * Menyusun penawaran dari Claude Code. Semua tool berjalan sebagai pemilik token,
 * dengan hak yang sama seperti saat ia membuka CRM di browser.
 */
class PenawaranServer extends Server
{
    protected string $name = 'CRM Penawaran';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        Server ini menyusun dan membuat penawaran harga (SPH) di CRM atas nama pengguna yang login.

        Alur kerja:
        1. Pahami kebutuhan: jenis alat/sistem, jumlah unit, instansi tujuan, lokasi, dan perusahaan penerbit (AS/ATC) bila disebut.
        2. Panggil `cari-penawaran-serupa` untuk melihat komposisi dan harga yang pernah dipakai. Utamakan yang `goal` atau disetujui.
        3. Panggil `cari-bundle` untuk paket price list dan `cari-komponen` untuk komponen satuan (mis. mengganti sensor di dalam bundle).
        4. Susun draft di chat: tabel item (judul, qty, satuan, harga satuan, subtotal, sumber harga), diskon, pajak, total, dan asumsi yang dipakai.
        5. Minta konfirmasi eksplisit. Setelah pengguna setuju, panggil `buat-penawaran` satu kali.
        6. Berikan nomor dokumen dan link-nya. Perubahan sesudahnya dilakukan pengguna di web.

        Aturan harga:
        - Pakai harga dari katalog (bundle/komponen). Jangan mengarang harga; kalau tidak ada di katalog, tanyakan.
        - Kalau harga katalog berbeda dari penawaran lama, sebutkan keduanya dan biarkan pengguna memilih.
        - `markup` adalah pengali (1.1 = naik 10%), bukan persen.

        Penawaran pengadaan biasanya terdiri dari bundle datalogger/sistem, bundle instalasi untuk wilayahnya,
        dan bundle sertifikasi produk (harga 0). Cek penawaran serupa untuk kebiasaan yang berlaku.
        MARKDOWN;

    protected array $tools = [
        CariPenawaranSerupa::class,
        CariBundle::class,
        CariKomponen::class,
        CariPic::class,
        BuatPenawaran::class,
    ];
}
