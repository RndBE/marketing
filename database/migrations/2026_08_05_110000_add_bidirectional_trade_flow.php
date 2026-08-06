<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumnUnlessExists('usulan_penawaran', 'target_company_id', function (Blueprint $table) {
            $table->foreignId('target_company_id')->nullable()->after('company_id')->constrained('companies')->nullOnDelete();
        });
        $this->addColumnUnlessExists('usulan_penawaran', 'jenis_transaksi', function (Blueprint $table) {
            $table->string('jenis_transaksi', 20)->default('barang')->after('judul');
        });
        $this->addColumnUnlessExists('usulan_penawaran', 'penawaran_status', function (Blueprint $table) {
            $table->string('penawaran_status', 30)->default('none')->after('penawaran_id');
        });
        $this->addColumnUnlessExists('usulan_penawaran', 'penawaran_tanggapan', function (Blueprint $table) {
            $table->text('penawaran_tanggapan')->nullable()->after('penawaran_status');
        });

        if (! Schema::hasIndex('usulan_penawaran', ['target_company_id', 'status'])) {
            Schema::table('usulan_penawaran', fn (Blueprint $table) => $table->index(['target_company_id', 'status']));
        }

        $this->addColumnUnlessExists('purchase_orders', 'supplier_company_id', function (Blueprint $table) {
            $table->foreignId('supplier_company_id')->nullable()->after('company_id')->constrained('companies')->nullOnDelete();
        });
        $this->addColumnUnlessExists('purchase_orders', 'usulan_id', function (Blueprint $table) {
            $table->foreignId('usulan_id')->nullable()->after('supplier_company_id')->constrained('usulan_penawaran')->nullOnDelete();
        });
        $this->addColumnUnlessExists('purchase_orders', 'penawaran_id', function (Blueprint $table) {
            $table->foreignId('penawaran_id')->nullable()->after('usulan_id')->constrained('penawaran')->nullOnDelete();
        });
        $this->addColumnUnlessExists('purchase_orders', 'verified_by', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
        $this->addColumnUnlessExists('purchase_orders', 'verified_at', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
        $this->addColumnUnlessExists('purchase_orders', 'verification_notes', function (Blueprint $table) {
            $table->text('verification_notes')->nullable()->after('verified_at');
        });

        if (! Schema::hasIndex('purchase_orders', ['supplier_company_id', 'status'])) {
            Schema::table('purchase_orders', fn (Blueprint $table) => $table->index(['supplier_company_id', 'status']));
        }

        $this->addColumnUnlessExists('purchase_order_terms', 'payment_verification_status', function (Blueprint $table) {
            $table->string('payment_verification_status', 30)->default('none')->after('status');
        });
        $this->addColumnUnlessExists('purchase_order_terms', 'payment_verification_notes', function (Blueprint $table) {
            $table->text('payment_verification_notes')->nullable()->after('payment_verification_status');
        });
        $this->addColumnUnlessExists('purchase_order_terms', 'payment_verified_by', function (Blueprint $table) {
            $table->foreignId('payment_verified_by')->nullable()->after('payment_verification_notes')->constrained('users')->nullOnDelete();
        });
        $this->addColumnUnlessExists('purchase_order_terms', 'payment_verified_at', function (Blueprint $table) {
            $table->timestamp('payment_verified_at')->nullable()->after('payment_verified_by');
        });
    }

    private function addColumnUnlessExists(string $tableName, string $column, callable $definition): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            Schema::table($tableName, $definition);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_order_terms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_verified_by');
            $table->dropColumn(['payment_verification_status', 'payment_verification_notes', 'payment_verified_at']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropConstrainedForeignId('penawaran_id');
            $table->dropConstrainedForeignId('usulan_id');
            $table->dropConstrainedForeignId('supplier_company_id');
            $table->dropColumn(['verified_at', 'verification_notes']);
        });

        Schema::table('usulan_penawaran', function (Blueprint $table) {
            $table->dropIndex(['target_company_id', 'status']);
            $table->dropConstrainedForeignId('target_company_id');
            $table->dropColumn(['jenis_transaksi', 'penawaran_status', 'penawaran_tanggapan']);
        });
    }
};
