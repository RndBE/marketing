<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usulan_penawaran', function (Blueprint $table) {
            $table->string('signature_name')->nullable();
            $table->string('signature_position')->nullable();
            $table->string('signature_city', 120)->nullable();
            $table->date('signature_date')->nullable();
            $table->string('signature_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usulan_penawaran', function (Blueprint $table) {
            $table->dropColumn([
                'signature_name',
                'signature_position',
                'signature_city',
                'signature_date',
                'signature_path',
            ]);
        });
    }
};
