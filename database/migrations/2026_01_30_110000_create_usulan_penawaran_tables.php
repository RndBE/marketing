<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usulan_penawaran', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->foreignId('pic_id')->nullable()->constrained('pics')->nullOnDelete();
            $table->text('deskripsi')->nullable();
            $table->bigInteger('nilai_estimasi')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'menunggu', 'ditanggapi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('tanggapan')->nullable();
            $table->foreignId('ditanggapi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_ditanggapi')->nullable();
            $table->date('tanggal_dibutuhkan')->nullable();
            $table->foreignId('penawaran_id')->nullable()->constrained('penawaran')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('usulan_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->constrained('usulan_penawaran')->cascadeOnDelete();
            $table->string('nama_file');
            $table->string('path');
            $table->string('tipe')->default('dokumen'); // survei, dokumen, foto, dll
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usulan_attachments');
        Schema::dropIfExists('usulan_penawaran');
    }
};
