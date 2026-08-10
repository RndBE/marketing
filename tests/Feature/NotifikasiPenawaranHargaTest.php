<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UsulanPenawaran;

function notifikasiUser(Company $company, string $label): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Notifikasi '.$label,
        'slug' => 'notifikasi-'.strtolower($label).'-'.uniqid(),
    ]);

    foreach (['view-usulan', 'create-usulan', 'edit-usulan', 'respond-usulan'] as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Trade Flow']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $user->roles()->attach($role->id);

    return $user;
}

/**
 * Notifikasi yang berdekatan bisa punya created_at yang sama persis, jadi
 * urutannya tidak bisa dipakai untuk menguji. Yang diuji keberadaannya.
 *
 * @return array<int, string|null>
 */
function jenisNotifikasi(User $user): array
{
    return $user->notifications()
        ->get()
        ->map(fn ($item) => data_get($item->data, 'jenis'))
        ->all();
}

function buatPermintaanHarga($test, User $pembeli, User $penjual, string $reference, string $status = 'menunggu'): UsulanPenawaran
{
    $test->actingAs($pembeli)
        ->post(route('penawaran-harga.store'), [
            'target_company_id' => $penjual->company_id,
            'judul' => 'Permintaan '.$reference,
            'jenis_transaksi' => 'barang',
            'deskripsi' => 'Uji notifikasi '.$reference,
            'status' => $status,
            'items_present' => 1,
            'item_judul' => ['Perangkat Monitoring'],
            'item_qty' => [1],
            'item_satuan' => ['unit'],
            'item_harga' => [1000000],
            'item_tipe' => ['custom'],
            'item_product_id' => [''],
        ])
        ->assertRedirect();

    return UsulanPenawaran::query()->where('judul', 'Permintaan '.$reference)->firstOrFail();
}

test('permintaan harga yang dikirim memunculkan notifikasi di perusahaan tujuan, dua arah', function () {
    $cv = Company::create(['code' => 'CV-NOTIF', 'name' => 'CV Arta Notif']);
    $pt = Company::create(['code' => 'PT-NOTIF', 'name' => 'PT Mitra Notif']);
    $cvUser = notifikasiUser($cv, 'CV');
    $ptUser = notifikasiUser($pt, 'PT');

    // CV -> PT
    buatPermintaanHarga($this, $cvUser, $ptUser, 'CV-PT');

    expect($ptUser->unreadNotifications()->count())->toBe(1)
        // Pelakunya sendiri tidak perlu dikabari.
        ->and($cvUser->unreadNotifications()->count())->toBe(0);

    $notifPt = $ptUser->notifications()->first();
    expect(data_get($notifPt->data, 'jenis'))->toBe('permintaan_dikirim')
        ->and(data_get($notifPt->data, 'pesan'))->toContain('CV Arta Notif');

    // PT -> CV, arah sebaliknya lewat jalur yang sama.
    buatPermintaanHarga($this, $ptUser, $cvUser, 'PT-CV');

    expect($cvUser->unreadNotifications()->count())->toBe(1)
        ->and($ptUser->unreadNotifications()->count())->toBe(1);

    $notifCv = $cvUser->notifications()->first();
    expect(data_get($notifCv->data, 'jenis'))->toBe('permintaan_dikirim')
        ->and(data_get($notifCv->data, 'pesan'))->toContain('PT Mitra Notif');
});

test('permintaan berstatus draft belum mengabari perusahaan tujuan', function () {
    $cv = Company::create(['code' => 'CV-DRAFT', 'name' => 'CV Draft']);
    $pt = Company::create(['code' => 'PT-DRAFT', 'name' => 'PT Draft']);
    $cvUser = notifikasiUser($cv, 'CV');
    $ptUser = notifikasiUser($pt, 'PT');

    $usulan = buatPermintaanHarga($this, $cvUser, $ptUser, 'DRAFT', 'draft');

    expect($ptUser->unreadNotifications()->count())->toBe(0);

    // Begitu draft-nya dikirim, barulah notifikasinya jalan.
    $this->actingAs($cvUser)
        ->put(route('penawaran-harga.update', $usulan), [
            'target_company_id' => $ptUser->company_id,
            'judul' => $usulan->judul,
            'jenis_transaksi' => 'barang',
            'status' => 'menunggu',
        ])
        ->assertRedirect();

    expect($ptUser->unreadNotifications()->count())->toBe(1);
});

test('tanggapan penjual sampai penawaran ditanggapi mengabari perusahaan lawan', function () {
    $cv = Company::create(['code' => 'CV-ALUR', 'name' => 'CV Alur']);
    $pt = Company::create(['code' => 'PT-ALUR', 'name' => 'PT Alur']);
    $cvUser = notifikasiUser($cv, 'CV');
    $ptUser = notifikasiUser($pt, 'PT');

    $usulan = buatPermintaanHarga($this, $cvUser, $ptUser, 'ALUR');

    // Penjual menanggapi -> peminta yang dikabari.
    $this->actingAs($ptUser)
        ->post(route('penawaran-harga.tanggapi', $usulan), [
            'tanggapan' => 'Permintaan diterima, penawaran disiapkan.',
            'status' => 'disetujui',
            'penawaran_action' => 'from_usulan',
        ])
        ->assertRedirect();

    expect($cvUser->unreadNotifications()->count())->toBe(1)
        ->and(jenisNotifikasi($cvUser))->toContain('permintaan_ditanggapi');

    // Penawaran dikirim -> peminta dikabari lagi.
    $this->actingAs($ptUser)
        ->post(route('penawaran-harga.kirim-penawaran', $usulan))
        ->assertRedirect();

    expect($cvUser->unreadNotifications()->count())->toBe(2)
        ->and(jenisNotifikasi($cvUser))->toContain('penawaran_dikirim');

    // Peminta menyetujui -> giliran penjual yang dikabari.
    $this->actingAs($cvUser)
        ->post(route('penawaran-harga.tanggapi-penawaran', $usulan), [
            'action' => 'accepted',
        ])
        ->assertRedirect();

    $judulPenjual = $ptUser->notifications()
        ->get()
        ->map(fn ($item) => data_get($item->data, 'judul'))
        ->all();

    expect(jenisNotifikasi($ptUser))->toContain('penawaran_ditanggapi')
        ->and($judulPenjual)->toContain('Penawaran disetujui');
});

/** Penanda merah di sidebar untuk perusahaan yang sedang login. */
function tugasSidebar($test, User $user): ?string
{
    $html = $test->actingAs($user)->get(route('penawaran-harga.index'))->assertOk()->getContent();

    preg_match('/data-tugas-penawaran-harga="(\d+)"/', $html, $cocok);

    return $cocok[1] ?? null;
}

test('dot sidebar hanya menyala untuk pihak yang gilirannya bertindak', function () {
    $cv = Company::create(['code' => 'CV-DOT', 'name' => 'CV Dot']);
    $pt = Company::create(['code' => 'PT-DOT', 'name' => 'PT Dot']);
    $cvUser = notifikasiUser($cv, 'CV');
    $ptUser = notifikasiUser($pt, 'PT');

    $usulan = buatPermintaanHarga($this, $cvUser, $ptUser, 'DOT');

    // Permintaan masuk belum ditanggapi: giliran penjual.
    expect(tugasSidebar($this, $ptUser))->toBe('1')
        ->and(tugasSidebar($this, $cvUser))->toBeNull();

    // Penawaran dibuat tapi masih draft: tetap giliran penjual.
    $this->actingAs($ptUser)
        ->post(route('penawaran-harga.tanggapi', $usulan), [
            'tanggapan' => 'Disiapkan.',
            'status' => 'disetujui',
            'penawaran_action' => 'from_usulan',
        ])
        ->assertRedirect();

    expect(tugasSidebar($this, $ptUser))->toBe('1')
        ->and(tugasSidebar($this, $cvUser))->toBeNull();

    // Penawaran dikirim: bolanya pindah ke pembeli.
    $this->actingAs($ptUser)
        ->post(route('penawaran-harga.kirim-penawaran', $usulan))
        ->assertRedirect();

    expect(tugasSidebar($this, $cvUser))->toBe('1')
        ->and(tugasSidebar($this, $ptUser))->toBeNull();

    // Pembeli minta revisi: balik lagi ke penjual.
    $this->actingAs($cvUser)
        ->post(route('penawaran-harga.tanggapi-penawaran', $usulan), [
            'action' => 'revision_requested',
            'penawaran_tanggapan' => 'Tolong turunkan harganya.',
        ])
        ->assertRedirect();

    expect(tugasSidebar($this, $ptUser))->toBe('1')
        ->and(tugasSidebar($this, $cvUser))->toBeNull();
});

test('dot sidebar padam setelah penawaran disetujui', function () {
    $cv = Company::create(['code' => 'CV-DONE', 'name' => 'CV Done']);
    $pt = Company::create(['code' => 'PT-DONE', 'name' => 'PT Done']);
    $cvUser = notifikasiUser($cv, 'CV');
    $ptUser = notifikasiUser($pt, 'PT');

    $usulan = buatPermintaanHarga($this, $cvUser, $ptUser, 'DONE');

    $this->actingAs($ptUser)->post(route('penawaran-harga.tanggapi', $usulan), [
        'tanggapan' => 'Disiapkan.',
        'status' => 'disetujui',
        'penawaran_action' => 'from_usulan',
    ])->assertRedirect();
    $this->actingAs($ptUser)->post(route('penawaran-harga.kirim-penawaran', $usulan))->assertRedirect();
    $this->actingAs($cvUser)->post(route('penawaran-harga.tanggapi-penawaran', $usulan), [
        'action' => 'accepted',
    ])->assertRedirect();

    expect(tugasSidebar($this, $cvUser))->toBeNull()
        ->and(tugasSidebar($this, $ptUser))->toBeNull();
});

test('lonceng menampilkan jumlah belum dibaca dan bisa ditandai terbaca', function () {
    $cv = Company::create(['code' => 'CV-BELL', 'name' => 'CV Bell']);
    $pt = Company::create(['code' => 'PT-BELL', 'name' => 'PT Bell']);
    $cvUser = notifikasiUser($cv, 'CV');
    $ptUser = notifikasiUser($pt, 'PT');

    $usulan = buatPermintaanHarga($this, $cvUser, $ptUser, 'BELL');

    $this->actingAs($ptUser)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('items.0.jenis', 'permintaan_dikirim')
        ->assertJsonPath('items.0.dibaca', false);

    // Klik notifikasi: tandai terbaca lalu diarahkan ke dokumennya.
    $notification = $ptUser->notifications()->firstOrFail();
    $this->actingAs($ptUser)
        ->get(route('notifications.open', $notification->id))
        ->assertRedirect(route('penawaran-harga.show', $usulan->id));

    expect($ptUser->unreadNotifications()->count())->toBe(0);

    // Notifikasi milik orang lain tidak bisa dibuka.
    $this->actingAs($cvUser)
        ->get(route('notifications.open', $notification->id))
        ->assertNotFound();

    buatPermintaanHarga($this, $cvUser, $ptUser, 'BELL-2');

    $this->actingAs($ptUser)
        ->postJson(route('notifications.read-all'))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    expect($ptUser->unreadNotifications()->count())->toBe(0);
});
