<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_lead')->nullable();
            $table->string('instansi');
            $table->foreignId('pic_id')->nullable()->constrained('pics')->nullOnDelete();
            $table->string('nama_pic')->nullable();
            $table->string('jabatan_pic')->nullable();
            $table->string('no_hp', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('sumber_lead', 100)->nullable();
            $table->string('produk')->nullable();
            $table->text('kebutuhan')->nullable();
            $table->bigInteger('potensi_nilai')->default(0);
            $table->string('status', 50)->default('new');
            $table->date('last_follow_up_at')->nullable();
            $table->date('next_follow_up_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('penawaran_id')->nullable()->constrained('penawaran')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->string('hasil_akhir', 50)->default('in_progress');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['assigned_to']);
            $table->index(['next_follow_up_at']);
            $table->index(['penawaran_id']);
        });

        Schema::create('prospect_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained('prospects')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('aktivitas');
            $table->string('status', 50);
            $table->date('next_follow_up_at')->nullable();
            $table->string('hasil_akhir', 50)->default('in_progress');
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['prospect_id', 'tanggal']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_updates');
        Schema::dropIfExists('prospects');
    }
};
