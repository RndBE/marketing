<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->boolean('discount_enabled')->default(false)->after('catatan');
            $table->string('discount_type', 10)->nullable()->after('discount_enabled');
            $table->decimal('discount_value', 12, 2)->nullable()->after('discount_type');

            $table->boolean('tax_enabled')->default(false)->after('discount_value');
            $table->decimal('tax_rate', 5, 2)->nullable()->after('tax_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->dropColumn([
                'discount_enabled',
                'discount_type',
                'discount_value',
                'tax_enabled',
                'tax_rate',
            ]);
        });
    }
};
