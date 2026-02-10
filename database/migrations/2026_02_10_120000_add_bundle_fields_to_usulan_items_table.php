<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usulan_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('usulan_id')->constrained('products')->nullOnDelete();
            $table->string('tipe', 20)->default('custom')->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('usulan_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'tipe']);
        });
    }
};
