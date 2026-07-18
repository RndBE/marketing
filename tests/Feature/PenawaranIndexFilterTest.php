<?php

use App\Models\Approval;
use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

function penawaranIndexCompany(string $code): Company
{
    return Company::firstOrCreate(
        ['code' => $code],
        ['name' => 'Penawaran Company '.$code]
    );
}

function penawaranIndexUserWithPermissions(Company $company, array $permissionSlugs): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Penawaran Index Tester '.uniqid(),
        'slug' => 'penawaran-index-tester-'.uniqid(),
    ]);

    foreach ($permissionSlugs as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Penawaran']
        );

        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}

function penawaranIndexCreateOffer(Company $company, User $owner, string $title, int $year, int $month, int $seq): Penawaran
{
    $docNumber = DocNumber::create([
        'company_id' => $company->id,
        'prefix' => 'PNW',
        'doc_type' => 'penawaran',
        'user_code' => 'USR',
        'seq' => $seq,
        'month' => $month,
        'year' => $year,
        'doc_no' => sprintf('%03d/USR/%s/%02d/%d', $seq, $company->code, $month, $year),
    ]);

    return Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $owner->id,
        'doc_number_id' => $docNumber->id,
        'judul' => $title,
        'instansi_tujuan' => 'Instansi Test',
        'nama_pekerjaan' => 'Pekerjaan Test',
        'lokasi_pekerjaan' => 'Yogyakarta',
        'tanggal_penawaran' => "{$year}-{$month}-01",
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
}

function penawaranIndexAttachApproval(Penawaran $penawaran, string $status, string $module = 'penawaran'): Approval
{
    $approval = Approval::create([
        'module' => $module,
        'ref_id' => $penawaran->id,
        'status' => $status,
        'current_step' => 1,
    ]);

    $penawaran->update(['approval_id' => $approval->id]);

    return $approval;
}

test('user with view all penawaran can see all companies and filter by company', function () {
    $cv = penawaranIndexCompany('AS');
    $pt = penawaranIndexCompany('ATC');
    $cvOwner = User::factory()->create(['company_id' => $cv->id]);
    $ptViewer = penawaranIndexUserWithPermissions($pt, ['view-all-penawaran']);

    penawaranIndexCreateOffer($cv, $cvOwner, 'Penawaran CV Terlihat', 2026, 7, 1);
    penawaranIndexCreateOffer($pt, $ptViewer, 'Penawaran PT Terlihat', 2026, 7, 2);

    $this->actingAs($ptViewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSee('Penawaran CV Terlihat')
        ->assertSee('Penawaran PT Terlihat');

    $this->actingAs($ptViewer)
        ->get(route('penawaran.index', ['company_id' => $pt->id]))
        ->assertOk()
        ->assertDontSee('Penawaran CV Terlihat')
        ->assertSee('Penawaran PT Terlihat');
});

test('penawaran index orders newest document number first by year month and sequence', function () {
    $company = penawaranIndexCompany('ORDER-AS');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);

    penawaranIndexCreateOffer($company, $viewer, 'Penawaran Lama Seq Besar', 2025, 12, 999);
    penawaranIndexCreateOffer($company, $viewer, 'Penawaran Baru Seq Kecil', 2026, 1, 1);

    $this->actingAs($viewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'Penawaran Baru Seq Kecil',
            'Penawaran Lama Seq Besar',
        ]);
});

test('penawaran index prioritizes logged in users company before other companies', function () {
    $cv = penawaranIndexCompany('PRIO-AS');
    $pt = penawaranIndexCompany('PRIO-ATC');
    $cvOwner = User::factory()->create(['company_id' => $cv->id]);
    $ptViewer = penawaranIndexUserWithPermissions($pt, ['view-all-penawaran']);

    penawaranIndexCreateOffer($cv, $cvOwner, 'Penawaran AS Nomor Lebih Baru', 2026, 7, 99);
    penawaranIndexCreateOffer($pt, $ptViewer, 'Penawaran PT Nomor Lebih Lama', 2026, 7, 1);

    $this->actingAs($ptViewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'Penawaran PT Nomor Lebih Lama',
            'Penawaran AS Nomor Lebih Baru',
        ]);

    $this->actingAs($ptViewer)
        ->get(route('penawaran.index', ['company_id' => $cv->id]))
        ->assertOk()
        ->assertDontSee('Penawaran PT Nomor Lebih Lama')
        ->assertSee('Penawaran AS Nomor Lebih Baru');
});

test('penawaran index exposes scoped status counts and filters the selected status', function () {
    $company = penawaranIndexCompany('STATUS');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);

    penawaranIndexCreateOffer($company, $viewer, 'Status Draft Unik', 2026, 7, 1);
    penawaranIndexAttachApproval(
        penawaranIndexCreateOffer($company, $viewer, 'Status Menunggu Unik', 2026, 7, 2),
        'menunggu'
    );
    penawaranIndexAttachApproval(
        penawaranIndexCreateOffer($company, $viewer, 'Status Disetujui Unik', 2026, 7, 3),
        'disetujui'
    );
    penawaranIndexAttachApproval(
        penawaranIndexCreateOffer($company, $viewer, 'Status Ditolak Unik', 2026, 7, 4),
        'ditolak'
    );
    penawaranIndexCreateOffer($company, $viewer, 'Status Goal Unik', 2026, 7, 5)
        ->update(['is_goal' => true]);

    $response = $this->actingAs($viewer)
        ->get(route('penawaran.index', ['status' => 'waiting']));

    $response
        ->assertOk()
        ->assertViewHas('statusFilter', 'waiting')
        ->assertViewHas('statusCounts', [
            'all' => 5,
            'waiting' => 1,
            'approved' => 1,
            'rejected' => 1,
            'goal' => 1,
        ])
        ->assertViewHas('data', fn ($data) => $data->total() === 1)
        ->assertSee('Status Menunggu Unik')
        ->assertDontSee('Status Draft Unik')
        ->assertDontSee('Status Disetujui Unik')
        ->assertDontSee('Status Ditolak Unik')
        ->assertDontSee('Status Goal Unik');

    foreach ([
        'approved' => 'Status Disetujui Unik',
        'rejected' => 'Status Ditolak Unik',
        'goal' => 'Status Goal Unik',
    ] as $status => $title) {
        $this->actingAs($viewer)
            ->get(route('penawaran.index', ['status' => $status]))
            ->assertOk()
            ->assertViewHas('statusFilter', $status)
            ->assertViewHas('data', fn ($data) => $data->total() === 1)
            ->assertSee($title);
    }
});

test('penawaran index excludes deletion records before pagination', function () {
    $company = penawaranIndexCompany('ACTIVE');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);
    penawaranIndexCreateOffer($company, $viewer, 'Penawaran Aktif Harus Muncul', 2026, 7, 1);

    for ($seq = 2; $seq <= 17; $seq++) {
        penawaranIndexAttachApproval(
            penawaranIndexCreateOffer($company, $viewer, 'Penawaran Penghapusan '.$seq, 2026, 7, $seq),
            'menunggu',
            'penghapusan'
        );
    }

    $this->actingAs($viewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertViewHas('data', fn ($data) => $data->total() === 1 && $data->count() === 1)
        ->assertSee('Penawaran Aktif Harus Muncul')
        ->assertDontSee('Penawaran Penghapusan');
});

test('penawaran index supports oldest title and status sorting', function () {
    $company = penawaranIndexCompany('SORT');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);

    $older = penawaranIndexCreateOffer($company, $viewer, 'Zulu Draft', 2025, 12, 99);
    $newer = penawaranIndexCreateOffer($company, $viewer, 'Alpha Approved', 2026, 1, 1);
    $waiting = penawaranIndexCreateOffer($company, $viewer, 'Mike Waiting', 2026, 2, 1);
    penawaranIndexAttachApproval($newer, 'disetujui');
    penawaranIndexAttachApproval($waiting, 'menunggu');

    $this->actingAs($viewer)
        ->get(route('penawaran.index', ['sort' => 'oldest']))
        ->assertOk()
        ->assertViewHas('sortFilter', 'oldest')
        ->assertSeeInOrder(['Zulu Draft', 'Alpha Approved', 'Mike Waiting']);

    $this->actingAs($viewer)
        ->get(route('penawaran.index', ['sort' => 'title']))
        ->assertOk()
        ->assertViewHas('sortFilter', 'title')
        ->assertSeeInOrder(['Alpha Approved', 'Mike Waiting', 'Zulu Draft']);

    $this->actingAs($viewer)
        ->get(route('penawaran.index', ['sort' => 'status']))
        ->assertOk()
        ->assertViewHas('sortFilter', 'status')
        ->assertSeeInOrder(['Mike Waiting', 'Alpha Approved', 'Zulu Draft']);
});

test('penawaran index normalizes controls and supports larger page sizes', function () {
    $company = penawaranIndexCompany('PAGE');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);

    foreach (range(1, 16) as $seq) {
        penawaranIndexCreateOffer($company, $viewer, 'Penawaran Halaman '.$seq, 2026, 7, $seq);
    }

    $this->actingAs($viewer)
        ->get(route('penawaran.index', ['per_page' => 30]))
        ->assertOk()
        ->assertViewHas('perPage', 30)
        ->assertViewHas('data', fn ($data) => $data->perPage() === 30 && $data->count() === 16);

    $this->actingAs($viewer)
        ->get(route('penawaran.index', [
            'status' => 'tidak-valid',
            'sort' => 'tidak-valid',
            'per_page' => 999,
        ]))
        ->assertOk()
        ->assertViewHas('statusFilter', 'all')
        ->assertViewHas('sortFilter', 'newest')
        ->assertViewHas('perPage', 15);
});

test('penawaran index renders find and monitor ux controls with navigable records', function () {
    $company = penawaranIndexCompany('UX');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);
    $approved = penawaranIndexCreateOffer($company, $viewer, 'UX Monitor Approved', 2026, 7, 1);
    $goal = penawaranIndexCreateOffer($company, $viewer, 'UX Monitor Goal', 2026, 7, 2);
    penawaranIndexAttachApproval($approved, 'disetujui');
    $goal->update(['is_goal' => true]);

    $this->actingAs($viewer)
        ->get(route('penawaran.index', [
            'q' => 'UX Monitor',
            'status' => 'approved',
            'sort' => 'title',
            'per_page' => 30,
        ]))
        ->assertOk()
        ->assertSee('data-mobile-filter-trigger', false)
        ->assertSee('data-mobile-filter-drawer', false)
        ->assertSee('data-mobile-filter-backdrop', false)
        ->assertSee('data-filter-apply', false)
        ->assertSee('data-compact-period-shortcuts', false)
        ->assertSee('Cepat:')
        ->assertDontSee('data-balanced-filter-panel', false)
        ->assertDontSee('data-status-group', false)
        ->assertDontSee('data-status-tabs', false)
        ->assertDontSee('data-status-tab="approved"', false)
        ->assertSee('data-active-filter-chips', false)
        ->assertSee('data-filter-chip="q"', false)
        ->assertSee('data-filter-chip="status"', false)
        ->assertSee('data-filter-chip="sort"', false)
        ->assertSee('data-filter-chip="per_page"', false)
        ->assertSee('data-clear-all-filters', false)
        ->assertSee('data-kpi-link="approved"', false)
        ->assertSee('data-kpi-link="goal"', false)
        ->assertSee('data-kpi-link="conversion"', false)
        ->assertDontSee('data-results-toolbar', false)
        ->assertSee('data-filter-results-summary', false)
        ->assertSee('Menampilkan')
        ->assertSee('1-1')
        ->assertSee('4 filter aktif')
        ->assertSee('Semua periode')
        ->assertSee('data-sticky-header', false)
        ->assertSee('data-sticky-action-column', false)
        ->assertSee('data-penawaran-detail-link', false);
});

test('penawaran keyword search does not inject a default date range filter', function () {
    $company = penawaranIndexCompany('NO-DATE');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);

    penawaranIndexCreateOffer($company, $viewer, 'Cari Tanpa Periode', 2026, 7, 1);

    $this->actingAs($viewer)
        ->get(route('penawaran.index', ['q' => 'Cari Tanpa Periode']))
        ->assertOk()
        ->assertViewHas('dateFrom', null)
        ->assertViewHas('dateTo', null)
        ->assertSee('name="date_from"', false)
        ->assertSee('name="date_to"', false)
        ->assertDontSee('value="2026-01-01"', false)
        ->assertDontSee('value="2026-12-31"', false)
        ->assertDontSee('Periode:', false)
        ->assertSee('data-filter-chip="q"', false)
        ->assertDontSee('data-filter-chip="date"', false);
});

test('penawaran index renders contextual empty states with a clear filter action', function () {
    $company = penawaranIndexCompany('EMPTY');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);
    penawaranIndexCreateOffer($company, $viewer, 'Penawaran Yang Ada', 2026, 7, 1);

    $this->actingAs($viewer)
        ->get(route('penawaran.index', ['q' => 'Tidak Ditemukan']))
        ->assertOk()
        ->assertSee('data-filter-empty-state', false)
        ->assertSee('Tidak ada penawaran yang sesuai filter')
        ->assertSee('data-clear-empty-filter', false);

    Penawaran::query()->delete();

    $this->actingAs($viewer)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSee('data-empty-state', false)
        ->assertSee('Belum ada penawaran');
});

test('penawaran detail lets logout form submit normally instead of ajax', function () {
    $company = penawaranIndexCompany('LOGOUT-AS');
    $viewer = penawaranIndexUserWithPermissions($company, ['view-all-penawaran']);
    $penawaran = penawaranIndexCreateOffer($company, $viewer, 'Penawaran Logout Normal', 2026, 7, 1);

    $this->actingAs($viewer)
        ->get(route('penawaran.show', $penawaran))
        ->assertOk()
        ->assertSee("formAction.includes('/logout')", false);
});

test('quotation owner without permissions does not see forbidden edit or delete actions', function () {
    $company = penawaranIndexCompany('ACTION-NONE');
    $owner = penawaranIndexUserWithPermissions($company, []);
    $penawaran = penawaranIndexCreateOffer($company, $owner, 'Penawaran Tanpa Aksi', 2026, 7, 1);

    $this->actingAs($owner)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertDontSee('data-create-penawaran-cta', false)
        ->assertDontSee('data-action-edit-penawaran', false)
        ->assertDontSee('data-action-delete-penawaran', false);

    $this->actingAs($owner)
        ->get(route('penawaran.show', $penawaran))
        ->assertOk()
        ->assertDontSee('data-action-edit-penawaran', false)
        ->assertDontSee('data-action-delete-penawaran', false);
});

test('quotation owner sees only actions granted by permissions', function () {
    $company = penawaranIndexCompany('ACTION-YES');
    $owner = penawaranIndexUserWithPermissions($company, [
        'edit-penawaran',
        'delete-penawaran',
        'create-penawaran',
    ]);
    $penawaran = penawaranIndexCreateOffer($company, $owner, 'Penawaran Dengan Aksi', 2026, 7, 1);

    $this->actingAs($owner)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertDontSee('data-create-penawaran-cta', false)
        ->assertSee('data-action-edit-penawaran', false)
        ->assertSee('data-action-delete-penawaran', false)
        ->assertSee('data-action-duplicate-penawaran', false);
});

test('quotation action menu floats without expanding mobile cards or desktop rows', function () {
    $company = penawaranIndexCompany('ACTION-MOBILE');
    $owner = penawaranIndexUserWithPermissions($company, [
        'edit-penawaran',
        'delete-penawaran',
        'create-penawaran',
    ]);

    penawaranIndexCreateOffer($company, $owner, 'Penawaran Menu Mobile', 2026, 7, 1);

    $this->actingAs($owner)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSee('data-action-menu', false)
        ->assertSee('data-mobile-action-popover', false)
        ->assertSee('data-desktop-action-menu', false)
        ->assertSee('x-data="floatingActionMenu"', false)
        ->assertSee('x-teleport="body"', false)
        ->assertSee(':style="menuStyle"', false)
        ->assertSee('fixed z-50', false)
        ->assertDontSee('data-action-backdrop', false)
        ->assertDontSee('data-mobile-action-sheet', false)
        ->assertDontSee('md:static', false);
});

test('quotation index renders responsive views and concise approval status', function () {
    $company = penawaranIndexCompany('RESPONSIVE');
    $owner = penawaranIndexUserWithPermissions($company, ['view-penawaran']);
    $penawaran = penawaranIndexCreateOffer($company, $owner, 'Penawaran Responsif', 2026, 7, 1);
    $approval = Approval::create([
        'module' => 'penawaran',
        'ref_id' => $penawaran->id,
        'status' => 'menunggu',
        'current_step' => 1,
    ]);

    $penawaran->update(['approval_id' => $approval->id]);

    $this->actingAs($owner)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSee('data-penawaran-desktop-table', false)
        ->assertSee('data-penawaran-mobile-list', false)
        ->assertSee('Menunggu persetujuan - Tahap 1')
        ->assertDontSee('Menunggu Approval Disetujui');
});
