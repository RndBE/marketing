<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_po')->nullable()->unique();
            $table->string('judul');
            $table->string('supplier_nama');
            $table->text('supplier_alamat')->nullable();
            $table->date('tgl_po');
            $table->string('status')->default('draft');
            $table->decimal('total', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
