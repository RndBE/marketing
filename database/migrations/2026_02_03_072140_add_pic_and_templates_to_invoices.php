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
            $table->string('pic')->nullable()->after('user_id');
        });

        Schema::create('invoice_signature_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name'); // e.g., "Direktur Utama", "Marketing Senior"
            $table->string('nama');
            $table->string('jabatan');
            $table->string('kota')->default('Sleman');
            $table->string('ttd_path')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_term_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name'); // e.g., "Syarat Umum", "Syarat Proyek Khusus"
            $table->text('isi'); // JSON or formatted text? Using text for single term or maybe multiline. 
            // User said "Terms", usually a list. 
            // Let's make it a simple list of terms sharing a template_id? 
            // OR one record properly formatted? 
            // Existing InvoiceTerms are individual records.
            // Let's simplify: A Template has MANY terms.
            // So `invoice_term_templates` (header) and `invoice_term_template_items` (items).
            // OR just store JSON in `isi` and parse it to create terms. JSON is easier for now.
            $table->json('terms'); // Array of strings ["Term 1", "Term 2"]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            //
        });
    }
};
