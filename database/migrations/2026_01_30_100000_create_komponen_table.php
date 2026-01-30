<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('komponen', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->nullable()->unique();
            $table->string('nama');
            $table->text('spesifikasi')->nullable();
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('harga')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komponen');
    }
};
