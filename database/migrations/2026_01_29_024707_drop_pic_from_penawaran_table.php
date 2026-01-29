<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->dropForeign(['id_pic']);
            $table->dropColumn('id_pic');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->foreignId('id_pic')->after('id')->constrained('pics')->cascadeOnDelete();
        });
    }
};
