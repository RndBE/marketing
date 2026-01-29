<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductDetailSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('product_details')->insert([
            ['id' => 4, 'product_id' => 2, 'urutan' => 1, 'nama' => 'Datalogger BL-110 System Series (lengkap dengan enclosure IP67)', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 24000000, 'subtotal' => 24000000, 'created_at' => '2026-01-28 19:37:47', 'updated_at' => '2026-01-28 19:37:47'],
            ['id' => 5, 'product_id' => 2, 'urutan' => 2, 'nama' => 'Power Supply (Solar Cell, MPPT Solar Charger, Deep Cycle Battery)', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 20000000, 'subtotal' => 20000000, 'created_at' => '2026-01-28 19:38:08', 'updated_at' => '2026-01-28 19:38:08'],
            ['id' => 6, 'product_id' => 2, 'urutan' => 3, 'nama' => 'Internet Communication Data Sender', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:38:22', 'updated_at' => '2026-01-28 19:38:22'],
            ['id' => 7, 'product_id' => 2, 'urutan' => 4, 'nama' => 'Firmware embedded system', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:38:34', 'updated_at' => '2026-01-28 19:38:34'],
            ['id' => 8, 'product_id' => 2, 'urutan' => 5, 'nama' => 'Web monitoring access and database storage cloud services', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 1500000, 'subtotal' => 1500000, 'created_at' => '2026-01-28 19:38:51', 'updated_at' => '2026-01-28 19:38:51'],

            ['id' => 9, 'product_id' => 3, 'urutan' => 1, 'nama' => 'Datalogger BL-110 System Series (lengkap dengan enclosure IP67)', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 24000000, 'subtotal' => 24000000, 'created_at' => '2026-01-28 19:38:59', 'updated_at' => '2026-01-28 19:38:59'],
            ['id' => 10, 'product_id' => 3, 'urutan' => 2, 'nama' => 'Power Supply Converter AC/DC', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 20000000, 'subtotal' => 20000000, 'created_at' => '2026-01-28 19:38:59', 'updated_at' => '2026-01-28 19:39:31'],
            ['id' => 11, 'product_id' => 3, 'urutan' => 3, 'nama' => 'Internet Communication Data Sender', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:38:59', 'updated_at' => '2026-01-28 19:38:59'],
            ['id' => 12, 'product_id' => 3, 'urutan' => 4, 'nama' => 'Firmware embedded system', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:38:59', 'updated_at' => '2026-01-28 19:38:59'],
            ['id' => 13, 'product_id' => 3, 'urutan' => 5, 'nama' => 'Web monitoring access and database storage cloud services', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 1500000, 'subtotal' => 1500000, 'created_at' => '2026-01-28 19:38:59', 'updated_at' => '2026-01-28 19:38:59'],

            ['id' => 14, 'product_id' => 4, 'urutan' => 1, 'nama' => 'Datalogger BL-1100 System Series (lengkap dengan enclosure IP67)', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 24000000, 'subtotal' => 24000000, 'created_at' => '2026-01-28 19:39:52', 'updated_at' => '2026-01-28 19:40:21'],
            ['id' => 15, 'product_id' => 4, 'urutan' => 2, 'nama' => 'Power Supply Converter AC/DC', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 20000000, 'subtotal' => 20000000, 'created_at' => '2026-01-28 19:39:52', 'updated_at' => '2026-01-28 19:40:33'],
            ['id' => 16, 'product_id' => 4, 'urutan' => 3, 'nama' => 'Internet Communication Data Sender', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:39:52', 'updated_at' => '2026-01-28 19:39:52'],
            ['id' => 17, 'product_id' => 4, 'urutan' => 4, 'nama' => 'Firmware embedded system', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:39:52', 'updated_at' => '2026-01-28 19:39:52'],
            ['id' => 18, 'product_id' => 4, 'urutan' => 5, 'nama' => 'Web monitoring access and database storage cloud services', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 1500000, 'subtotal' => 1500000, 'created_at' => '2026-01-28 19:39:52', 'updated_at' => '2026-01-28 19:39:52'],

            ['id' => 19, 'product_id' => 5, 'urutan' => 1, 'nama' => 'Produksi perangkat (termasuk material Tiang Monopole, Bracket, Kerangkeng Outdoor Enclosure Box, dan Pondasi)', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Set', 'harga' => 1000000, 'subtotal' => 1000000, 'created_at' => '2026-01-28 19:42:23', 'updated_at' => '2026-01-28 19:42:23'],
            ['id' => 20, 'product_id' => 5, 'urutan' => 2, 'nama' => 'Pengiriman perangkat', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Set', 'harga' => 1000000, 'subtotal' => 1000000, 'created_at' => '2026-01-28 19:42:40', 'updated_at' => '2026-01-28 19:42:40'],
            ['id' => 21, 'product_id' => 5, 'urutan' => 3, 'nama' => 'Mobilisasi dan Akomodasi selama pemasangan', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Set', 'harga' => 1000000, 'subtotal' => 1000000, 'created_at' => '2026-01-28 19:42:54', 'updated_at' => '2026-01-28 19:42:54'],
            ['id' => 22, 'product_id' => 5, 'urutan' => 4, 'nama' => 'Pemasangan Perangkat', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Set', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:43:06', 'updated_at' => '2026-01-28 19:43:06'],
            ['id' => 23, 'product_id' => 5, 'urutan' => 5, 'nama' => 'Commissioning dan Testing Perangkat', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Set', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:43:16', 'updated_at' => '2026-01-28 19:43:16'],
            ['id' => 24, 'product_id' => 5, 'urutan' => 6, 'nama' => 'Training Perangkat', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Set', 'harga' => 1000000, 'subtotal' => 1000000, 'created_at' => '2026-01-28 19:43:29', 'updated_at' => '2026-01-28 19:43:29'],
            ['id' => 25, 'product_id' => 5, 'urutan' => 7, 'nama' => 'Garansi Perangkat selama 1 tahun (termasuk perangkat & paket data GSM)', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:43:40', 'updated_at' => '2026-01-28 19:43:40'],
            ['id' => 26, 'product_id' => 5, 'urutan' => 8, 'nama' => 'Penggantian Perangkat jika terjadi kerusakan selama masa garansi', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:43:52', 'updated_at' => '2026-01-28 19:43:52'],
            ['id' => 27, 'product_id' => 5, 'urutan' => 9, 'nama' => 'Mobilisasi & Akomodasi ke lapangan untuk perawatan selama masa garansi, 1x kunjungan setiap 2 bulan', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 19:44:04', 'updated_at' => '2026-01-28 19:44:04'],

            ['id' => 28, 'product_id' => 6, 'urutan' => 1, 'nama' => 'Datalogger BL-1100 System Series (lengkap dengan enclosure IP67)', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 24000000, 'subtotal' => 24000000, 'created_at' => '2026-01-28 20:26:44', 'updated_at' => '2026-01-28 20:26:44'],
            ['id' => 29, 'product_id' => 6, 'urutan' => 2, 'nama' => 'Power Supply Converter AC/DC', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 20000000, 'subtotal' => 20000000, 'created_at' => '2026-01-28 20:26:44', 'updated_at' => '2026-01-28 20:26:44'],
            ['id' => 30, 'product_id' => 6, 'urutan' => 3, 'nama' => 'Internet Communication Data Sender', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 20:26:44', 'updated_at' => '2026-01-28 20:26:44'],
            ['id' => 31, 'product_id' => 6, 'urutan' => 4, 'nama' => 'Firmware embedded system', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 20:26:44', 'updated_at' => '2026-01-28 20:26:44'],
            ['id' => 32, 'product_id' => 6, 'urutan' => 5, 'nama' => 'Web monitoring access and database storage cloud services', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 1500000, 'subtotal' => 1500000, 'created_at' => '2026-01-28 20:26:44', 'updated_at' => '2026-01-28 20:26:44'],

            ['id' => 33, 'product_id' => 7, 'urutan' => 1, 'nama' => 'Datalogger BL-110 System Series (lengkap dengan enclosure IP67)', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 24000000, 'subtotal' => 24000000, 'created_at' => '2026-01-28 20:27:57', 'updated_at' => '2026-01-28 20:27:57'],
            ['id' => 34, 'product_id' => 7, 'urutan' => 2, 'nama' => 'Power Supply Converter AC/DC', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 20000000, 'subtotal' => 20000000, 'created_at' => '2026-01-28 20:27:57', 'updated_at' => '2026-01-28 20:27:57'],
            ['id' => 35, 'product_id' => 7, 'urutan' => 3, 'nama' => 'Internet Communication Data Sender', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 20:27:57', 'updated_at' => '2026-01-28 20:27:57'],
            ['id' => 36, 'product_id' => 7, 'urutan' => 4, 'nama' => 'Firmware embedded system', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000, 'created_at' => '2026-01-28 20:27:57', 'updated_at' => '2026-01-28 20:27:57'],
            ['id' => 37, 'product_id' => 7, 'urutan' => 5, 'nama' => 'Web monitoring access and database storage cloud services', 'spesifikasi' => null, 'qty' => 1.00, 'satuan' => 'Unit', 'harga' => 1500000, 'subtotal' => 1500000, 'created_at' => '2026-01-28 20:27:57', 'updated_at' => '2026-01-28 20:27:57'],
        ]);
    }
}
