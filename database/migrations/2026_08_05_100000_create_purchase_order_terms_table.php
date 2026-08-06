<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('jenis_transaksi', 20)->default('barang')->after('status');
            $table->string('po_file_path')->nullable()->after('catatan');
        });

        Schema::create('purchase_order_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->unsignedInteger('pembayaran_ke');
            $table->date('tanggal_jatuh_tempo');
            $table->decimal('nilai_tagihan', 15, 2);

            $table->string('nomor_invoice')->nullable();
            $table->date('tanggal_invoice')->nullable();
            $table->string('invoice_path')->nullable();

            $table->string('nomor_faktur')->nullable();
            $table->string('faktur_path')->nullable();

            $table->date('tanggal_bayar')->nullable();
            $table->decimal('nilai_dibayar', 15, 2)->default(0);
            $table->string('bukti_bayar_path')->nullable();

            $table->string('jenis_pph', 30)->nullable();
            $table->decimal('nilai_pph', 15, 2)->default(0);
            $table->string('bukti_potong_pph_path')->nullable();

            $table->string('status', 30)->default('draft');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['purchase_order_id', 'pembayaran_ke'], 'po_terms_installment_unique');
            $table->index(['purchase_order_id', 'tanggal_jatuh_tempo']);
            $table->index(['purchase_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_terms');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['jenis_transaksi', 'po_file_path']);
        });
    }
};
