<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlurPenawaranSeeder extends Seeder
{
    public function run(): void
    {
        // Buat alur penawaran
        $alurId = DB::table('alur_penawaran')->insertGetId([
            'nama' => 'Alur Pengajuan Penawaran',
            'berlaku_untuk' => 'penawaran',
            'status' => 'aktif',
            'dibuat_oleh' => null,
            'dibuat_pada' => now(),
            'diubah_pada' => now(),
        ]);

        // Ambil user_id untuk Megaratri dan Yanu
        $megaratri = DB::table('users')->where('name', 'Megaratri Ika Listina Dewi')->first();
        $yanu = DB::table('users')->where('name', 'Yanu Hertanto')->first();

        // Buat langkah-langkah approval
        DB::table('langkah_alur_penawaran')->insert([
            [
                'alur_penawaran_id' => $alurId,
                'no_langkah' => 1,
                'nama_langkah' => 'Approval Manager',
                'user_id' => $megaratri->id,
                'harus_semua' => true,
                'kondisi' => null,
                'dibuat_pada' => now(),
                'diubah_pada' => now(),
            ],
            [
                'alur_penawaran_id' => $alurId,
                'no_langkah' => 2,
                'nama_langkah' => 'Approval Direktur',
                'user_id' => $yanu->id,
                'harus_semua' => true,
                'kondisi' => null,
                'dibuat_pada' => now(),
                'diubah_pada' => now(),
            ],
        ]);
    }
}
