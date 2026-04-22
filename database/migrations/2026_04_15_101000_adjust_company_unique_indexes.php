<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_kode_unique');
            $table->unique(['company_id', 'kode'], 'products_company_kode_unique');
        });

        Schema::table('komponen', function (Blueprint $table) {
            $table->dropUnique('komponen_kode_unique');
            $table->unique(['company_id', 'kode'], 'komponen_company_kode_unique');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique('purchase_orders_nomor_po_unique');
            $table->unique(['company_id', 'nomor_po'], 'purchase_orders_company_nomor_po_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_company_kode_unique');
            $table->unique('kode');
        });

        Schema::table('komponen', function (Blueprint $table) {
            $table->dropUnique('komponen_company_kode_unique');
            $table->unique('kode');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique('purchase_orders_company_nomor_po_unique');
            $table->unique('nomor_po');
        });
    }
};
