<?php

namespace Database\Seeders;

use App\Models\AlurPenawaran;
use App\Models\LangkahAlurPenawaran;
use App\Models\Permission;
use App\Models\PenawaranTermTemplate;
use App\Models\Pic;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ========================
        // USERS
        // ========================
        $users = [
            ['id' => 1, 'name' => 'Yanu Hertanto', 'email' => 'yanu@arsol.co.id', 'password' => Hash::make('password'), 'ttd' => 'signatures/Ll1fd6YtaW5KztepuYyVnFCu4UJRBepu6VT1UQkF.png'],
            ['id' => 2, 'name' => 'Megaratri Ika Listina Dewi', 'email' => 'megaratrilistina14@gmail.com', 'password' => Hash::make('password'), 'ttd' => 'signatures/NA4xqNKlBRZB2PBGO8HpYUqCTleeuu7hLpNKQLaC.png'],
            ['id' => 3, 'name' => 'Nofita Tri Hanggarini', 'email' => 'novitatrihanggarini@gmail.com', 'password' => Hash::make('password'), 'ttd' => 'signatures/7ZEzrqAfJijG1RHq49fisGmulvhQE59K55hG2ZGd.png'],
            ['id' => 4, 'name' => 'Akhmad Zaeni Mustofa', 'email' => 'zeniakhmadmustofa@gmail.com', 'password' => Hash::make('password'), 'ttd' => null],
            ['id' => 5, 'name' => 'superadmin', 'email' => 'superadmin@gmail.com', 'password' => Hash::make('password'), 'ttd' => null],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        // ========================
        // ROLES
        // ========================
        $roles = [
            ['id' => 1, 'name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrator dengan akses penuh'],
            ['id' => 2, 'name' => 'Corporate Account Manager', 'slug' => 'sales', 'description' => 'Pembuat penawaran'],
            ['id' => 4, 'name' => 'Bussines Development', 'slug' => 'busdev', 'description' => 'Pembuat usulan penawaran'],
            ['id' => 5, 'name' => 'Direktur', 'slug' => 'direktur', 'description' => 'Direktur CV Arta Solusindo'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        // ========================
        // PERMISSIONS
        // ========================
        $permissions = [
            ['id' => 1, 'name' => 'Kelola Users', 'slug' => 'manage-users', 'description' => 'CRUD users', 'group' => 'User Management'],
            ['id' => 2, 'name' => 'Kelola Roles', 'slug' => 'manage-roles', 'description' => 'CRUD roles', 'group' => 'User Management'],
            ['id' => 3, 'name' => 'Kelola Permissions', 'slug' => 'manage-permissions', 'description' => 'CRUD permissions', 'group' => 'User Management'],
            ['id' => 4, 'name' => 'Lihat Penawaran (Sendiri)', 'slug' => 'view-penawaran', 'description' => 'Lihat penawaran yang dibuat sendiri', 'group' => 'Penawaran'],
            ['id' => 5, 'name' => 'Buat Penawaran', 'slug' => 'create-penawaran', 'description' => 'Buat penawaran baru', 'group' => 'Penawaran'],
            ['id' => 6, 'name' => 'Edit Penawaran', 'slug' => 'edit-penawaran', 'description' => 'Edit penawaran', 'group' => 'Penawaran'],
            ['id' => 7, 'name' => 'Hapus Penawaran', 'slug' => 'delete-penawaran', 'description' => 'Hapus penawaran', 'group' => 'Penawaran'],
            ['id' => 8, 'name' => 'Approve Penawaran', 'slug' => 'approve-penawaran', 'description' => 'Approve atau reject penawaran', 'group' => 'Penawaran'],
            ['id' => 17, 'name' => 'Lihat Semua Penawaran', 'slug' => 'view-all-penawaran', 'description' => 'Lihat semua penawaran', 'group' => 'Penawaran'],
            ['id' => 9, 'name' => 'Kelola Price List', 'slug' => 'manage-pricelist', 'description' => 'CRUD price list', 'group' => 'Price List'],
            ['id' => 10, 'name' => 'Kelola PIC', 'slug' => 'manage-pic', 'description' => 'CRUD PIC', 'group' => 'PIC'],
            ['id' => 11, 'name' => 'Kelola Alur Approval', 'slug' => 'manage-alur', 'description' => 'CRUD alur approval', 'group' => 'Alur Approval'],
            ['id' => 12, 'name' => 'Lihat Usulan', 'slug' => 'view-usulan', 'description' => 'Lihat daftar usulan', 'group' => 'Usulan Penawaran'],
            ['id' => 13, 'name' => 'Buat Usulan', 'slug' => 'create-usulan', 'description' => 'Buat usulan baru', 'group' => 'Usulan Penawaran'],
            ['id' => 14, 'name' => 'Edit Usulan', 'slug' => 'edit-usulan', 'description' => 'Edit usulan sendiri', 'group' => 'Usulan Penawaran'],
            ['id' => 15, 'name' => 'Hapus Usulan', 'slug' => 'delete-usulan', 'description' => 'Hapus usulan', 'group' => 'Usulan Penawaran'],
            ['id' => 16, 'name' => 'Tanggapi Usulan', 'slug' => 'respond-usulan', 'description' => 'Tanggapi dan approve usulan', 'group' => 'Usulan Penawaran'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // ========================
        // PERMISSION - ROLE (pivot)
        // ========================
        $permissionRole = [
            // Admin (role_id: 1) - all permissions
            ['permission_id' => 1, 'role_id' => 1],
            ['permission_id' => 2, 'role_id' => 1],
            ['permission_id' => 3, 'role_id' => 1],
            ['permission_id' => 4, 'role_id' => 1],
            ['permission_id' => 5, 'role_id' => 1],
            ['permission_id' => 6, 'role_id' => 1],
            ['permission_id' => 7, 'role_id' => 1],
            ['permission_id' => 8, 'role_id' => 1],
            ['permission_id' => 9, 'role_id' => 1],
            ['permission_id' => 10, 'role_id' => 1],
            ['permission_id' => 11, 'role_id' => 1],
            ['permission_id' => 12, 'role_id' => 1],
            ['permission_id' => 13, 'role_id' => 1],
            ['permission_id' => 14, 'role_id' => 1],
            ['permission_id' => 15, 'role_id' => 1],
            ['permission_id' => 16, 'role_id' => 1],
            ['permission_id' => 17, 'role_id' => 1],

            // Corporate Account Manager (role_id: 2)
            ['permission_id' => 4, 'role_id' => 2],
            ['permission_id' => 8, 'role_id' => 2],
            ['permission_id' => 9, 'role_id' => 2],
            ['permission_id' => 10, 'role_id' => 2],
            ['permission_id' => 12, 'role_id' => 2],
            ['permission_id' => 15, 'role_id' => 2],
            ['permission_id' => 16, 'role_id' => 2],

            // Bussines Development (role_id: 4)
            ['permission_id' => 4, 'role_id' => 4],
            ['permission_id' => 10, 'role_id' => 4],
            ['permission_id' => 12, 'role_id' => 4],
            ['permission_id' => 13, 'role_id' => 4],
            ['permission_id' => 15, 'role_id' => 4],

            // Direktur (role_id: 5)
            ['permission_id' => 4, 'role_id' => 5],
            ['permission_id' => 5, 'role_id' => 5],
            ['permission_id' => 6, 'role_id' => 5],
            ['permission_id' => 7, 'role_id' => 5],
            ['permission_id' => 8, 'role_id' => 5],
            ['permission_id' => 9, 'role_id' => 5],
            ['permission_id' => 10, 'role_id' => 5],
            ['permission_id' => 11, 'role_id' => 5],
            ['permission_id' => 12, 'role_id' => 5],
            ['permission_id' => 17, 'role_id' => 5],
        ];

        foreach ($permissionRole as $pr) {
            DB::table('permission_role')->insert($pr);
        }

        // ========================
        // USER - ROLE (pivot)
        // ========================
        $userRole = [
            ['user_id' => 1, 'role_id' => 5], // Yanu - Direktur
            ['user_id' => 2, 'role_id' => 2], // Megaratri - Corporate Account Manager
            ['user_id' => 3, 'role_id' => 2], // Nofita - Corporate Account Manager
            ['user_id' => 4, 'role_id' => 4], // Akhmad - Bussines Development
            ['user_id' => 5, 'role_id' => 1], // superadmin - Admin
        ];

        foreach ($userRole as $ur) {
            DB::table('role_user')->insert($ur);
        }

        // ========================
        // PICs
        // ========================
        $pics = [
            ['id' => 1, 'nama' => 'Ir. Bambang Supriyanto, M.T.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Serayu Opak', 'email' => 'bbws.serayuopak@pu.go.id', 'no_hp' => '081234567890', 'alamat' => 'Jl. Magelang Km. 7, Yogyakarta'],
            ['id' => 2, 'nama' => 'Ir. Suharyanto, M.Sc.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Bengawan Solo', 'email' => 'bbws.bengawansolo@pu.go.id', 'no_hp' => '081234567891', 'alamat' => 'Jl. Colombo No. 31, Solo, Jawa Tengah'],
            ['id' => 3, 'nama' => 'Ir. Ahmad Fauzi, M.T.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Pemali Juana', 'email' => 'bbws.pemalijuana@pu.go.id', 'no_hp' => '081234567892', 'alamat' => 'Jl. Pemuda No. 1, Semarang, Jawa Tengah'],
            ['id' => 4, 'nama' => 'Ir. Dedi Supriadi, M.T.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Citarum', 'email' => 'bbws.citarum@pu.go.id', 'no_hp' => '081234567893', 'alamat' => 'Jl. Inspeksi Cikapundung, Bandung, Jawa Barat'],
            ['id' => 5, 'nama' => 'Ir. Eko Prasetyo, M.Eng.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Cimanuk Cisanggarung', 'email' => 'bbws.cimanukcisanggarung@pu.go.id', 'no_hp' => '081234567894', 'alamat' => 'Jl. RE Martadinata No. 34, Cirebon, Jawa Barat'],
            ['id' => 6, 'nama' => 'Ir. Widodo Haryanto, M.T.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Brantas', 'email' => 'bbws.brantas@pu.go.id', 'no_hp' => '081234567895', 'alamat' => 'Jl. Jetayu No. 4, Surabaya, Jawa Timur'],
            ['id' => 7, 'nama' => 'Ir. Muhammad Yusuf, M.Sc.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Pompengan Jeneberang', 'email' => 'bbws.pompenganjeneberang@pu.go.id', 'no_hp' => '081234567896', 'alamat' => 'Jl. Perintis Kemerdekaan Km. 18, Makassar, Sulawesi Selatan'],
            ['id' => 8, 'nama' => 'Ir. Rizki Ramadhan, M.T.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Sumatera V', 'email' => 'bbws.sumaterav@pu.go.id', 'no_hp' => '081234567897', 'alamat' => 'Jl. Diponegoro No. 28, Palembang, Sumatera Selatan'],
            ['id' => 9, 'nama' => 'Ir. Hendra Gunawan, M.Eng.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Sumatera II', 'email' => 'bbws.sumateraii@pu.go.id', 'no_hp' => '081234567898', 'alamat' => null],
            ['id' => 10, 'nama' => 'Ir. Agus Suryanto, M.T.', 'jabatan' => 'Kepala Balai', 'instansi' => 'BBWS Kalimantan III', 'email' => 'bbws.kalimantaniii@pu.go.id', 'no_hp' => '081234567899', 'alamat' => 'Jl. Lambung Mangkurat No. 16, Banjarmasin, Kalimantan Selatan'],
        ];

        foreach ($pics as $pic) {
            Pic::create($pic);
        }

        // ========================
        // ALUR PENAWARAN
        // ========================
        $alur = AlurPenawaran::create([
            'id' => 1,
            'nama' => 'Alur Pengajuan Penawaran',
            'berlaku_untuk' => 'penawaran',
            'status' => 'aktif',
        ]);

        LangkahAlurPenawaran::create([
            'id' => 1,
            'alur_penawaran_id' => 1,
            'no_langkah' => 1,
            'nama_langkah' => 'Approval Direktur',
            'user_id' => 1,
            'harus_semua' => true,
        ]);

        // ========================
        // PRODUCTS
        // ========================
        $products = [
            [
                'id' => 4,
                'nama' => 'STESY Beacon Datalogger BL-1100 as a substitution of Datalogger 3 Channel',
                'satuan' => 'Unit',
                'is_active' => true,
                'details' => [
                    ['urutan' => 1, 'nama' => 'Datalogger BL-1100 System Series (lengkap dengan enclosure IP67)', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 24000000, 'subtotal' => 24000000],
                    ['urutan' => 2, 'nama' => 'Power Supply Converter AC/DC', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 20000000, 'subtotal' => 20000000],
                    ['urutan' => 3, 'nama' => 'Internet Communication Data Sender', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 4, 'nama' => 'Firmware embedded system', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 5, 'nama' => 'Web monitoring access and database storage cloud services', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 1500000, 'subtotal' => 1500000],
                ],
            ],
            [
                'id' => 5,
                'nama' => 'Jasa Instalasi Datalogger Wilayah Jawa Tengah',
                'satuan' => 'Set',
                'is_active' => true,
                'details' => [
                    ['urutan' => 1, 'nama' => 'Produksi perangkat (termasuk material Tiang Monopole, Bracket, Kerangkeng Outdoor Enclosure Box, dan Pondasi)', 'qty' => 1, 'satuan' => 'Set', 'harga' => 1000000, 'subtotal' => 1000000],
                    ['urutan' => 2, 'nama' => 'Pengiriman perangkat', 'qty' => 1, 'satuan' => 'Set', 'harga' => 1000000, 'subtotal' => 1000000],
                    ['urutan' => 3, 'nama' => 'Mobilisasi dan Akomodasi selama pemasangan', 'qty' => 1, 'satuan' => 'Set', 'harga' => 1000000, 'subtotal' => 1000000],
                    ['urutan' => 4, 'nama' => 'Pemasangan Perangkat', 'qty' => 1, 'satuan' => 'Set', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 5, 'nama' => 'Commissioning dan Testing Perangkat', 'qty' => 1, 'satuan' => 'Set', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 6, 'nama' => 'Training Perangkat', 'qty' => 1, 'satuan' => 'Set', 'harga' => 1000000, 'subtotal' => 1000000],
                    ['urutan' => 7, 'nama' => 'Garansi Perangkat selama 1 tahun (termasuk perangkat & paket data GSM)', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 8, 'nama' => 'Penggantian Perangkat jika terjadi kerusakan selama masa garansi', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 9, 'nama' => 'Mobilisasi & Akomodasi ke lapangan untuk perawatan selama masa garansi, 1x kunjungan setiap 2 bulan', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000],
                ],
            ],
            [
                'id' => 6,
                'nama' => 'STESY Beacon Datalogger BL-1100',
                'satuan' => 'Unit',
                'is_active' => true,
                'details' => [
                    ['urutan' => 1, 'nama' => 'Datalogger BL-1100 System Series (lengkap dengan enclosure IP67)', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 24000000, 'subtotal' => 24000000],
                    ['urutan' => 2, 'nama' => 'Power Supply Converter AC/DC', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 20000000, 'subtotal' => 20000000],
                    ['urutan' => 3, 'nama' => 'Internet Communication Data Sender', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 4, 'nama' => 'Firmware embedded system', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 5, 'nama' => 'Web monitoring access and database storage cloud services', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 1500000, 'subtotal' => 1500000],
                ],
            ],
            [
                'id' => 7,
                'nama' => 'STESY Beacon Datalogger BL-110',
                'satuan' => 'Unit',
                'is_active' => true,
                'details' => [
                    ['urutan' => 1, 'nama' => 'Datalogger BL-110 System Series (lengkap dengan enclosure IP67)', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 24000000, 'subtotal' => 24000000],
                    ['urutan' => 2, 'nama' => 'Power Supply Converter AC/DC', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 20000000, 'subtotal' => 20000000],
                    ['urutan' => 3, 'nama' => 'Internet Communication Data Sender', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 4, 'nama' => 'Firmware embedded system', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 10000000, 'subtotal' => 10000000],
                    ['urutan' => 5, 'nama' => 'Web monitoring access and database storage cloud services', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 1500000, 'subtotal' => 1500000],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $details = $productData['details'];
            unset($productData['details']);

            $product = Product::create($productData);

            foreach ($details as $detail) {
                $detail['product_id'] = $product->id;
                ProductDetail::create($detail);
            }
        }

        // ========================
        // PENAWARAN TERM TEMPLATES
        // ========================
        $termTemplates = [
            ['id' => 8, 'parent_id' => null, 'urutan' => 1, 'judul' => null, 'isi' => 'Harga FOB Derah Istimewa Yogyakarta (DIY)'],
            ['id' => 9, 'parent_id' => null, 'urutan' => 2, 'judul' => null, 'isi' => 'Harga sudah termasuk biaya jasa instalasi dan aktivasi website monitoring'],
            ['id' => 10, 'parent_id' => null, 'urutan' => 3, 'judul' => null, 'isi' => 'Harga tidak termasuk pekerjaan fisik (konstruksi sipil / pembangunan pos)'],
            ['id' => 11, 'parent_id' => null, 'urutan' => 4, 'judul' => null, 'isi' => 'Harga dapat berubah apabila berubah lokasi dan kondisi yang mempengaruhi instalasi'],
            ['id' => 12, 'parent_id' => null, 'urutan' => 5, 'judul' => null, 'isi' => 'Harga sudah termasuk pengiriman logistik, pemasangan, testing, comissioning, programming, dan akomodasi'],
            ['id' => 13, 'parent_id' => null, 'urutan' => 6, 'judul' => null, 'isi' => 'Pembayaran DP bersifat wajib, tanpa menggunakan bank jaminan dan tanpa retensi'],
            ['id' => 16, 'parent_id' => null, 'urutan' => 7, 'judul' => null, 'isi' => 'Barang indent produksi estimasi 2 bulan setelah PO dan DP diterima'],
            ['id' => 17, 'parent_id' => null, 'urutan' => 8, 'judul' => null, 'isi' => 'Harga di atas sudah berdasarkan hasil negosiasi'],
            ['id' => 18, 'parent_id' => null, 'urutan' => 9, 'judul' => null, 'isi' => 'Harga sudah termasuk garansi peralatan selama 1 tahun'],
            ['id' => 19, 'parent_id' => null, 'urutan' => 10, 'judul' => null, 'isi' => 'Harga berlaku Januari 2026'],
        ];

        // Insert parent terms first
        foreach ($termTemplates as $template) {
            PenawaranTermTemplate::create($template);
        }

        // Insert child terms (parent_id = 13 for payment terms)
        $childTerms = [
            ['id' => 14, 'parent_id' => 13, 'urutan' => 1, 'judul' => null, 'isi' => 'Termin 1 : DP sebesar 20% setelah kontrak dan PO kami terima'],
            ['id' => 15, 'parent_id' => 13, 'urutan' => 2, 'judul' => null, 'isi' => 'Termin 2 : Pelunasan 80% setelah Material On Site'],
        ];

        foreach ($childTerms as $child) {
            PenawaranTermTemplate::create($child);
        }
    }
}
