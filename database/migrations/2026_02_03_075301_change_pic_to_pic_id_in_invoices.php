<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('pic');
            $table->foreignId('pic_id')->nullable()->after('user_id')->constrained('pics')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['pic_id']);
            $table->dropColumn('pic_id');
            $table->string('pic')->nullable()->after('user_id');
        });
    }
};
