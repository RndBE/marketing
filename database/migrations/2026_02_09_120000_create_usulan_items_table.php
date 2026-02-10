<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usulan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->constrained('usulan_penawaran')->cascadeOnDelete();
            $table->unsignedInteger('urutan')->default(1);
            $table->string('judul');
            $table->text('catatan')->nullable();
            $table->decimal('qty', 12, 2)->default(1);
            $table->string('satuan')->nullable();
            $table->bigInteger('harga')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usulan_items');
    }
};
