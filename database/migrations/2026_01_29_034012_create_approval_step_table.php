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
        Schema::create('approval_step', function (Blueprint $table) {
            // $table->id();
            // $table->timestamps();
            $table->id();
            $table->foreignId('approval_id')->constrained('approvals')->cascadeOnDelete();

            $table->unsignedInteger('step_order'); // 1,2,3...
            $table->string('step_name'); // Teknis, Finance, Direksi

            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan')->nullable();
            $table->json('akses_approve')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_step');
    }
};
