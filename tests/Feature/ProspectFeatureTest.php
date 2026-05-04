<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Prospect;
use App\Models\Role;
use App\Models\User;

function prospectTestCompany(string $code = 'PROSPECT-CO'): Company
{
    return Company::firstOrCreate(
        ['code' => $code],
        ['name' => 'Prospect Company ' . $code]
    );
}

function prospectUserWithPermissions(array $permissionSlugs, ?Company $company = null): User
{
    $company ??= prospectTestCompany();
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Prospect Tester ' . uniqid(),
        'slug' => 'prospect-tester-' . uniqid(),
    ]);

    foreach ($permissionSlugs as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Prospek']
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $user->roles()->attach($role->id);

    return $user;
}

test('prospect index requires permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('prospects.index'))
        ->assertForbidden();
});

test('authorized user can create prospect and append progress update', function () {
    $user = prospectUserWithPermissions([
        'view-prospect',
        'create-prospect',
        'edit-prospect',
    ]);

    $this->actingAs($user)
        ->post(route('prospects.store'), [
            'tanggal_lead' => '2026-04-02',
            'judul' => 'Pengadaan Telemetri AWLR',
            'instansi' => 'BBWS Serayu Opak',
            'nama_pic' => 'Pak Idris',
            'jabatan_pic' => 'PPK',
            'no_hp' => '08123456788',
            'email' => 'prospek@example.com',
            'lokasi' => 'D.I. Yogyakarta',
            'sumber_lead' => 'Referral',
            'produk' => 'Telemetri AWLR',
            'kebutuhan' => 'JIAT & ATAB',
            'potensi_nilai' => 3000000000,
            'status' => 'proposal_sent',
            'hasil_akhir' => 'in_progress',
            'next_follow_up_at' => '2026-04-05',
            'catatan' => 'Proposal sudah dikirim.',
        ])
        ->assertRedirect();

    $prospect = Prospect::first();

    expect($prospect)->not()->toBeNull();
    expect($prospect->judul)->toBe('Pengadaan Telemetri AWLR');
    expect($prospect->instansi)->toBe('BBWS Serayu Opak');
    expect($prospect->updates()->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('prospects.updates.store', $prospect), [
            'tanggal' => '2026-04-03',
            'aktivitas' => 'Follow up WhatsApp',
            'status' => 'waiting_decision',
            'hasil_akhir' => 'in_progress',
            'next_follow_up_at' => '2026-04-07',
            'catatan' => 'Menunggu review dari pihak instansi.',
        ])
        ->assertRedirect(route('prospects.show', $prospect));

    $prospect->refresh();

    $this->assertDatabaseHas('prospects', [
        'id' => $prospect->id,
        'status' => 'waiting_decision',
        'hasil_akhir' => 'in_progress',
    ]);

    $this->assertDatabaseHas('prospect_updates', [
        'prospect_id' => $prospect->id,
        'aktivitas' => 'Follow up WhatsApp',
        'status' => 'waiting_decision',
    ]);
});

test('user with edit prospect permission can update prospect from another company', function () {
    $originCompany = prospectTestCompany('PROSPECT-ORIGIN');
    $editorCompany = prospectTestCompany('PROSPECT-EDITOR');
    $creator = prospectUserWithPermissions(['view-prospect', 'edit-prospect'], $originCompany);
    $editor = prospectUserWithPermissions(['view-prospect', 'edit-prospect', 'create-penawaran'], $editorCompany);

    $prospect = Prospect::create([
        'company_id' => $originCompany->id,
        'tanggal_lead' => '2026-04-02',
        'judul' => 'Prospek Lintas Company',
        'instansi' => 'Instansi Awal',
        'produk' => 'Telemetri',
        'potensi_nilai' => 1000000,
        'status' => 'new',
        'hasil_akhir' => 'in_progress',
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    $this->actingAs($editor)
        ->get(route('prospects.edit', $prospect))
        ->assertOk();

    $this->actingAs($editor)
        ->put(route('prospects.update', $prospect), [
            'tanggal_lead' => '2026-04-02',
            'judul' => 'Prospek Lintas Company Updated',
            'instansi' => 'Instansi Baru',
            'nama_pic' => 'Bu Sari',
            'jabatan_pic' => 'PPK',
            'no_hp' => '08123456789',
            'email' => 'sari@example.com',
            'lokasi' => 'Jakarta',
            'sumber_lead' => 'Referral',
            'produk' => 'Telemetri AWLR',
            'kebutuhan' => 'Monitoring debit sungai',
            'potensi_nilai' => 2500000,
            'status' => 'contacted',
            'hasil_akhir' => 'in_progress',
            'last_follow_up_at' => '2026-04-04',
            'next_follow_up_at' => '2026-04-10',
            'catatan' => 'Sudah dihubungi dari company lain.',
        ])
        ->assertRedirect(route('prospects.show', $prospect));

    $this->actingAs($editor)
        ->post(route('prospects.updates.store', $prospect), [
            'tanggal' => '2026-04-05',
            'aktivitas' => 'Follow up lintas company',
            'status' => 'waiting_decision',
            'hasil_akhir' => 'in_progress',
            'next_follow_up_at' => '2026-04-12',
            'catatan' => 'Menunggu jadwal meeting.',
        ])
        ->assertRedirect(route('prospects.show', $prospect));

    $this->assertDatabaseHas('prospects', [
        'id' => $prospect->id,
        'company_id' => $originCompany->id,
        'judul' => 'Prospek Lintas Company Updated',
        'status' => 'waiting_decision',
        'updated_by' => $editor->id,
    ]);

    $this->assertDatabaseHas('prospect_updates', [
        'prospect_id' => $prospect->id,
        'aktivitas' => 'Follow up lintas company',
        'user_id' => $editor->id,
    ]);

    $this->actingAs($editor)
        ->post(route('prospects.buat-penawaran', $prospect))
        ->assertForbidden();
});
