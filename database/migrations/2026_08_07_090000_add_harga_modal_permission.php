<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Izin untuk menu Harga Modal. Dibuat lewat migration, bukan seeder, karena
     * deploy production hanya menjalankan `migrate --force` -- izin yang hanya ada
     * di seeder tidak akan pernah terbentuk di sana.
     *
     * Sengaja tidak dilekatkan ke role mana pun. Yang ditampilkan halaman ini adalah
     * harga modal, jadi siapa yang boleh melihatnya harus jadi keputusan sadar lewat
     * halaman Kelola Roles, bukan efek samping deploy.
     */
    private const SLUG = 'view-harga-modal';

    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['slug' => self::SLUG],
            [
                'name' => 'Lihat Harga Modal',
                'description' => 'Lihat harga modal (HPP) dari inventory',
                'group' => 'Harga Modal',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', self::SLUG)->value('id');

        if ($permissionId === null) {
            return;
        }

        // Pemberian ke role dilepas lebih dulu supaya tidak menyisakan baris
        // menggantung di permission_role setelah izinnya hilang.
        DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
