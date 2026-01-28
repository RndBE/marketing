<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('doc_numbers', function (Blueprint $table) {
            $table->string('doc_type')->nullable()->after('prefix');     // SPH02
            $table->string('user_code', 10)->nullable()->after('doc_type'); // AS
            $table->unsignedTinyInteger('month')->nullable()->after('user_code');
            $table->year('year')->nullable()->after('month');
        });
    }

    public function down(): void
    {
        Schema::table('doc_numbers', function (Blueprint $table) {
            $table->dropColumn(['doc_type', 'user_code', 'month', 'year']);
        });
    }
};
