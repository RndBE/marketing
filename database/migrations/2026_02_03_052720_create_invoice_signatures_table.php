<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('nama');
            $table->string('jabatan');
            $table->string('kota')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('ttd_path')->nullable(); // path to image
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_signatures');
    }
};
