<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InvoiceSignatureTemplate;
use App\Models\InvoiceTermTemplate;

class TemplateSeeder extends Seeder
{
    public function run()
    {
        // Signatures
        InvoiceSignatureTemplate::create([
            'template_name' => 'Direktur Utama',
            'nama' => 'Budi Santoso',
            'jabatan' => 'Direktur Utama',
            'kota' => 'Yogyakarta',
            'ttd_path' => null // No image for now
        ]);

        InvoiceSignatureTemplate::create([
            'template_name' => 'Marketing Manager',
            'nama' => 'Siti Aminah',
            'jabatan' => 'Marketing Manager',
            'kota' => 'Sleman',
            'ttd_path' => null
        ]);

        // Terms
        InvoiceTermTemplate::create([
            'template_name' => 'Syarat Umum',
            'terms' => [
                'Pembayaran DP minimal 50%.',
                'Pelunasan maksimal 7 hari setelah invoice diterima.',
                'Harga sudah termasuk PPN 11%.'
            ]
        ]);

        InvoiceTermTemplate::create([
            'template_name' => 'Proyek Pemerintah',
            'terms' => [
                'Pembayaran termin sesuai progress.',
                'Garansi 1 tahun.',
                'Dokumen lengkap sesuai SPK.'
            ]
        ]);
    }
}
