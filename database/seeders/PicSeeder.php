<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PicSeeder extends Seeder
{
    public function run(): void
    {
        $pics = [
            // BBWS Serayu Opak
            [
                'nama' => 'Ir. Bambang Supriyanto, M.T.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Serayu Opak',
                'email' => 'bbws.serayuopak@pu.go.id',
                'no_hp' => '081234567890',
                'alamat' => 'Jl. Magelang Km. 7, Yogyakarta',
            ],
            // BBWS Bengawan Solo
            [
                'nama' => 'Ir. Suharyanto, M.Sc.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Bengawan Solo',
                'email' => 'bbws.bengawansolo@pu.go.id',
                'no_hp' => '081234567891',
                'alamat' => 'Jl. Colombo No. 31, Solo, Jawa Tengah',
            ],
            // BBWS Pemali Juana
            [
                'nama' => 'Ir. Ahmad Fauzi, M.T.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Pemali Juana',
                'email' => 'bbws.pemalijuana@pu.go.id',
                'no_hp' => '081234567892',
                'alamat' => 'Jl. Pemuda No. 1, Semarang, Jawa Tengah',
            ],
            // BBWS Citarum
            [
                'nama' => 'Ir. Dedi Supriadi, M.T.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Citarum',
                'email' => 'bbws.citarum@pu.go.id',
                'no_hp' => '081234567893',
                'alamat' => 'Jl. Inspeksi Cikapundung, Bandung, Jawa Barat',
            ],
            // BBWS Cimanuk Cisanggarung
            [
                'nama' => 'Ir. Eko Prasetyo, M.Eng.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Cimanuk Cisanggarung',
                'email' => 'bbws.cimanukcisanggarung@pu.go.id',
                'no_hp' => '081234567894',
                'alamat' => 'Jl. RE Martadinata No. 34, Cirebon, Jawa Barat',
            ],
            // BBWS Brantas
            [
                'nama' => 'Ir. Widodo Haryanto, M.T.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Brantas',
                'email' => 'bbws.brantas@pu.go.id',
                'no_hp' => '081234567895',
                'alamat' => 'Jl. Jetayu No. 4, Surabaya, Jawa Timur',
            ],
            // BBWS Pompengan Jeneberang
            [
                'nama' => 'Ir. Muhammad Yusuf, M.Sc.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Pompengan Jeneberang',
                'email' => 'bbws.pompenganjeneberang@pu.go.id',
                'no_hp' => '081234567896',
                'alamat' => 'Jl. Perintis Kemerdekaan Km. 18, Makassar, Sulawesi Selatan',
            ],
            // BBWS Sumatera V
            [
                'nama' => 'Ir. Rizki Ramadhan, M.T.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Sumatera V',
                'email' => 'bbws.sumaterav@pu.go.id',
                'no_hp' => '081234567897',
                'alamat' => 'Jl. Diponegoro No. 28, Palembang, Sumatera Selatan',
            ],
            // BBWS Sumatera II
            [
                'nama' => 'Ir. Hendra Gunawan, M.Eng.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Sumatera II',
                'email' => 'bbws.sumateraii@pu.go.id',
                'no_hp' => '081234567898',
                'alamal' => 'Jl. Sisingamangaraja No. 1, Medan, Sumatera Utara',
            ],
            // BBWS Kalimantan III
            [
                'nama' => 'Ir. Agus Suryanto, M.T.',
                'jabatan' => 'Kepala Balai',
                'instansi' => 'BBWS Kalimantan III',
                'email' => 'bbws.kalimantaniii@pu.go.id',
                'no_hp' => '081234567899',
                'alamat' => 'Jl. Lambung Mangkurat No. 16, Banjarmasin, Kalimantan Selatan',
            ],
        ];

        foreach ($pics as $pic) {
            DB::table('pics')->insert([
                'nama' => $pic['nama'],
                'jabatan' => $pic['jabatan'],
                'instansi' => $pic['instansi'],
                'email' => $pic['email'],
                'no_hp' => $pic['no_hp'],
                'alamat' => $pic['alamat'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
