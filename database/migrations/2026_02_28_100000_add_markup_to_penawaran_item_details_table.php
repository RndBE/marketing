<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawaran_item_details', function (Blueprint $table) {
            $table->decimal('markup', 5, 2)->default(1.00)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_item_details', function (Blueprint $table) {
            $table->dropColumn('markup');
        });
    }
};
