<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PenawaranTermTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('penawaran_term_templates')->insert([
            ['id' => 8, 'parent_id' => null, 'urutan' => 1, 'judul' => null, 'isi' => 'Harga FOB Derah Istimewa Yogyakarta (DIY)', 'created_at' => '2026-01-28 19:29:19', 'updated_at' => '2026-01-28 19:29:19'],
            ['id' => 9, 'parent_id' => null, 'urutan' => 2, 'judul' => null, 'isi' => 'Harga sudah termasuk biaya jasa instalasi dan aktivasi website monitoring', 'created_at' => '2026-01-28 19:29:36', 'updated_at' => '2026-01-28 19:29:36'],
            ['id' => 10, 'parent_id' => null, 'urutan' => 3, 'judul' => null, 'isi' => 'Harga tidak termasuk pekerjaan fisik (konstruksi sipil / pembangunan pos)', 'created_at' => '2026-01-28 19:29:43', 'updated_at' => '2026-01-28 19:29:43'],
            ['id' => 11, 'parent_id' => null, 'urutan' => 4, 'judul' => null, 'isi' => 'Harga dapat berubah apabila berubah lokasi dan kondisi yang mempengaruhi instalasi', 'created_at' => '2026-01-28 19:29:53', 'updated_at' => '2026-01-28 19:29:53'],
            ['id' => 12, 'parent_id' => null, 'urutan' => 5, 'judul' => null, 'isi' => 'Harga sudah termasuk pengiriman logistik, pemasangan, testing, comissioning, programming, dan akomodasi', 'created_at' => '2026-01-28 19:29:59', 'updated_at' => '2026-01-28 19:29:59'],
            ['id' => 13, 'parent_id' => null, 'urutan' => 6, 'judul' => null, 'isi' => 'Pembayaran DP bersifat wajib, tanpa menggunakan bank jaminan dan tanpa retensi', 'created_at' => '2026-01-28 19:30:05', 'updated_at' => '2026-01-28 19:30:05'],
            ['id' => 14, 'parent_id' => 13, 'urutan' => 1, 'judul' => null, 'isi' => 'Termin 1 : DP sebesar 20% setelah kontrak dan PO kami terima', 'created_at' => '2026-01-28 19:30:16', 'updated_at' => '2026-01-28 19:30:16'],
            ['id' => 15, 'parent_id' => 13, 'urutan' => 2, 'judul' => null, 'isi' => 'Termin 2 : Pelunasan 80% setelah Material On Site', 'created_at' => '2026-01-28 19:30:26', 'updated_at' => '2026-01-28 19:30:26'],
            ['id' => 16, 'parent_id' => null, 'urutan' => 7, 'judul' => null, 'isi' => 'Barang indent produksi estimasi 2 bulan setelah PO dan DP diterima', 'created_at' => '2026-01-28 19:30:34', 'updated_at' => '2026-01-28 19:30:34'],
            ['id' => 17, 'parent_id' => null, 'urutan' => 8, 'judul' => null, 'isi' => 'Harga di atas sudah berdasarkan hasil negosiasi', 'created_at' => '2026-01-28 19:30:41', 'updated_at' => '2026-01-28 19:30:41'],
            ['id' => 18, 'parent_id' => null, 'urutan' => 9, 'judul' => null, 'isi' => 'Harga sudah termasuk garansi peralatan selama 1 tahun', 'created_at' => '2026-01-28 19:30:50', 'updated_at' => '2026-01-28 19:30:50'],
            ['id' => 19, 'parent_id' => null, 'urutan' => 10, 'judul' => null, 'isi' => 'Harga berlaku Januari 2026', 'created_at' => '2026-01-28 19:30:55', 'updated_at' => '2026-01-28 19:30:55'],
        ]);
    }
}
