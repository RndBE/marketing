<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('deskripsi');
        });

        Schema::table('komponen', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('spesifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('foto');
        });

        Schema::table('komponen', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
