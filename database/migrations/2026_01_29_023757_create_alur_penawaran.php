<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alur_penawaran', function (Blueprint $table) {
            // $table->id();
            // $table->timestamps();
            $table->id();
            // $table->foreignId('instansi_id')->constrained('instansi');
            // $table->string('kode', 80);
            $table->string('nama', 200);
            $table->string('berlaku_untuk', 80);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diubah_pada')->useCurrent()->useCurrentOnUpdate();
            // $table->unique(['instansi_id', 'kode'], 'uq_alur_kode');
        });

        Schema::create('langkah_alur_penawaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alur_penawaran_id')->constrained('alur_penawaran')->cascadeOnDelete();
            $table->unsignedInteger('no_langkah');
            $table->string('nama_langkah', 200);
            // $table->enum('tipe_penyetuju', ['peran', 'pengguna', 'unit_peran'])->default('peran');
            // $table->foreignId('peran_id')->nullable()->constrained('peran')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // $table->foreignId('unit_organisasi_id')->nullable()->constrained('unit_organisasi')->nullOnDelete();
            $table->boolean('harus_semua')->default(true);
            $table->json('kondisi')->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diubah_pada')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['alur_penawaran_id', 'no_langkah'], 'uq_langkah_alur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('langkah_alur_penawaran');
        Schema::dropIfExists('alur_penawaran');
    }
};
