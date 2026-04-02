<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('judul')->nullable()->after('tanggal_lead');
        });

        DB::table('prospects')
            ->whereNull('judul')
            ->update([
                'judul' => DB::raw("COALESCE(NULLIF(instansi, ''), 'Prospek Baru')"),
            ]);

        Schema::table('prospects', function (Blueprint $table) {
            $table->string('judul')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn('judul');
        });
    }
};
