<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->insert([
            ['id' => 2, 'kode' => null, 'nama' => 'STESY Beacon Datalogger BL-110 as a substitution of Datalogger 2 Channel (Include Power Supply)', 'deskripsi' => null, 'satuan' => 'Unit', 'is_active' => 1, 'created_at' => '2026-01-28 19:36:30', 'updated_at' => '2026-01-28 19:56:27'],
            ['id' => 3, 'kode' => null, 'nama' => 'STESY Beacon Datalogger BL-110 as a substitution of Datalogger 2 Channel', 'deskripsi' => null, 'satuan' => 'Unit', 'is_active' => 1, 'created_at' => '2026-01-28 19:38:59', 'updated_at' => '2026-01-28 19:39:18'],
            ['id' => 4, 'kode' => null, 'nama' => 'STESY Beacon Datalogger BL-1100 as a substitution of Datalogger 3 Channel', 'deskripsi' => null, 'satuan' => 'Unit', 'is_active' => 1, 'created_at' => '2026-01-28 19:39:52', 'updated_at' => '2026-01-28 20:27:23'],
            ['id' => 5, 'kode' => null, 'nama' => 'Jasa Instalasi Datalogger Wilayah Jawa Tengah', 'deskripsi' => null, 'satuan' => 'Set', 'is_active' => 1, 'created_at' => '2026-01-28 19:42:01', 'updated_at' => '2026-01-28 19:56:35'],
            ['id' => 6, 'kode' => null, 'nama' => 'STESY Beacon Datalogger BL-1100', 'deskripsi' => null, 'satuan' => 'Unit', 'is_active' => 1, 'created_at' => '2026-01-28 20:26:44', 'updated_at' => '2026-01-28 20:27:12'],
            ['id' => 7, 'kode' => null, 'nama' => 'STESY Beacon Datalogger BL-110', 'deskripsi' => null, 'satuan' => 'Unit', 'is_active' => 1, 'created_at' => '2026-01-28 20:27:57', 'updated_at' => '2026-01-28 20:28:10'],
        ]);
    }
}
