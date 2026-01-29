<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pics', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('instansi')->nullable();
            $table->string('email')->nullable();
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('module')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('current_step')->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('doc_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20);
            $table->unsignedBigInteger('seq')->default(1);
            $table->string('doc_no')->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable()->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('nama');
            $table->text('spesifikasi')->nullable();
            $table->decimal('qty', 12, 2)->default(1);
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('harga')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'urutan']);
        });

        Schema::create('penawaran', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('id_pic')->constrained('pics')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->foreignId('doc_number_id')->nullable()->constrained('doc_numbers')->nullOnDelete();
            $table->foreignId('approval_id')->nullable()->constrained('approvals')->nullOnDelete();
            $table->string('judul')->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('date_created')->nullable();
            $table->unsignedBigInteger('date_updated')->nullable();
            $table->timestamps();
        });

        Schema::create('penawaran_cover', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_id')->constrained('penawaran')->cascadeOnDelete();
            $table->string('judul_cover')->nullable();
            $table->string('subjudul')->nullable();
            $table->string('perusahaan_nama')->nullable();
            $table->text('perusahaan_alamat')->nullable();
            $table->string('perusahaan_email')->nullable();
            $table->string('perusahaan_telp')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('intro_text')->nullable();
            $table->timestamps();
            $table->unique('penawaran_id');
        });

        Schema::create('penawaran_validity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_id')->constrained('penawaran')->cascadeOnDelete();
            $table->date('mulai')->nullable();
            $table->date('sampai')->nullable();
            $table->integer('berlaku_hari')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unique('penawaran_id');
        });

        Schema::create('penawaran_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_id')->constrained('penawaran')->cascadeOnDelete();
            $table->string('tipe')->default('bundle');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('judul');
            $table->text('catatan')->nullable();
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
            $table->index(['penawaran_id', 'urutan']);
        });

        Schema::create('penawaran_item_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_item_id')->constrained('penawaran_items')->cascadeOnDelete();
            $table->foreignId('product_detail_id')->nullable()->constrained('product_details')->nullOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('nama');
            $table->text('spesifikasi')->nullable();
            $table->decimal('qty', 12, 2)->default(1);
            $table->string('satuan', 50)->nullable();
            $table->bigInteger('harga')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
            $table->index(['penawaran_item_id', 'urutan']);
        });

        Schema::create('penawaran_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_id')->constrained('penawaran')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('penawaran_terms')->cascadeOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('judul')->nullable();
            $table->text('isi');
            $table->timestamps();
            $table->index(['penawaran_id', 'urutan']);
            $table->index(['parent_id']);
        });

        Schema::create('penawaran_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_id')->constrained('penawaran')->cascadeOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('kota')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('ttd_path')->nullable();
            $table->timestamps();
            $table->index(['penawaran_id', 'urutan']);
        });

        Schema::create('penawaran_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_id')->constrained('penawaran')->cascadeOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('judul')->nullable();
            $table->string('file_path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
            $table->index(['penawaran_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran_attachments');
        Schema::dropIfExists('penawaran_signatures');
        Schema::dropIfExists('penawaran_terms');
        Schema::dropIfExists('penawaran_item_details');
        Schema::dropIfExists('penawaran_items');
        Schema::dropIfExists('penawaran_validity');
        Schema::dropIfExists('penawaran_cover');
        Schema::dropIfExists('penawaran');
        Schema::dropIfExists('product_details');
        Schema::dropIfExists('products');
        Schema::dropIfExists('doc_numbers');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('pics');
    }
};
