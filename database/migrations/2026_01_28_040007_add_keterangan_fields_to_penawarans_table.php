<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->string('instansi_tujuan')->nullable()->after('judul');
            $table->string('nama_pekerjaan')->nullable()->after('instansi_tujuan');
            $table->string('lokasi_pekerjaan')->nullable()->after('nama_pekerjaan');
            $table->date('tanggal_penawaran')->nullable()->after('lokasi_pekerjaan');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->dropColumn(['instansi_tujuan', 'nama_pekerjaan', 'lokasi_pekerjaan', 'tanggal_penawaran']);
        });
    }
};
