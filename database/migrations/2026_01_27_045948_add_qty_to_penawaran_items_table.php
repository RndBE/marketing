<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawaran_items', function (Blueprint $table) {
            $table->decimal('qty', 12, 2)->default(1)->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_items', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
