<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->boolean('is_goal')->default(false)->after('tax_rate');
            $table->timestamp('goal_at')->nullable()->after('is_goal');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->dropColumn(['is_goal', 'goal_at']);
        });
    }
};
