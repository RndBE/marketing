<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Izin Purchase Order selama ini hanya dibuat oleh seeder, sedangkan deploy hanya
     * menjalankan migrate. Akibatnya di production barisnya tidak pernah ada, sehingga
     * seluruh rute PO menolak siapa pun -- termasuk admin -- dan izinnya juga tidak
     * muncul di halaman Kelola Permission untuk diberikan secara manual.
     *
     * Migration ini sengaja hanya membuat izinnya, tanpa melekatkannya ke role mana pun.
     * Pemberian ke role dilakukan lewat halaman Kelola Roles, supaya siapa yang boleh
     * mengakses Purchase Order tetap menjadi keputusan sadar, bukan efek samping deploy.
     */
    private const PERMISSIONS = [
        [
            'slug' => 'view-purchase-order',
            'name' => 'Lihat Purchase Order',
            'description' => 'Lihat daftar purchase order',
        ],
        [
            'slug' => 'create-purchase-order',
            'name' => 'Buat Purchase Order',
            'description' => 'Buat purchase order baru',
        ],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'group' => 'Purchase Order',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_column(self::PERMISSIONS, 'slug'))
            ->pluck('id');

        // Pemberian ke role dilepas lebih dulu agar tidak menyisakan baris menggantung
        // pada permission_role setelah izinnya dihapus.
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
