<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('original_filename');
            $table->string('file_path');
            $table->longText('content');
            $table->unsignedBigInteger('uploaded_by');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->date('report_date')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index('report_date');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_reports');
    }
};
