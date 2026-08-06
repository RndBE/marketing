<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // 'internal'       : pembeli maupun penjual adalah perusahaan di sistem,
            //                    atau pembelian ke pemasok luar (perilaku lama).
            // 'pelanggan_luar' : PO diterima dari pelanggan di luar sistem. Perusahaan
            //                    pemilik data bertindak sebagai penjual.
            $table->string('sumber', 20)->default('internal')->after('status');
            $table->string('pembeli_nama')->nullable()->after('sumber');
            $table->text('pembeli_alamat')->nullable()->after('pembeli_nama');
            $table->index(['company_id', 'sumber']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'sumber']);
            $table->dropColumn(['sumber', 'pembeli_nama', 'pembeli_alamat']);
        });
    }
};
