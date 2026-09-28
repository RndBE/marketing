<?php

use App\Mcp\Servers\PenawaranServer;
use App\Mcp\Tools\BuatPenawaran;
use App\Mcp\Tools\CariBundle;
use App\Mcp\Tools\CariPenawaranSerupa;
use App\Models\AlurPenawaran;
use App\Models\Company;
use App\Models\Komponen;
use App\Models\LangkahAlurPenawaran;
use App\Models\Penawaran;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Role;
use App\Models\User;

function mcpSales(Company $company, array $permissionSlugs = ['create-penawaran', 'edit-penawaran']): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create(['name' => 'Sales '.uniqid(), 'slug' => 'sales-'.uniqid()]);

    foreach ($permissionSlugs as $slug) {
        $role->permissions()->attach(
            Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'group' => 'Penawaran'])->id
        );
    }

    $user->roles()->attach($role->id);

    return $user;
}

function mcpSiapkanKatalog(): array
{
    $company = Company::create(['code' => 'MCP', 'name' => 'PT Uji MCP']);
    $user = mcpSales($company);

    $alur = AlurPenawaran::create([
        'company_id' => $company->id,
        'nama' => 'Alur MCP',
        'berlaku_untuk' => 'penawaran',
        'status' => 'aktif',
        'dibuat_oleh' => $user->id,
    ]);
    LangkahAlurPenawaran::create([
        'alur_penawaran_id' => $alur->id,
        'no_langkah' => 1,
        'nama_langkah' => 'Approve',
        'user_id' => null,
        'harus_semua' => 0,
    ]);

    $bundle = Product::create(['company_id' => $company->id, 'kode' => 'BE-AWLR', 'nama' => 'Beacon Data logger AWLR', 'satuan' => 'Paket', 'is_active' => true]);
    ProductDetail::create(['product_id' => $bundle->id, 'urutan' => 1, 'nama' => 'Datalogger BL-110', 'qty' => 1, 'satuan' => 'Unit', 'harga' => 30_000_000, 'subtotal' => 30_000_000]);
    ProductDetail::create(['product_id' => $bundle->id, 'urutan' => 2, 'nama' => 'Sensor Radar', 'qty' => 2, 'satuan' => 'Unit', 'harga' => 5_000_000, 'subtotal' => 10_000_000]);

    $komponen = Komponen::create(['company_id' => $company->id, 'kode' => 'MPPT-30A', 'nama' => 'MPPT Solar Charger 30A', 'satuan' => 'Unit', 'harga' => 1_500_000, 'is_active' => true]);

    return [$company, $user, $bundle, $komponen];
}

test('buat-penawaran membuat penawaran lengkap dengan bundle, item custom dari katalog, diskon, dan pajak', function () {
    [, $user, $bundle, $komponen] = mcpSiapkanKatalog();

    PenawaranServer::actingAs($user)->tool(BuatPenawaran::class, [
        'judul' => 'Pengadaan AWLR',
        'instansi_tujuan' => 'BBWS Uji',
        'diskon' => ['tipe' => 'percent', 'nilai' => 10],
        'pajak_persen' => 11,
        'items' => [
            ['bundle_id' => $bundle->id, 'qty' => 3],
            ['judul' => 'Catu daya tambahan', 'rincian' => [['komponen_id' => $komponen->id, 'qty' => 2]]],
        ],
    ])->assertOk()->assertHasNoErrors()
        // (3 x 40jt + 2 x 1,5jt) - 10% = 110,7jt; + PPN 11% = 122.877.000
        ->assertSee(['"dpp":110700000', '"total":122877000']);

    $penawaran = Penawaran::with(['docNumber', 'items.details', 'approval.steps', 'cover', 'validity', 'signatures'])->sole();

    expect($penawaran->docNumber->doc_no)->toStartWith('001/SPH')->toContain('/MCP/')
        ->and($penawaran->id_user)->toBe($user->id)
        ->and($penawaran->instansi_tujuan)->toBe('BBWS Uji')
        ->and($penawaran->approval->status)->toBe('menunggu')
        ->and($penawaran->approval->steps->first()->akses_approve['user_id'])->toBe($user->id)
        ->and($penawaran->cover)->not->toBeNull()
        ->and($penawaran->validity)->not->toBeNull()
        ->and($penawaran->signatures)->toHaveCount(1);

    [$itemBundle, $itemCustom] = $penawaran->items->all();

    expect($itemBundle->tipe)->toBe('bundle')
        ->and($itemBundle->details->pluck('nama')->all())->toBe(['Datalogger BL-110', 'Sensor Radar'])
        ->and($itemBundle->subtotal)->toBe(120_000_000)
        ->and($itemCustom->tipe)->toBe('custom')
        ->and($itemCustom->details->first()->only(['nama', 'harga', 'subtotal']))
        ->toBe(['nama' => 'MPPT Solar Charger 30A', 'harga' => 1_500_000, 'subtotal' => 3_000_000]);
});

test('buat-penawaran menolak item custom tanpa rincian dan perusahaan lain untuk non-admin', function () {
    [, $user] = mcpSiapkanKatalog();
    Company::create(['code' => 'LAIN', 'name' => 'PT Lain']);

    PenawaranServer::actingAs($user)->tool(BuatPenawaran::class, [
        'judul' => 'Tanpa rincian',
        'items' => [['judul' => 'Item kosong']],
    ])->assertHasErrors();

    PenawaranServer::actingAs($user)->tool(BuatPenawaran::class, [
        'judul' => 'Perusahaan lain',
        'perusahaan' => 'LAIN',
        'items' => [['judul' => 'Jasa', 'rincian' => [['nama' => 'Jasa', 'qty' => 1, 'harga' => 1000]]]],
    ])->assertHasErrors(['Hanya admin']);

    expect(Penawaran::count())->toBe(0);
});

test('cari-bundle menemukan bundle lewat rinciannya dan cari-penawaran-serupa hanya memperlihatkan milik sendiri', function () {
    [, $user, $bundle] = mcpSiapkanKatalog();
    $rekan = mcpSales($user->company);

    PenawaranServer::actingAs($user)->tool(CariBundle::class, ['q' => 'awlr radar'])
        ->assertOk()->assertSee(['"id":'.$bundle->id, '"harga_per_unit":40000000']);

    foreach ([$user, $rekan] as $pemilik) {
        PenawaranServer::actingAs($pemilik)->tool(BuatPenawaran::class, [
            'judul' => 'AWLR milik '.$pemilik->id,
            'items' => [['bundle_id' => $bundle->id]],
        ])->assertOk();
    }

    PenawaranServer::actingAs($user)->tool(CariPenawaranSerupa::class, ['q' => 'AWLR'])
        ->assertOk()->assertSee('AWLR milik '.$user->id)->assertDontSee('AWLR milik '.$rekan->id);
});

test('form web store dan addBundle tetap menghasilkan penawaran yang sama lewat service bersama', function () {
    [, $user, $bundle] = mcpSiapkanKatalog();

    $this->actingAs($user)->post(route('penawaran.store'), ['judul' => 'Dari web'])->assertRedirect();

    $penawaran = Penawaran::with(['docNumber', 'approval', 'cover', 'validity', 'signatures'])->sole();
    expect($penawaran->docNumber->doc_no)->toStartWith('001/SPH')
        ->and($penawaran->approval->status)->toBe('menunggu')
        ->and($penawaran->cover->subjudul)->toBe('Dari web')
        ->and($penawaran->validity->berlaku_hari)->toBe(30)
        ->and($penawaran->signatures)->toHaveCount(1)
        ->and($penawaran->discount_enabled)->toBeFalse()
        ->and($penawaran->tax_enabled)->toBeFalse();

    $this->actingAs($user)->postJson(route('penawaran.items.bundle', $penawaran), ['product_id' => $bundle->id, 'qty' => 2])->assertOk();

    $item = $penawaran->items()->with('details')->sole();
    expect($item->judul)->toBe('Beacon Data logger AWLR')
        ->and($item->details)->toHaveCount(2)
        ->and($item->subtotal)->toBe(80_000_000);
});

test('mcp:token membuat dan mencabut token', function () {
    [, $user] = mcpSiapkanKatalog();

    $this->artisan('mcp:token', ['email' => $user->email])
        ->expectsOutputToContain('claude mcp add --scope user --transport http crm-penawaran')
        ->assertSuccessful();
    expect($user->tokens()->count())->toBe(1);

    $this->artisan('mcp:token', ['email' => $user->email, '--cabut' => true])->assertSuccessful();
    expect($user->tokens()->count())->toBe(0);
});

test('endpoint MCP butuh token Sanctum dan izin create-penawaran', function () {
    [$company, $user] = mcpSiapkanKatalog();
    $tanpaIzin = mcpSales($company, []);
    $body = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => []];

    $this->postJson('/mcp/penawaran', $body)->assertUnauthorized();

    $this->withToken($tanpaIzin->createToken('claude-mcp')->plainTextToken)
        ->postJson('/mcp/penawaran', $body)->assertForbidden();

    // Guard Sanctum menyimpan user hasil request sebelumnya di dalam satu test.
    $this->app['auth']->forgetGuards();

    $this->withToken($user->createToken('claude-mcp')->plainTextToken)
        ->postJson('/mcp/penawaran', $body)->assertOk()
        ->assertSee(['cari-penawaran-serupa', 'cari-bundle', 'cari-komponen', 'cari-pic', 'buat-penawaran']);
});
