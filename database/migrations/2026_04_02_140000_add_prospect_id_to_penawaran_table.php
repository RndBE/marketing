<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->foreignId('prospect_id')->nullable()->after('id_user')->constrained('prospects')->nullOnDelete();
            $table->index(['prospect_id']);
        });

        DB::table('prospects')
            ->whereNotNull('penawaran_id')
            ->orderBy('id')
            ->select(['id', 'penawaran_id'])
            ->chunkById(100, function ($prospects) {
                foreach ($prospects as $prospect) {
                    DB::table('penawaran')
                        ->where('id', $prospect->penawaran_id)
                        ->whereNull('prospect_id')
                        ->update(['prospect_id' => $prospect->id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prospect_id');
        });
    }
};
