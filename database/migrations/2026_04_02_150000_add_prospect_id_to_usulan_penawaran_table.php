<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usulan_penawaran', function (Blueprint $table) {
            $table->foreignId('prospect_id')->nullable()->after('pic_id')->constrained('prospects')->nullOnDelete();
            $table->index(['prospect_id']);
        });

        DB::table('usulan_penawaran')
            ->whereNotNull('penawaran_id')
            ->orderBy('id')
            ->select(['id', 'penawaran_id'])
            ->chunkById(100, function ($usulans) {
                foreach ($usulans as $usulan) {
                    $prospectId = DB::table('penawaran')
                        ->where('id', $usulan->penawaran_id)
                        ->value('prospect_id');

                    if ($prospectId) {
                        DB::table('usulan_penawaran')
                            ->where('id', $usulan->id)
                            ->update(['prospect_id' => $prospectId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('usulan_penawaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prospect_id');
        });
    }
};
