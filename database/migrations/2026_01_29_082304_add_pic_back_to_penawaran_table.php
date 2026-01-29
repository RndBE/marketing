<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->foreignId('id_pic')
                ->nullable()
                ->after('id') // boleh ubah posisi
                ->constrained('pics')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->dropForeign(['id_pic']);
            $table->dropColumn('id_pic');
        });
    }
};
