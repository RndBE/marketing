<?php

namespace Database\Seeders;

use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\PenawaranCover;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
use App\Models\PenawaranTerm;
use App\Models\PenawaranValidity;
use App\Models\Pic;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MarketingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::first() ?? User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password')
            ]);
            $kepala = User::firstOrCreate(
                ['email' => 'kepala@example.com'],
                [
                    'name' => 'Kepala',
                    'password' => Hash::make('password')
                ]
            );

            $keuangan = User::firstOrCreate(
                ['email' => 'keuangan@example.com'],
                [
                    'name' => 'Keuangan',
                    'password' => Hash::make('password')
                ]
            );

            $pic = Pic::create([
                'nama' => 'Budi Santoso',
                'jabatan' => 'PPK',
                'instansi' => 'Dinas PU',
                'email' => 'budi@instansi.go.id',
                'no_hp' => '08123456789',
                'alamat' => 'Jl. Contoh No. 1'
            ]);

            $p1 = Product::create([
                'kode' => 'PKT-TLM-01',
                'nama' => 'Paket Telemetri AWLR Basic',
                'deskripsi' => 'Bundle perangkat + instalasi',
                'is_active' => true
            ]);

            $d1 = ProductDetail::create([
                'product_id' => $p1->id,
                'urutan' => 1,
                'nama' => 'Data Logger',
                'spesifikasi' => '4G, IP67',
                'qty' => 1,
                'satuan' => 'unit',
                'harga' => 8500000,
                'subtotal' => 8500000
            ]);

            $d2 = ProductDetail::create([
                'product_id' => $p1->id,
                'urutan' => 2,
                'nama' => 'Sensor Water Level',
                'spesifikasi' => 'Range 0-10m',
                'qty' => 1,
                'satuan' => 'unit',
                'harga' => 3500000,
                'subtotal' => 3500000
            ]);

            $d3 = ProductDetail::create([
                'product_id' => $p1->id,
                'urutan' => 3,
                'nama' => 'Instalasi & Konfigurasi',
                'spesifikasi' => 'Termasuk pengujian',
                'qty' => 1,
                'satuan' => 'paket',
                'harga' => 2000000,
                'subtotal' => 2000000
            ]);

            $doc = DocNumber::create([
                'prefix' => 'PNW',
                'seq' => 1,
                'doc_no' => 'PNW-000001'
            ]);

            $penawaran = Penawaran::create([
                'id_pic' => $pic->id,
                'id_user' => $user->id,
                'doc_number_id' => $doc->id,
                'approval_id' => null,
                'judul' => 'Penawaran Pengadaan Telemetri',
                'catatan' => 'Harga belum termasuk PPN.',
                'date_created' => now()->timestamp,
                'date_updated' => now()->timestamp
            ]);

            PenawaranCover::create([
                'penawaran_id' => $penawaran->id,
                'judul_cover' => 'Dokumen Penawaran',
                'subjudul' => $penawaran->judul,
                'perusahaan_nama' => config('app.name'),
                'perusahaan_alamat' => 'Jl. Perusahaan No. 1',
                'perusahaan_email' => 'info@example.com',
                'perusahaan_telp' => '021-000000',
                'logo_path' => null,
                'intro_text' => 'Berikut kami sampaikan penawaran sesuai kebutuhan.'
            ]);

            PenawaranValidity::create([
                'penawaran_id' => $penawaran->id,
                'mulai' => now()->toDateString(),
                'sampai' => now()->addDays(30)->toDateString(),
                'berlaku_hari' => 30,
                'keterangan' => 'Penawaran berlaku 30 hari.'
            ]);

            $item = PenawaranItem::create([
                'penawaran_id' => $penawaran->id,
                'tipe' => 'bundle',
                'product_id' => $p1->id,
                'urutan' => 1,
                'judul' => $p1->nama,
                'catatan' => $p1->deskripsi,
                'subtotal' => 0
            ]);

            $sum = 0;

            foreach ([$d1, $d2, $d3] as $pd) {
                $qty = (float) $pd->qty;
                $harga = (int) $pd->harga;
                $subtotal = (int) round($qty * $harga);
                $sum += $subtotal;

                PenawaranItemDetail::create([
                    'penawaran_item_id' => $item->id,
                    'product_detail_id' => $pd->id,
                    'urutan' => $pd->urutan,
                    'nama' => $pd->nama,
                    'spesifikasi' => $pd->spesifikasi,
                    'qty' => $qty,
                    'satuan' => $pd->satuan,
                    'harga' => $harga,
                    'subtotal' => $subtotal
                ]);
            }

            $item->update(['subtotal' => $sum]);

            PenawaranTerm::create([
                'penawaran_id' => $penawaran->id,
                'urutan' => 1,
                'judul' => 'Pembayaran',
                'isi' => 'Termin pembayaran 50% DP, 50% setelah serah terima.'
            ]);
        });
    }
}
