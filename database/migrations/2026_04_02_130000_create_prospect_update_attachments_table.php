<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_update_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_update_id')->constrained('prospect_updates')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index('prospect_update_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_update_attachments');
    }
};
