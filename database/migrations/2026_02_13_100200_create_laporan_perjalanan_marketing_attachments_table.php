<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_perjalanan_marketing_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan_perjalanan_marketing')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_perjalanan_marketing_attachments');
    }
};
