<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PenawaranTermTemplate;

class PenawaranTermTemplateSeeder extends Seeder
{
    public function run(): void
    {
        PenawaranTermTemplate::query()->delete();

        $pembayaran = PenawaranTermTemplate::create([
            'parent_id' => null,
            'urutan' => 1,
            'judul' => 'Pembayaran',
            'isi' => 'Termin pembayaran sesuai kesepakatan.',
        ]);

        PenawaranTermTemplate::create([
            'parent_id' => $pembayaran->id,
            'urutan' => 1,
            'judul' => null,
            'isi' => '50% DP, 50% setelah serah terima.',
        ]);

        $pengiriman = PenawaranTermTemplate::create([
            'parent_id' => null,
            'urutan' => 2,
            'judul' => 'Pengiriman',
            'isi' => 'Waktu pengiriman menyesuaikan jadwal dan ketersediaan barang.',
        ]);

        PenawaranTermTemplate::create([
            'parent_id' => $pengiriman->id,
            'urutan' => 1,
            'judul' => null,
            'isi' => 'Biaya pengiriman mengikuti lokasi pekerjaan (jika ada).',
        ]);

        $garansi = PenawaranTermTemplate::create([
            'parent_id' => null,
            'urutan' => 3,
            'judul' => 'Garansi',
            'isi' => 'Garansi unit sesuai ketentuan pabrikan/penyedia.',
        ]);

        PenawaranTermTemplate::create([
            'parent_id' => $garansi->id,
            'urutan' => 1,
            'judul' => null,
            'isi' => 'Garansi tidak berlaku jika kerusakan akibat kesalahan pemakaian.',
        ]);

        PenawaranTermTemplate::create([
            'parent_id' => null,
            'urutan' => 4,
            'judul' => 'Catatan',
            'isi' => 'Harga belum termasuk pajak (jika pajak diaktifkan pada penawaran).',
        ]);
    }
}
