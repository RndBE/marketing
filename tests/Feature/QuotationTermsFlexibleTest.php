<?php

use App\Models\Company;
use App\Models\Penawaran;
use App\Models\PenawaranTerm;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UsulanPenawaran;

function termsFlowSeller(): User
{
    $company = Company::create(['code' => 'CV-TERMS', 'name' => 'CV Terms']);
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create(['name' => 'Terms Flow', 'slug' => 'terms-flow-'.uniqid()]);

    foreach (['view-usulan', 'edit-usulan', 'respond-usulan', 'view-penawaran', 'edit-penawaran'] as $slug) {
        $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'group' => 'Terms Flow']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }
    $user->roles()->attach($role->id);

    return $user;
}

/**
 * Penawaran dari permohonan berstatus draft dengan 3 poin utama; poin 2 punya 2 sub-poin.
 * Baris sengaja dibuat dengan urutan yang bertabrakan antara poin utama dan sub-poin,
 * seperti hasil salinan template perusahaan.
 *
 * @return array{0: User, 1: UsulanPenawaran, 2: Penawaran, 3: array<string, PenawaranTerm>}
 */
function termsFlowQuotation(): array
{
    $seller = termsFlowSeller();
    $buyerCompany = Company::create(['code' => 'PT-TERMS', 'name' => 'PT Terms Pembeli']);
    $buyer = User::factory()->create(['company_id' => $buyerCompany->id]);

    $usulan = UsulanPenawaran::create([
        'company_id' => $buyerCompany->id,
        'target_company_id' => $seller->company_id,
        'judul' => 'Permintaan Terms',
        'jenis_transaksi' => 'jasa',
        'status' => 'disetujui',
        'penawaran_status' => 'draft',
        'created_by' => $buyer->id,
    ]);

    $penawaran = Penawaran::create([
        'company_id' => $seller->company_id,
        'id_user' => $seller->id,
        'judul' => 'Penawaran Terms',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    $usulan->update(['penawaran_id' => $penawaran->id]);
    $item = $penawaran->items()->create([
        'judul' => 'Jasa',
        'qty' => 1,
        'satuan' => 'paket',
        'unit_price' => 1000,
        'urutan' => 1,
    ]);

    $make = fn (string $isi, int $urutan, ?PenawaranTerm $parent = null) => PenawaranTerm::create([
        'penawaran_id' => $penawaran->id,
        'parent_id' => $parent?->id,
        'urutan' => $urutan,
        'isi' => $isi,
    ]);

    $t1 = $make('Satu', 1);
    $t2 = $make('Dua', 2);
    $t3 = $make('Tiga', 3);
    $c1 = $make('Dua-A', 1, $t2);
    $c2 = $make('Dua-B', 2, $t2);

    $penawaran->setRelation('items', collect([$item]));

    return [$seller, $usulan, $penawaran, compact('t1', 't2', 't3', 'c1', 'c2')];
}

function termsFlowPayload(Penawaran $penawaran, array $termRows): array
{
    $item = $penawaran->items()->first();

    return [
        'tanggal_penawaran' => '2026-10-19',
        'nama_pekerjaan' => 'Pekerjaan',
        'items' => [$item->id => [
            'judul' => 'Jasa', 'catatan' => null, 'qty' => 1, 'satuan' => 'paket', 'unit_price' => 1000,
        ]],
        'discount_enabled' => 0,
        'tax_enabled' => 0,
        'signature_name' => 'Penanda Tangan',
        'term_rows' => $termRows,
    ];
}

function termsFlowTexts(Penawaran $penawaran): array
{
    return $penawaran->fresh()->orderedTerms()
        ->map(fn ($t) => str_repeat('-', $t->depth).$t->isi)
        ->values()
        ->all();
}

test('keterangan diurutkan poin utama lalu sub-poinnya', function () {
    [, , $penawaran] = termsFlowQuotation();

    expect(termsFlowTexts($penawaran))->toBe(['Satu', 'Dua', '-Dua-A', '-Dua-B', 'Tiga']);
});

test('baris keterangan yang dikosongkan dihapus dan tidak menyebabkan error 500', function () {
    [$seller, $usulan, $penawaran, $t] = termsFlowQuotation();

    $rows = [
        ['key' => 't'.$t['t1']->id, 'id' => $t['t1']->id, 'parent_key' => null, 'isi' => 'Satu'],
        ['key' => 't'.$t['t2']->id, 'id' => $t['t2']->id, 'parent_key' => null, 'isi' => 'Dua'],
        ['key' => 't'.$t['c1']->id, 'id' => $t['c1']->id, 'parent_key' => 't'.$t['t2']->id, 'isi' => 'Dua-A'],
        // Sub-poin kedua dikosongkan, seperti kasus Termin 2 di produksi.
        ['key' => 't'.$t['c2']->id, 'id' => $t['c2']->id, 'parent_key' => 't'.$t['t2']->id, 'isi' => null],
        ['key' => 't'.$t['t3']->id, 'id' => $t['t3']->id, 'parent_key' => null, 'isi' => 'Tiga'],
    ];

    $this->actingAs($seller)
        ->put(route('penawaran-harga.quotation.update', $usulan), termsFlowPayload($penawaran, $rows))
        ->assertRedirect(route('penawaran-harga.quotation.show', $usulan));

    expect(termsFlowTexts($penawaran))->toBe(['Satu', 'Dua', '-Dua-A', 'Tiga']);
    expect(PenawaranTerm::find($t['c2']->id))->toBeNull();
});

test('keterangan bisa ditambah, disisipkan sebagai sub-poin, dan diurutkan ulang', function () {
    [$seller, $usulan, $penawaran, $t] = termsFlowQuotation();

    $rows = [
        ['key' => 't'.$t['t3']->id, 'id' => $t['t3']->id, 'parent_key' => null, 'isi' => 'Tiga'],
        ['key' => 'n1', 'id' => null, 'parent_key' => 't'.$t['t3']->id, 'isi' => 'Tiga-baru'],
        ['key' => 't'.$t['t1']->id, 'id' => $t['t1']->id, 'parent_key' => null, 'isi' => 'Satu diubah'],
        ['key' => 'n2', 'id' => null, 'parent_key' => null, 'isi' => 'Poin baru'],
    ];

    $this->actingAs($seller)
        ->put(route('penawaran-harga.quotation.update', $usulan), termsFlowPayload($penawaran, $rows))
        ->assertRedirect();

    // "Dua" beserta sub-poinnya tidak dikirim lagi, jadi ikut terhapus.
    expect(termsFlowTexts($penawaran))->toBe(['Tiga', '-Tiga-baru', 'Satu diubah', 'Poin baru']);
    expect(PenawaranTerm::where('penawaran_id', $penawaran->id)->count())->toBe(4);
    expect(PenawaranTerm::find($t['t1']->id)->isi)->toBe('Satu diubah');
});

test('sub-poin yang poin utamanya dikosongkan ikut hilang', function () {
    [$seller, $usulan, $penawaran, $t] = termsFlowQuotation();

    $rows = [
        ['key' => 't'.$t['t1']->id, 'id' => $t['t1']->id, 'parent_key' => null, 'isi' => 'Satu'],
        ['key' => 't'.$t['t2']->id, 'id' => $t['t2']->id, 'parent_key' => null, 'isi' => ''],
        ['key' => 't'.$t['c1']->id, 'id' => $t['c1']->id, 'parent_key' => 't'.$t['t2']->id, 'isi' => 'Dua-A'],
    ];

    $this->actingAs($seller)
        ->put(route('penawaran-harga.quotation.update', $usulan), termsFlowPayload($penawaran, $rows))
        ->assertRedirect();

    expect(termsFlowTexts($penawaran))->toBe(['Satu']);
});

test('simpan tanpa term_rows tidak menyentuh keterangan yang ada', function () {
    [$seller, $usulan, $penawaran] = termsFlowQuotation();

    $payload = termsFlowPayload($penawaran, []);
    unset($payload['term_rows']);

    $this->actingAs($seller)
        ->put(route('penawaran-harga.quotation.update', $usulan), $payload)
        ->assertRedirect();

    expect(termsFlowTexts($penawaran))->toBe(['Satu', 'Dua', '-Dua-A', '-Dua-B', 'Tiga']);
});

test('baris dari penawaran lain tidak bisa ikut diubah lewat id', function () {
    [$seller, $usulan, $penawaran, $t] = termsFlowQuotation();
    $other = Penawaran::create([
        'company_id' => $seller->company_id,
        'id_user' => $seller->id,
        'judul' => 'Penawaran Lain',
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
    $foreign = PenawaranTerm::create(['penawaran_id' => $other->id, 'urutan' => 1, 'isi' => 'Milik lain']);

    $rows = [
        ['key' => 'x', 'id' => $foreign->id, 'parent_key' => null, 'isi' => 'Dibajak'],
    ];

    $this->actingAs($seller)
        ->put(route('penawaran-harga.quotation.update', $usulan), termsFlowPayload($penawaran, $rows))
        ->assertRedirect();

    expect($foreign->fresh()->isi)->toBe('Milik lain')
        ->and($foreign->fresh()->penawaran_id)->toBe($other->id);
    expect(termsFlowTexts($penawaran))->toBe(['Dibajak']);
});

test('form menampilkan nomor hierarki dan tombol tambah', function () {
    [$seller, $usulan] = termsFlowQuotation();

    $this->actingAs($seller)
        ->get(route('penawaran-harga.quotation.show', $usulan))
        ->assertOk()
        ->assertSee('+ Tambah keterangan', false)
        ->assertSee('+ Sub-poin', false)
        ->assertSee('Dua-A');
});
