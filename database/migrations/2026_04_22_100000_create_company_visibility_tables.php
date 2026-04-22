<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penawaran_company_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_id')->constrained('penawaran')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['penawaran_id', 'company_id'], 'penawaran_company_visibility_unique');
        });

        Schema::create('usulan_penawaran_company_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_penawaran_id')->constrained('usulan_penawaran')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['usulan_penawaran_id', 'company_id'], 'usulan_penawaran_company_visibility_unique');
        });

        Schema::create('prospect_company_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained('prospects')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['prospect_id', 'company_id'], 'prospect_company_visibility_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_company_visibility');
        Schema::dropIfExists('usulan_penawaran_company_visibility');
        Schema::dropIfExists('penawaran_company_visibility');
    }
};
