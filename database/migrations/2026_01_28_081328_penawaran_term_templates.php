<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penawaran_term_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('urutan')->default(1);
            $table->string('judul')->nullable();
            $table->text('isi');
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('penawaran_term_templates')
                ->cascadeOnDelete();

            $table->index(['parent_id', 'urutan', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran_term_templates');
    }
};
