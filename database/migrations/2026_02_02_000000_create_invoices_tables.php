<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('doc_number_id')->nullable()->constrained('doc_numbers')->nullOnDelete();
            // Referral to penawaran if generated from one? Optional but good.
            $table->foreignId('penawaran_id')->nullable()->constrained('penawaran')->nullOnDelete();

            $table->string('judul')->nullable(); // Invoice Title
            $table->text('catatan')->nullable();

            $table->string('status')->default('draft'); // draft, sent, paid, cancelled, overdue

            $table->date('tgl_invoice')->nullable();
            $table->date('jatuh_tempo')->nullable();

            // Financials
            $table->bigInteger('subtotal')->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('grand_total')->default(0);

            // Customer info snapshot (optional, or just rely on penawaran/user)
            // For now keeping it simple, maybe add if needed.

            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('tipe')->default('bundle'); // bundle, custom
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('judul');
            $table->text('catatan')->nullable();
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
            $table->index(['invoice_id', 'urutan']);
        });

        Schema::create('invoice_item_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_item_id')->constrained('invoice_items')->cascadeOnDelete();
            $table->foreignId('product_detail_id')->nullable()->constrained('product_details')->nullOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('nama');
            $table->text('spesifikasi')->nullable();
            $table->decimal('qty', 12, 2)->default(1);
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('harga')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
            $table->index(['invoice_item_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_item_details');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
