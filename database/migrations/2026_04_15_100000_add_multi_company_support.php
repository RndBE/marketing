<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        $primaryCompanyId = (int) (DB::table('companies')->insertGetId([
            'code' => 'ARSOL',
            'name' => 'CV. ARTA SOLUSINDO',
            'address' => 'Juwangen RT 10 RW 02 Purwomartani, Kalasan, Sleman, Daerah Istimewa Yogyakarta 55571',
            'email' => 'cv.artasolusindo@gmail.com',
            'phone' => '(0274) 5044026 / 085727868505',
            'logo_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]) ?: 1);

        DB::table('companies')->insert([
            'code' => 'COMPANY2',
            'name' => 'Perusahaan 2',
            'address' => null,
            'email' => null,
            'phone' => null,
            'logo_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tables = [
            'users',
            'penawaran',
            'invoices',
            'purchase_orders',
            'prospects',
            'usulan_penawaran',
            'laporan_perjalanan_marketing',
            'pics',
            'products',
            'komponen',
            'penawaran_term_templates',
            'invoice_signature_templates',
            'invoice_term_templates',
            'alur_penawaran',
            'doc_numbers',
            'audit_logs',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->index('company_id');
            });
        }

        $this->backfillUsers($primaryCompanyId);
        $this->backfillOwnedTables();
        $this->backfillLinkedTables($primaryCompanyId);
        $this->backfillGlobalTables($primaryCompanyId);
        $this->backfillRoles();
    }

    public function down(): void
    {
        $tables = [
            'audit_logs',
            'doc_numbers',
            'alur_penawaran',
            'invoice_term_templates',
            'invoice_signature_templates',
            'penawaran_term_templates',
            'komponen',
            'products',
            'pics',
            'laporan_perjalanan_marketing',
            'usulan_penawaran',
            'prospects',
            'purchase_orders',
            'invoices',
            'penawaran',
            'users',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }

        Schema::dropIfExists('companies');
    }

    private function backfillUsers(int $companyId): void
    {
        DB::table('users')
            ->whereNull('company_id')
            ->update(['company_id' => $companyId]);
    }

    private function backfillOwnedTables(): void
    {
        $this->copyCompanyFromUser('penawaran', 'id_user');
        $this->copyCompanyFromUser('invoices', 'user_id');
        $this->copyCompanyFromUser('purchase_orders', 'user_id');
        $this->copyCompanyFromUser('prospects', 'created_by');
        $this->copyCompanyFromUser('usulan_penawaran', 'created_by');
        $this->copyCompanyFromUser('laporan_perjalanan_marketing', 'created_by');
        $this->copyCompanyFromUser('audit_logs', 'user_id');
        $this->copyCompanyFromUser('alur_penawaran', 'dibuat_oleh');
    }

    private function backfillLinkedTables(int $defaultCompanyId): void
    {
        DB::table('invoices')
            ->whereNull('company_id')
            ->orderBy('id')
            ->select(['id', 'penawaran_id'])
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $companyId = $row->penawaran_id
                        ? DB::table('penawaran')->where('id', $row->penawaran_id)->value('company_id')
                        : null;

                    if ($companyId) {
                        DB::table('invoices')->where('id', $row->id)->update(['company_id' => $companyId]);
                    }
                }
            });

        DB::table('usulan_penawaran')
            ->whereNull('company_id')
            ->orderBy('id')
            ->select(['id', 'penawaran_id', 'prospect_id'])
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $companyId = null;

                    if ($row->penawaran_id) {
                        $companyId = DB::table('penawaran')->where('id', $row->penawaran_id)->value('company_id');
                    }

                    if (!$companyId && $row->prospect_id) {
                        $companyId = DB::table('prospects')->where('id', $row->prospect_id)->value('company_id');
                    }

                    if ($companyId) {
                        DB::table('usulan_penawaran')->where('id', $row->id)->update(['company_id' => $companyId]);
                    }
                }
            });

        DB::table('doc_numbers')
            ->whereNull('company_id')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $companyId = DB::table('penawaran')
                        ->where('doc_number_id', $row->id)
                        ->value('company_id');

                    if (!$companyId) {
                        $companyId = DB::table('invoices')
                            ->where('doc_number_id', $row->id)
                            ->value('company_id');
                    }

                    if ($companyId) {
                        DB::table('doc_numbers')->where('id', $row->id)->update(['company_id' => $companyId]);
                    }
                }
            });

        DB::table('doc_numbers')
            ->whereNull('company_id')
            ->update(['company_id' => $defaultCompanyId]);
    }

    private function backfillGlobalTables(int $companyId): void
    {
        foreach ([
            'pics',
            'products',
            'komponen',
            'penawaran_term_templates',
            'invoice_signature_templates',
            'invoice_term_templates',
            'alur_penawaran',
        ] as $tableName) {
            DB::table($tableName)
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }

        DB::table('audit_logs')
            ->whereNull('company_id')
            ->update(['company_id' => $companyId]);
    }

    private function backfillRoles(): void
    {
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        if (!$adminRoleId) {
            $adminRoleId = DB::table('roles')->insertGetId([
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrator dengan akses lintas seluruh perusahaan',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionIds = DB::table('permissions')->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $adminRoleId, 'permission_id' => $permissionId],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }

        $adminUserId = DB::table('users')
            ->where('email', 'superadmin@gmail.com')
            ->value('id');

        if ($adminUserId) {
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $adminUserId, 'role_id' => $adminRoleId],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    private function copyCompanyFromUser(string $tableName, string $userColumn): void
    {
        DB::table($tableName)
            ->whereNull('company_id')
            ->whereNotNull($userColumn)
            ->orderBy('id')
            ->select(['id', $userColumn])
            ->chunkById(100, function ($rows) use ($tableName, $userColumn) {
                foreach ($rows as $row) {
                    $companyId = DB::table('users')
                        ->where('id', $row->{$userColumn})
                        ->value('company_id');

                    if ($companyId) {
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update(['company_id' => $companyId]);
                    }
                }
            });
    }
};
