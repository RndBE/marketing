<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawaran_items', function (Blueprint $table) {
            $table->boolean('discount_enabled')->default(false)->after('subtotal');
            $table->string('discount_type', 20)->nullable()->after('discount_enabled'); // percent | fixed
            $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_items', function (Blueprint $table) {
            $table->dropColumn(['discount_enabled', 'discount_type', 'discount_value']);
        });
    }
};
