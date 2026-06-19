<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawaran_term_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('penawaran_term_templates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('isi');
            }

            if (!Schema::hasColumn('penawaran_term_templates', 'group_name')) {
                $table->string('group_name', 80)->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_term_templates', function (Blueprint $table) {
            if (Schema::hasColumn('penawaran_term_templates', 'group_name')) {
                $table->dropColumn('group_name');
            }

            if (Schema::hasColumn('penawaran_term_templates', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
