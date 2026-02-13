<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_perjalanan_marketing', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_laporan')->nullable()->unique();
            $table->date('tanggal_pertemuan');
            $table->time('waktu_pertemuan')->nullable();
            $table->string('tempat_pertemuan');
            $table->string('instansi')->nullable();
            $table->text('pihak_ditemui');
            $table->text('peserta_internal')->nullable();
            $table->text('topik_pembahasan');
            $table->text('hasil_pertemuan')->nullable();
            $table->text('rencana_tindak_lanjut')->nullable();
            $table->date('target_tindak_lanjut')->nullable();
            $table->enum('status', ['draft', 'follow_up', 'selesai'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_perjalanan_marketing');
    }
};

