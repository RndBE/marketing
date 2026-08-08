<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/*
 * Halaman Harga Modal meminjam data HPP dari inventory. Yang dijaga berkas ini:
 * kunci API tidak pernah sampai ke browser, email yang ditanyakan selalu milik
 * pengguna yang sedang login, satu kunjungan hanya menarik satu tab, tiap status
 * jawaban punya pesan sendiri, dan datanya tidak mengendap di database CRM.
 */

const KUNCI_UJI = 'kunci-crm-rahasia-untuk-uji';

beforeEach(function () {
    config()->set('services.inventory.base_url', 'https://inventory.internal');
    config()->set('services.inventory.api_key', KUNCI_UJI);
    config()->set('services.inventory.timeout', 10);
    config()->set('services.inventory.harga_modal_cache_ttl', 0);
    config()->set('services.inventory.harga_modal_cache_store', null);
});

function perusahaanHargaModal(string $kode): Company
{
    return Company::firstOrCreate(['code' => $kode], ['name' => 'Perusahaan '.$kode]);
}

function penggunaHargaModal(string $email, bool $denganIzin = true, string $kodePerusahaan = 'ATC'): User
{
    $role = Role::firstOrCreate(
        ['slug' => $denganIzin ? 'sales-hpp' : 'sales-polos'],
        ['name' => $denganIzin ? 'Sales HPP' : 'Sales Polos'],
    );

    if ($denganIzin) {
        // Izinnya dibuat oleh migration, jadi di sini tinggal dilekatkan ke role.
        $izin = Permission::where('slug', 'view-harga-modal')->firstOrFail();

        if (! $role->permissions()->where('permissions.id', $izin->id)->exists()) {
            $role->permissions()->attach($izin->id);
        }
    }

    // Halaman ini juga dibatasi per perusahaan, jadi tiap pengguna uji harus punya
    // perusahaan. Bawaannya ATC, yang memang diizinkan.
    $pengguna = User::factory()->create([
        'email' => $email,
        'company_id' => perusahaanHargaModal($kodePerusahaan)->id,
    ]);

    $pengguna->roles()->attach($role->id);

    return $pengguna;
}

function barisUnit(array $ganti = []): array
{
    return array_merge([
        'nama_produk' => 'Panel Surya 450Wp',
        'kode_produksi' => 'PJ-0912',
        'kode_unit' => 'UNIT-0001',
        'serial_number' => 'SN-001',
        'stok_sisa' => 12,
        'harga_modal_satuan' => 1850000,
        'sumber' => 'Produksi Internal',
    ], $ganti);
}

function barisBahan(array $ganti = []): array
{
    return array_merge([
        'nama_bahan' => 'Kabel NYAF 1.5mm',
        'stok_sisa' => 120,
        'harga_modal_satuan' => 12000,
        'harga_modal_rata2' => 9500,
        'nilai_persediaan' => 1140000,
        'sumber' => 'Pembelian',
    ], $ganti);
}

/** Bentuk baris endpoint /rincian -- berbeda dari tab Bahan. */
function barisRincian(array $ganti = []): array
{
    return array_merge([
        'nama' => 'Kabel NYAF 1.5mm',
        'kode' => 'BHN-0042',
        'jenis' => 'Kabel',
        'batch' => 'BATCH-2606',
        'qty' => 12,
        'harga_satuan' => 9500,
        'sub_total' => 114000,
        'gambar_url' => 'https://inventory.internal/img/kabel.png',
    ], $ganti);
}

function badanRincian(array $baris, array $ganti = []): array
{
    return array_merge([
        'diambil_pada' => '2026-08-07T09:15:00+07:00',
        'jml_produksi' => 34,
        'total_biaya_bahan' => 426025848,
        'harga_modal_satuan' => 12530172,
        'rincian' => $baris,
    ], $ganti);
}

function banyakBarisRincian(int $jumlah): array
{
    return collect(range(1, $jumlah))->map(fn (int $n) => barisRincian([
        'nama' => sprintf('Bahan %04d', $n),
        'kode' => sprintf('BHN-%04d', $n),
    ]))->all();
}

function badanTab(string $kunci, array $baris, array $ganti = []): array
{
    return array_merge([
        'diambil_pada' => '2026-08-07T09:15:00+07:00',
        $kunci => $baris,
    ], $ganti);
}

function palsukanInventory(array|int $badan, int $status = 200): void
{
    Http::fake([
        '*/api/crm/harga-modal*' => Http::response(is_array($badan) ? $badan : [], is_array($badan) ? $status : $badan),
    ]);
}

/** Parameter query dari tiap panggilan yang benar-benar terkirim ke inventory. */
function kueriTerkirim(): array
{
    return collect(Http::recorded())->map(function ($catatan) {
        parse_str((string) parse_url($catatan[0]->url(), PHP_URL_QUERY), $kueri);

        return $kueri;
    })->all();
}

/*
|--------------------------------------------------------------------------
| Dua akun: yang berhak dan yang tidak
|--------------------------------------------------------------------------
*/

test('akun dewi yang berhak menerima 200 dan melihat harga modalnya', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $dewi = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($dewi)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Panel Surya 450Wp')
        ->assertSee('SN-001')
        ->assertSee('Rp 1.850.000');

    Http::assertSentCount(1);
});

test('akun tanpa izin ditolak CRM sebelum inventory sempat ditanya', function () {
    // Lapis pertama pengamanan. Kalau lapis ini bocor, HPP orang lain ikut terbuka
    // hanya karena seseorang menebak alamat halamannya.
    Http::fake();

    $tanpaIzin = penggunaHargaModal('staf.baru@example.com', denganIzin: false);

    $this->actingAs($tanpaIzin)
        ->get(route('harga-modal.index'))
        ->assertForbidden();

    Http::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Pembatasan per perusahaan
|--------------------------------------------------------------------------
*/

test('pengguna dari perusahaan yang diizinkan bisa membuka halamannya', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $dewi = penggunaHargaModal('dewi.priyambodo@yahoo.com', kodePerusahaan: 'ATC');

    $this->actingAs($dewi)->get(route('harga-modal.index'))->assertOk();

    expect(view('layouts.partials.sidebar')->render())->toContain(route('harga-modal.index'));
});

test('izin yang sama di perusahaan lain tetap ditolak', function () {
    // Role dan izinnya persis sama; yang membedakan hanya perusahaannya. Izin per
    // role tidak bisa memisahkan keduanya, karena rolenya dipakai bersama.
    Http::fake();

    $lain = penggunaHargaModal('sales.as@example.com', kodePerusahaan: 'AS');

    $this->actingAs($lain)->get(route('harga-modal.index'))->assertForbidden();

    Http::assertNothingSent();
});

test('menu sidebar ikut hilang untuk perusahaan yang tidak diizinkan', function () {
    $lain = penggunaHargaModal('sales.as@example.com', kodePerusahaan: 'AS');

    // Menu tidak boleh menawarkan halaman yang ujungnya ditolak.
    $this->actingAs($lain);
    expect(view('layouts.partials.sidebar')->render())->not->toContain(route('harga-modal.index'));
});

test('halaman rincian dijaga pembatasan perusahaan yang sama', function () {
    // Menyembunyikan menu saja tidak mengamankan apa pun; alamatnya bisa diketik.
    Http::fake();

    $lain = penggunaHargaModal('sales.as@example.com', kodePerusahaan: 'AS');

    $this->actingAs($lain)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertForbidden();

    Http::assertNothingSent();
});

test('pengguna tanpa perusahaan ditolak', function () {
    Http::fake();

    $tanpaPerusahaan = penggunaHargaModal('lepas@example.com');
    $tanpaPerusahaan->forceFill(['company_id' => null])->save();

    $this->actingAs($tanpaPerusahaan)->get(route('harga-modal.index'))->assertForbidden();

    Http::assertNothingSent();
});

test('kode perusahaan dicocokkan tanpa memedulikan huruf besar kecil', function () {
    config()->set('services.inventory.perusahaan', ['atc']);
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $dewi = penggunaHargaModal('dewi.priyambodo@yahoo.com', kodePerusahaan: 'ATC');

    $this->actingAs($dewi)->get(route('harga-modal.index'))->assertOk();
});

test('daftar perusahaan yang dikosongkan mematikan pembatasannya', function () {
    // Jalan keluar kalau suatu saat halaman ini berlaku untuk semua perusahaan.
    config()->set('services.inventory.perusahaan', []);
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $lain = penggunaHargaModal('sales.as@example.com', kodePerusahaan: 'AS');

    $this->actingAs($lain)->get(route('harga-modal.index'))->assertOk();
});

test('beberapa perusahaan bisa diizinkan sekaligus', function () {
    config()->set('services.inventory.perusahaan', ['ATC', 'AS']);
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $lain = penggunaHargaModal('sales.as@example.com', kodePerusahaan: 'AS');

    $this->actingAs($lain)->get(route('harga-modal.index'))->assertOk();
});

test('admin mengikuti perusahaan aktif yang sedang dipilihnya', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
    $izin = Permission::where('slug', 'view-harga-modal')->firstOrFail();
    $adminRole->permissions()->syncWithoutDetaching([$izin->id]);

    $arta = perusahaanHargaModal('AS');
    $atc = perusahaanHargaModal('ATC');

    $admin = User::factory()->create(['email' => 'admin@example.com', 'company_id' => $arta->id]);
    $admin->roles()->attach($adminRole->id);

    // Perusahaannya sendiri tidak diizinkan, jadi tanpa memilih apa pun: ditolak.
    $this->actingAs($admin)->get(route('harga-modal.index'))->assertForbidden();

    // Setelah berpindah ke ATC lewat pemilih di header: boleh.
    $this->actingAs($admin)
        ->withSession(['active_company_id' => $atc->id])
        ->get(route('harga-modal.index'))
        ->assertOk();
});

test('tamu yang belum login diarahkan ke halaman masuk', function () {
    Http::fake();

    $this->get(route('harga-modal.index'))->assertRedirect(route('login'));

    Http::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Satu tab satu panggilan
|--------------------------------------------------------------------------
*/

test('tanpa parameter tab, yang ditarik hanya produk jadi', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk();

    // Satu kunjungan, satu panggilan, satu tab -- bukan ketiganya sekaligus.
    Http::assertSentCount(1);
    expect(kueriTerkirim()[0]['tab'])->toBe('produk-jadi');
});

test('tiap tab mengirim nilai tab-nya sendiri', function (string $diminta, string $kunci, array $baris) {
    palsukanInventory(badanTab($kunci, $baris));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => $diminta]))
        ->assertOk();

    Http::assertSentCount(1);
    expect(kueriTerkirim()[0]['tab'])->toBe($diminta);
})->with([
    ['produk-jadi', 'produk_jadi', [['nama_produk' => 'Panel Surya 450Wp', 'harga_modal_satuan' => 1850000]]],
    ['setengah-jadi', 'produk_setengah_jadi', [['nama_produk' => 'BE-MSCAM V.0', 'harga_modal_satuan' => 750000]]],
    ['bahan', 'bahan', [['nama_bahan' => 'Kabel NYAF 1.5mm', 'harga_modal_satuan' => 12000]]],
]);

test('nilai tab yang tidak dikenal jatuh ke produk jadi, bukan diteruskan mentah-mentah', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'semua; drop']))
        ->assertOk();

    expect(kueriTerkirim()[0]['tab'])->toBe('produk-jadi');
});

test('ketiga tab tersedia sebagai tautan, jadi pindah tab berarti satu panggilan baru', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee(route('harga-modal.index', ['tab' => 'produk-jadi']))
        ->assertSee(route('harga-modal.index', ['tab' => 'setengah-jadi']))
        ->assertSee(route('harga-modal.index', ['tab' => 'bahan']));
});

/*
|--------------------------------------------------------------------------
| Saringan hanya_tersedia
|--------------------------------------------------------------------------
*/

test('hanya_tersedia tidak pernah ikut terkirim untuk tampilan bawaan', function () {
    // Stok produk jadi sedang nol untuk seluruh unit. Kalau saringan ini menyala
    // diam-diam, tabnya kosong padahal harga modalnya tetap perlu dilihat.
    palsukanInventory(badanTab('produk_jadi', [barisUnit(['stok_sisa' => 0])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Panel Surya 450Wp');

    expect(kueriTerkirim()[0])->not->toHaveKey('hanya_tersedia');
});

test('hanya_tersedia dikirim hanya kalau kotaknya dicentang', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['hanya_tersedia' => 1]))
        ->assertOk();

    expect(kueriTerkirim()[0]['hanya_tersedia'])->toBe('1');
});

test('kotak centang saringan tersedia dan defaultnya mati', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('name="hanya_tersedia"')
        ->and($isi)->toContain('Hanya yang stoknya tersedia')
        ->and($isi)->not->toContain('name="hanya_tersedia" value="1" checked');
});

test('saringan yang menyala tetap terbawa saat pindah tab', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['hanya_tersedia' => 1]))
        ->assertOk()
        // Tanpa escape: & pada URL ter-render sebagai &amp; di HTML.
        ->assertSee(route('harga-modal.index', ['tab' => 'bahan', 'hanya_tersedia' => 1]));
});

test('tab kosong karena saringan menawarkan jalan keluarnya', function () {
    palsukanInventory(badanTab('produk_jadi', []));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['hanya_tersedia' => 1]))
        ->assertOk()
        ->assertSee('Matikan saringan');
});

/*
|--------------------------------------------------------------------------
| Bidang harga dipatok ke harga_modal_satuan
|--------------------------------------------------------------------------
*/

test('harga dibaca dari harga_modal_satuan', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit(['harga_modal_satuan' => 1850000])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Rp 1.850.000')
        ->assertDontSee('Nilai harga modal tidak ada di jawaban inventory.');
});

test('harga_modal, hpp, dan unit_price tidak pernah dipakai sebagai pengganti', function () {
    // Kalau suatu saat inventory mengirim nama-nama ini dengan arti berbeda,
    // halaman HPP lebih baik menampilkan "-" daripada angka yang keliru.
    palsukanInventory(badanTab('produk_jadi', [[
        'nama_produk' => 'Panel Surya 450Wp',
        'kode_produksi' => 'PJ-0912',
        'serial_number' => 'SN-001',
        'stok_sisa' => 12,
        'harga_modal' => 9111111,
        'hpp' => 9222222,
        'unit_price' => 9333333,
        'sumber' => 'Produksi Internal',
    ]]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertDontSee('Rp 9.111.111')
        ->assertDontSee('Rp 9.222.222')
        ->assertDontSee('Rp 9.333.333')
        ->assertSee('Nilai harga modal tidak ada di jawaban inventory.');
});

/*
|--------------------------------------------------------------------------
| Kode produksi kosong jatuh ke kode unit
|--------------------------------------------------------------------------
*/

test('kode_produksi yang kosong digantikan kode_unit, bukan sel kosong', function () {
    // 123 dari 321 unit setengah jadi memang belum punya kode produksi
    // (data sebelum alur QC), tapi kode unitnya selalu terisi.
    palsukanInventory(badanTab('produk_setengah_jadi', [barisUnit([
        'nama_produk' => 'BE-MSCAM V.0',
        'kode_produksi' => null,
        'kode_unit' => 'UNIT-20260413-0002',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'setengah-jadi']))
        ->assertOk()
        ->assertSee('UNIT-20260413-0002');
});

test('kode_produksi yang ada tetap dipakai, kode_unit tidak menimpanya', function () {
    palsukanInventory(badanTab('produk_setengah_jadi', [barisUnit([
        'kode_produksi' => 'PRD-20260413135218-0002',
        'kode_unit' => 'UNIT-20260413-0002',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'setengah-jadi']))
        ->assertOk()
        ->assertSee('PRD-20260413135218-0002')
        ->assertDontSee('UNIT-20260413-0002');
});

/*
|--------------------------------------------------------------------------
| Tab Bahan
|--------------------------------------------------------------------------
*/

test('tab bahan menampilkan ketiga kolom harga sekaligus', function () {
    palsukanInventory(badanTab('bahan', [barisBahan()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('Kabel NYAF 1.5mm')
        ->assertSee('Harga Beli Terakhir')
        ->assertSee('Rata-rata Tertimbang')
        ->assertSee('Nilai Persediaan')
        ->assertSee('Rp 12.000')
        ->assertSee('Rp 9.500')
        ->assertSee('Rp 1.140.000');
});

test('tab bahan tidak punya kolom kode produksi dan serial', function () {
    palsukanInventory(badanTab('bahan', [barisBahan()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertDontSee('Kode Produksi')
        ->assertDontSee('Serial');
});

test('bahan yang harga terakhirnya jauh dari rata-rata diberi tanda', function () {
    // 12.000 terhadap 9.500 berselisih 26%, di atas ambang 20%.
    palsukanInventory(badanTab('bahan', [
        barisBahan(),
        barisBahan(['nama_bahan' => 'Baut M4', 'harga_modal_satuan' => 1000, 'harga_modal_rata2' => 980]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('1 dari 2 bahan berstok punya selisih di atas 20%')
        ->assertSee('harga terakhir 26% di atas rata-rata');
});

test('harga terakhir yang lebih murah dari rata-rata ditandai turun, bukan naik', function () {
    // Nilai mutlak menyembunyikan arahnya: 7.600 terhadap 9.500 sama-sama
    // berselisih 20%, tapi artinya biaya sedang turun, bukan naik.
    palsukanInventory(badanTab('bahan', [
        barisBahan(['harga_modal_satuan' => 7125, 'harga_modal_rata2' => 9500]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('harga terakhir 25% di bawah rata-rata')
        ->assertDontSee('di atas rata-rata');
});

test('persentase dihitung terhadap rata-rata, bukan terhadap harga terakhir', function () {
    // 12.000 vs 9.500: selisihnya 2.500. Terhadap rata-rata 26%, terhadap harga
    // terakhir 21% -- labelnya harus menyebut pembanding yang benar.
    palsukanInventory(badanTab('bahan', [
        barisBahan(['harga_modal_satuan' => 12000, 'harga_modal_rata2' => 9500]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('harga terakhir 26% di atas rata-rata')
        ->assertDontSee('21%');
});

test('bahan yang harganya rapat tidak diberi tanda', function () {
    palsukanInventory(badanTab('bahan', [
        barisBahan(['harga_modal_satuan' => 1000, 'harga_modal_rata2' => 980]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertDontSee('bahan berstok punya selisih di atas 20%');
});

test('bahan tidak ikut dilebur walau namanya kembar', function () {
    // Baris bahan sudah satu per bahan; meleburnya justru berisiko menyatukan
    // dua bahan berbeda yang kebetulan senama.
    palsukanInventory(badanTab('bahan', [
        barisBahan(['nama_bahan' => 'Kabel NYAF 1.5mm', 'nilai_persediaan' => 1140000]),
        barisBahan(['nama_bahan' => 'Kabel NYAF 1.5mm', 'nilai_persediaan' => 2280000]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('Rp 1.140.000')
        ->assertSee('Rp 2.280.000')
        ->assertDontSee('unit, harga modal sama');
});

/*
|--------------------------------------------------------------------------
| Email dari sesi, kunci tetap di server
|--------------------------------------------------------------------------
*/

test('email yang ditanyakan diambil dari pengguna yang login, bukan email yang ditulis mati', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $lain = penggunaHargaModal('rudi.hartono@example.com');

    $this->actingAs($lain)->get(route('harga-modal.index'))->assertOk();

    expect(kueriTerkirim()[0]['email'])->toBe('rudi.hartono@example.com');
});

test('panggilan membawa header X-API-KEY dan kuncinya tidak ikut ke HTML', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $dewi = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $respons = $this->actingAs($dewi)->get(route('harga-modal.index'))->assertOk();

    Http::assertSent(fn ($request) => $request->hasHeader('X-API-KEY', KUNCI_UJI));

    // Kalau kunci sampai ter-render, siapa pun yang membuka DevTools bisa membacanya.
    $respons->assertDontSee(KUNCI_UJI);
});

/*
|--------------------------------------------------------------------------
| Tiap status punya pesannya sendiri
|--------------------------------------------------------------------------
*/

test('403 dari inventory berarti haknya kurang, bukan sistemnya rusak', function () {
    palsukanInventory(403);

    $pengguna = penggunaHargaModal('belum.berhak@example.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Anda tidak punya akses harga modal.')
        ->assertDontSee('Panel Surya 450Wp');
});

test('404 dari inventory berarti emailnya yang belum terdaftar', function () {
    palsukanInventory(404);

    $pengguna = penggunaHargaModal('orang.baru@example.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Email Anda belum terdaftar di inventory.');
});

test('401 tampil sebagai pesan teknis soal kunci, bukan sebagai salah pengguna', function () {
    palsukanInventory(401);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Inventory menolak kunci API CRM (401)')
        ->assertSee('CRM_API_KEY')
        ->assertDontSee('Anda tidak punya akses harga modal.');
});

test('503 tampil sebagai layanan sedang tidak tersedia', function () {
    palsukanInventory(503);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Layanan inventory sedang tidak tersedia (503)')
        ->assertDontSee('Email Anda belum terdaftar di inventory.');
});

test('inventory yang tidak menjawab dilaporkan sebagai gangguan koneksi, bukan ditelan diam-diam', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Inventory tidak menjawab dalam 10 detik');
});

test('konfigurasi yang belum diisi dikenali sebagai masalah server, bukan ditembak ke inventory', function () {
    config()->set('services.inventory.api_key', '');
    Http::fake();

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('CRM_API_KEY');

    Http::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Isi halaman
|--------------------------------------------------------------------------
*/

test('kesegaran data ditampilkan sebagai data per waktu pengambilan', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('data per 7 Agustus 2026, 09:15');
});

test('kartu ringkasan tidak ada lagi di tab mana pun', function (string $diminta, string $kunci, array $baris) {
    // Angka-angkanya dihitung menyilang produk yang tidak sebanding, jadi
    // terendah/tertinggi/terakhir tidak punya arti dan justru menyesatkan.
    palsukanInventory(badanTab($kunci, $baris));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => $diminta]))
        ->assertOk()
        ->assertDontSee('Jumlah Unit')
        ->assertDontSee('Stok Tersedia')
        ->assertDontSee('Harga Modal Terakhir')
        ->assertDontSee('Harga Modal Terendah')
        ->assertDontSee('Harga Modal Tertinggi')
        ->assertDontSee('Rentang harga modal lebar.');
})->with([
    ['produk-jadi', 'produk_jadi', [['nama_produk' => 'Panel Surya 450Wp', 'harga_modal_satuan' => 1850000]]],
    ['setengah-jadi', 'produk_setengah_jadi', [['nama_produk' => 'BE-MSCAM V.0', 'harga_modal_satuan' => 750000]]],
    ['bahan', 'bahan', [['nama_bahan' => 'Kabel NYAF 1.5mm', 'harga_modal_satuan' => 12000]]],
]);

test('blok ringkasan dari inventory diabaikan, bukan ikut dirender', function () {
    palsukanInventory(badanTab('produk_jadi', [
        'ringkasan' => [
            'jumlah_unit' => 41,
            'harga_modal_terendah' => 1016010,
            'harga_modal_tertinggi' => 17665338,
        ],
        'baris' => [barisUnit()],
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        // Barisnya tetap terbaca walau ada kunci ringkasan di sebelahnya.
        ->assertSee('Panel Surya 450Wp')
        ->assertDontSee('Rp 1.016.010')
        ->assertDontSee('Rp 17.665.338');
});

/*
|--------------------------------------------------------------------------
| Peleburan baris kembar pada tab berbasis unit
|--------------------------------------------------------------------------
*/

test('nama produk dan harga modal yang sama dilebur jadi satu baris dengan stok dijumlahkan', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['kode_produksi' => 'PJ-0912', 'serial_number' => 'SN-001', 'stok_sisa' => 12]),
        barisUnit(['kode_produksi' => 'PJ-0913', 'serial_number' => 'SN-002', 'stok_sisa' => 8]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $respons = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk();

    expect(substr_count($respons->getContent(), 'Panel Surya 450Wp'))->toBe(1);

    $respons->assertSee('2 unit, harga modal sama')
        ->assertSee('+1 kode lainnya')
        ->assertSee('+1 serial lainnya')
        ->assertSee('20');
});

test('nama produk sama tapi harga modal beda tetap tampil terpisah', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['serial_number' => 'SN-001', 'harga_modal_satuan' => 1850000]),
        barisUnit(['serial_number' => 'SN-002', 'harga_modal_satuan' => 2400000]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $respons = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk();

    // Selisih harga justru yang perlu dilihat, jadi tidak boleh dilebur.
    expect(substr_count($respons->getContent(), 'Panel Surya 450Wp'))->toBe(2);

    $respons->assertSee('Rp 1.850.000')
        ->assertSee('Rp 2.400.000')
        ->assertDontSee('unit, harga modal sama');
});

test('baris leburan menyebut berapa unit yang ada di baliknya', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['serial_number' => 'SN-001', 'stok_sisa' => 10]),
        barisUnit(['serial_number' => 'SN-002', 'stok_sisa' => 10]),
        barisUnit(['serial_number' => 'SN-003', 'stok_sisa' => 10]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $respons = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk();

    // Tiga unit jadi satu baris, dan cacahnya tetap terbaca di baris itu --
    // satu-satunya tempat jumlah unit disebut sekarang.
    expect(substr_count($respons->getContent(), 'Panel Surya 450Wp'))->toBe(1);

    $respons->assertSee('3 unit, harga modal sama')->assertSee('30');
});

test('leburan tanpa angka harga tidak mengklaim harganya sama', function () {
    palsukanInventory(badanTab('produk_jadi', [
        ['nama_produk' => 'BE - CST - 110', 'kode_produksi' => 'BE-CST-110-00024', 'serial_number' => 'SN-A', 'stok_sisa' => 0, 'sumber' => 'Produksi'],
        ['nama_produk' => 'BE - CST - 110', 'kode_produksi' => 'BE-CST-110-00025', 'serial_number' => 'SN-B', 'stok_sisa' => 0, 'sumber' => 'Produksi'],
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // Yang sama di sini adalah ketiadaan angkanya, bukan harganya.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('2 unit,')
        ->assertSee('harga modal belum tersedia')
        ->assertDontSee('harga modal sama');
});

test('baris tanpa nama produk tidak pernah dilebur', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['nama_produk' => '', 'serial_number' => 'SN-001']),
        barisUnit(['nama_produk' => '', 'serial_number' => 'SN-002']),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('SN-001')
        ->assertSee('SN-002')
        ->assertDontSee('unit, harga modal sama');
});

/*
|--------------------------------------------------------------------------
| Pencarian dan saringan per tab
|--------------------------------------------------------------------------
*/

test('kotak pencarian tersedia di setiap tab', function (string $diminta, string $kunci, array $baris) {
    palsukanInventory(badanTab($kunci, $baris));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => $diminta]))
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('id="cari-harga-modal"')
        ->and($isi)->toContain('name="cari"');
})->with([
    ['produk-jadi', 'produk_jadi', [['nama_produk' => 'Panel Surya 450Wp', 'harga_modal_satuan' => 1850000]]],
    ['setengah-jadi', 'produk_setengah_jadi', [['nama_produk' => 'BE-MSCAM V.0', 'harga_modal_satuan' => 750000]]],
    ['bahan', 'bahan', [['nama_bahan' => 'Kabel NYAF 1.5mm', 'harga_modal_satuan' => 12000]]],
]);

test('bilah saringan berada di luar kontainer bergulir milik tabel', function () {
    // Kalau bilahnya ikut masuk ke dalam overflow-x-auto, ia akan tergeser dan
    // terpotong begitu tabel yang lebar digulir ke samping.
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    $posisiCari = strpos($isi, 'id="cari-harga-modal"');
    $posisiGulir = strpos($isi, 'overflow-x-auto');

    expect($posisiCari)->not->toBeFalse()
        ->and($posisiGulir)->not->toBeFalse()
        ->and($posisiCari)->toBeLessThan($posisiGulir);
});

test('pilihan sumber disusun dari nilai yang benar-benar ada', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['serial_number' => 'SN-001', 'sumber' => 'Produksi Internal']),
        barisUnit(['serial_number' => 'SN-002', 'sumber' => 'Pembelian Vendor', 'harga_modal_satuan' => 2400000]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('<option value="">Semua sumber</option>')
        ->and($isi)->toContain('<option value="Produksi Internal"')
        ->and($isi)->toContain('<option value="Pembelian Vendor"');
});

test('pencarian mencocokkan nama, kode produksi, serial, dan sumber', function (string $kueri) {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['nama_produk' => 'Panel Surya 450Wp', 'kode_produksi' => 'PJ-0912', 'serial_number' => 'SN-AAA', 'sumber' => 'Produksi Internal']),
        barisUnit(['nama_produk' => 'Inverter Hybrid 5kW', 'kode_produksi' => 'PJ-9999', 'serial_number' => 'SN-ZZZ', 'sumber' => 'Pembelian Vendor', 'harga_modal_satuan' => 5000000]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['cari' => $kueri]))
        ->assertOk()
        ->assertSee('Panel Surya 450Wp')
        ->assertDontSee('Inverter Hybrid 5kW');
})->with([
    'nama' => ['panel surya'],
    'kode produksi' => ['PJ-0912'],
    'serial' => ['SN-AAA'],
    'sumber' => ['Produksi Internal'],
]);

test('pencarian tidak peduli huruf besar kecil', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['nama_produk' => 'Panel Surya 450Wp']),
        barisUnit(['nama_produk' => 'Inverter Hybrid 5kW', 'harga_modal_satuan' => 5000000]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['cari' => 'PANEL SURYA']))
        ->assertOk()
        ->assertSee('Panel Surya 450Wp')
        ->assertDontSee('Inverter Hybrid 5kW');
});

test('saringan sumber menyisakan hanya baris dari sumber itu', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['nama_produk' => 'Panel Surya 450Wp', 'sumber' => 'Produksi Internal']),
        barisUnit(['nama_produk' => 'Inverter Hybrid 5kW', 'sumber' => 'Pembelian Vendor', 'harga_modal_satuan' => 5000000]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['sumber' => 'Pembelian Vendor']))
        ->assertOk()
        ->assertSee('Inverter Hybrid 5kW')
        ->assertDontSee('Panel Surya 450Wp');
});

test('saringan yang tidak menyisakan apa pun menjelaskan sebabnya', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['cari' => 'tidak-ada-yang-begini']))
        ->assertOk()
        ->assertSee('Tidak ada baris yang cocok dengan pencarian atau saringan.')
        ->assertSee('Bersihkan saringan');
});

test('menyimpang=1 menyisakan hanya bahan yang selisihnya lebar', function () {
    palsukanInventory(badanTab('bahan', [
        barisBahan(['nama_bahan' => 'Kabel NYAF 1.5mm', 'harga_modal_satuan' => 12000, 'harga_modal_rata2' => 9500]),
        barisBahan(['nama_bahan' => 'Baut M4', 'harga_modal_satuan' => 1000, 'harga_modal_rata2' => 980]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan', 'menyimpang' => 1]))
        ->assertOk()
        ->assertSee('Kabel NYAF 1.5mm')
        ->assertDontSee('Baut M4');
});

test('saringan selisih hanya ada di tab bahan', function () {
    palsukanInventory(badanTab('bahan', [barisBahan()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('Hanya selisih', escape: false);

    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertDontSee('Hanya selisih', escape: false);
});

/*
|--------------------------------------------------------------------------
| Paginasi
|--------------------------------------------------------------------------
*/

function banyakBarisUnit(int $jumlah): array
{
    // Nama dibedakan supaya barisnya tidak dilebur jadi satu oleh aturan
    // nama + harga yang sama.
    return collect(range(1, $jumlah))->map(fn (int $n) => barisUnit([
        'nama_produk' => sprintf('Produk %03d', $n),
        'kode_produksi' => sprintf('PJ-%04d', $n),
        'serial_number' => sprintf('SN-%04d', $n),
        'harga_modal_satuan' => 1000000 + $n,
    ]))->all();
}

function banyakBarisBahan(int $jumlah, int $menyimpangDiAkhir = 0): array
{
    $batasMenyimpang = $jumlah - $menyimpangDiAkhir;

    return collect(range(1, $jumlah))->map(function (int $n) use ($batasMenyimpang) {
        // Yang menyimpang ditaruh di ujung daftar, jadi halaman pertama tidak memuat
        // satu pun -- hitungan di atas tabel tetap harus menyebut semuanya.
        $lebar = $n > $batasMenyimpang;

        return barisBahan([
            'nama_bahan' => sprintf('Bahan %04d', $n),
            'harga_modal_satuan' => $lebar ? 12000 : 1000,
            'harga_modal_rata2' => $lebar ? 9500 : 980,
        ]);
    })->all();
}

test('halaman pertama memuat 50 baris saja, bukan seluruhnya', function () {
    palsukanInventory(badanTab('produk_jadi', banyakBarisUnit(120)));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Produk 001')
        ->assertSee('Produk 050')
        ->assertDontSee('Produk 051')
        ->assertSee('Menampilkan 1-50 dari 120 baris');
});

test('halaman kedua memuat penggalan berikutnya', function () {
    palsukanInventory(badanTab('produk_jadi', banyakBarisUnit(120)));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['halaman' => 2]))
        ->assertOk()
        ->assertSee('Produk 051')
        ->assertSee('Produk 100')
        ->assertDontSee('Produk 050')
        ->assertDontSee('Produk 101')
        ->assertSee('Menampilkan 51-100 dari 120 baris');
});

test('nomor halaman di luar jangkauan dijepit ke halaman terakhir', function () {
    palsukanInventory(badanTab('produk_jadi', banyakBarisUnit(120)));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // Tabel kosong tanpa penjelasan lebih membingungkan daripada halaman terakhir.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['halaman' => 999]))
        ->assertOk()
        ->assertSee('Produk 120')
        ->assertSee('Menampilkan 101-120 dari 120 baris');
});

test('tautan halaman membawa tab dan saringan yang sedang berlaku', function () {
    palsukanInventory(badanTab('bahan', banyakBarisBahan(120)));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan', 'cari' => 'Bahan']))
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('halaman=2')
        ->and($isi)->toContain('tab=bahan')
        // Tanpa ini, klik halaman 2 akan membuang kata pencariannya.
        ->and($isi)->toContain('cari=Bahan');
});

test('halaman tunggal tidak memunculkan tautan paginasi', function () {
    palsukanInventory(badanTab('produk_jadi', banyakBarisUnit(10)));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->not->toContain('halaman=2');
});

test('pencarian menjangkau seluruh tab, bukan cuma halaman yang tampil', function () {
    palsukanInventory(badanTab('produk_jadi', banyakBarisUnit(120)));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // Produk 117 ada di halaman ketiga. Kalau penyaringan dilakukan sesudah
    // pemenggalan, baris ini tidak akan pernah ketemu.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['cari' => 'Produk 117']))
        ->assertOk()
        ->assertSee('Produk 117')
        ->assertSee('Menampilkan 1-1 dari 1 baris');
});

test('paginasi mengikuti jumlah hasil saringan, bukan jumlah seluruh baris', function () {
    palsukanInventory(badanTab('produk_jadi', banyakBarisUnit(120)));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // Tanpa saringan ada 120 baris alias 3 halaman. 'Produk 0' hanya cocok dengan
    // 001-099, jadi paginasinya harus menyusut jadi 2 halaman.
    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['cari' => 'Produk 0']))
        ->assertOk()
        ->assertSee('Menampilkan 1-50 dari 99 baris')
        ->getContent();

    expect($isi)->toContain('halaman=2')
        ->and($isi)->not->toContain('halaman=3');
});

test('hitungan simpangan menghitung seluruh bahan, bukan halaman yang tampil', function () {
    // 10 bahan menyimpang, semuanya di ujung daftar sehingga tidak satu pun
    // muncul di halaman pertama.
    palsukanInventory(badanTab('bahan', banyakBarisBahan(60, menyimpangDiAkhir: 10)));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('10 dari 60 bahan berstok punya selisih di atas 20%')
        // Penanda per barisnya memang belum terlihat di halaman ini.
        ->assertDontSee('harga terakhir 26% di atas rata-rata');
});

test('bahan dipenggal per 50 seperti tab lainnya', function () {
    palsukanInventory(badanTab('bahan', banyakBarisBahan(120)));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('Bahan 0001')
        ->assertSee('Bahan 0050')
        ->assertDontSee('Bahan 0051')
        ->assertSee('Menampilkan 1-50 dari 120 bahan');
});

/*
|--------------------------------------------------------------------------
| Thumbnail gambar bahan
|--------------------------------------------------------------------------
*/

test('gambar_url ditampilkan sebagai thumbnail', function () {
    palsukanInventory(badanTab('bahan', [
        barisBahan(['nama_bahan' => 'Kabel NYAF 1.5mm', 'gambar_url' => 'https://inventory.internal/img/kabel.png']),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('src="https://inventory.internal/img/kabel.png"')
        ->and($isi)->toContain('alt="Gambar Kabel NYAF 1.5mm"')
        // Gambar yang gagal dimuat menyingkir supaya placeholder di belakangnya terlihat.
        ->and($isi)->toContain('onerror="this.remove()"');
});

test('gambar_url null menyisakan placeholder, bukan sel kosong', function () {
    palsukanInventory(badanTab('bahan', [barisBahan(['gambar_url' => null])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->getContent();

    // Penanda khusus thumbnail: logo di sidebar juga sebuah <img>.
    expect($isi)->toContain('ri-image-line')
        ->and($isi)->not->toContain('data-thumbnail');
});

test('jalur gambar relatif disambung ke alamat inventory', function () {
    palsukanInventory(badanTab('bahan', [barisBahan(['gambar_url' => '/storage/bahan/1.png'])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('https://inventory.internal/storage/bahan/1.png', escape: false);
});

test('gambar_url berskema selain http diabaikan', function (string $jahat) {
    // Nilainya berakhir di dalam atribut src, jadi skema selain http/https tidak
    // pernah dipasang -- yang tampil placeholder.
    palsukanInventory(badanTab('bahan', [barisBahan(['gambar_url' => $jahat])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->getContent();

    expect($isi)->not->toContain('data-thumbnail')
        ->and($isi)->toContain('ri-image-line');
})->with([
    'javascript' => ['javascript:alert(1)'],
    'data' => ['data:text/html;base64,PHNjcmlwdD4='],
    'file' => ['file:///etc/passwd'],
    'protokol relatif' => ['//jahat.example.com/x.png'],
]);

/*
|--------------------------------------------------------------------------
| Gambar produk di tab unit
|--------------------------------------------------------------------------
*/

test('gambar produk tampil satu sel bersama nama dan serial, bukan kolom terpisah', function (string $diminta, string $kunci) {
    palsukanInventory(badanTab($kunci, [barisUnit([
        'nama_produk' => 'Panel Surya 450Wp',
        'serial_number' => 'SN-001',
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc123',
        'link_gambar' => 'https://drive.google.com/file/d/abc123/view',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => $diminta]))
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('src="https://lh3.googleusercontent.com/d/abc123"')
        ->and($isi)->toContain('Panel Surya 450Wp')
        ->and($isi)->toContain('SN-001')
        // Serial pindah ke sel produk, jadi tidak ada lagi kolomnya sendiri.
        ->and($isi)->not->toContain('>Serial<');
})->with([
    ['produk-jadi', 'produk_jadi'],
    ['setengah-jadi', 'produk_setengah_jadi'],
]);

test('gambar dibungkus tautan ke link_gambar yang aman dibuka di tab baru', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc123',
        'link_gambar' => 'https://drive.google.com/file/d/abc123/view',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('href="https://drive.google.com/file/d/abc123/view"')
        ->and($isi)->toContain('target="_blank"')
        ->and($isi)->toContain('rel="noopener noreferrer"');
});

test('tag gambar memenuhi aturan lazy, referrerpolicy, dan onerror', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc123',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('loading="lazy"')
        // Drive kadang menolak permintaan gambar yang membawa referrer domain lain.
        ->and($isi)->toContain('referrerpolicy="no-referrer"')
        ->and($isi)->toContain('onerror="this.remove()"')
        ->and($isi)->toContain('object-cover');
});

test('gambar_url null menampilkan placeholder, bukan img tanpa src', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => null,
        'link_gambar' => null,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('ri-image-line')
        ->and($isi)->not->toContain('data-thumbnail')
        ->and($isi)->not->toContain('src=""');
});

test('kotak gambar berukuran tetap untuk ketiga keadaannya', function () {
    // Tanpa ukuran tetap, tinggi baris loncat-loncat mengikuti ada tidaknya foto.
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['nama_produk' => 'Dengan Foto', 'gambar_url' => 'https://lh3.googleusercontent.com/d/a']),
        barisUnit(['nama_produk' => 'Tanpa Foto', 'gambar_url' => null, 'harga_modal_satuan' => 2400000]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Dua baris, dua kotak, dan keduanya berukuran sama meski yang satu tanpa foto.
    expect(substr_count($isi, 'data-kotak-gambar'))->toBe(2)
        ->and(substr_count($isi, 'data-kotak-gambar class="relative h-16 w-16'))->toBe(2);
});

test('klik gambar membuka pratinjau, bukan berpindah halaman', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'nama_produk' => 'Panel Surya 450Wp',
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc123',
        'link_gambar' => 'https://drive.google.com/file/d/abc123/view',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('x-data="pratinjauGambar"')
        ->and($isi)->toContain('window.bukaPratinjauGambar($event)')
        ->and($isi)->toContain('data-gambar="https://lh3.googleusercontent.com/d/abc123"')
        // Judulnya kode unit: satu nama produk bisa punya banyak unit.
        ->and($isi)->toContain('data-judul="UNIT-0001"')
        ->and($isi)->toContain('data-tautan="https://drive.google.com/file/d/abc123/view"')
        // Tanpa ini tombolnya tertinggal dalam keadaan "Membuka..." selamanya.
        ->and($isi)->toContain('data-no-link-loading');
});

test('pratinjau memperbesar gambar utuh, bukan dipotong seperti thumbnail', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc123',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('object-contain')
        ->and($isi)->toContain('Buka di Google Drive')
        // Gambar besar pun perlu ini; Drive menolak referrer dari domain lain.
        ->and($isi)->toContain('referrerpolicy="no-referrer"');
});

test('tautan gambar tetap sungguhan supaya Ctrl-klik masih bekerja', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc123',
        'link_gambar' => null,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Tanpa link_gambar, gambarnya sendiri yang dituju -- bukan tautan mati.
    expect($isi)->toContain('href="https://lh3.googleusercontent.com/d/abc123"')
        ->and($isi)->toContain('window.bukaPratinjauGambar($event)');
});

test('baris tanpa gambar tidak dibuat bisa diklik', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => null,
        'link_gambar' => null,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Tidak ada yang bisa diperbesar, jadi jangan ditawarkan.
    expect($isi)->toContain('data-kotak-gambar')
        ->and($isi)->not->toContain('bukaPratinjauGambar');
});

test('tautan Drive disematkan sebagai pratinjau Drive', function (string $link, string $id) {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => 'https://lh3.googleusercontent.com/d/'.$id,
        'link_gambar' => $link,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('data-sematan="https://drive.google.com/file/d/'.$id.'/preview"')
        ->and($isi)->toContain('<iframe');
})->with([
    'bentuk /file/d/<id>/view' => ['https://drive.google.com/file/d/ABC_123-xyz/view?usp=sharing', 'ABC_123-xyz'],
    'bentuk /open?id=<id>' => ['https://drive.google.com/open?id=ABC_123-xyz', 'ABC_123-xyz'],
]);

test('tautan bukan Drive tidak dipaksa jadi iframe', function (string $link) {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => 'https://cdn.example.com/foto.jpg',
        'link_gambar' => $link,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Menyematkan host yang menolak hanya menghasilkan bingkai kosong tanpa pesan.
    expect($isi)->toContain('data-sematan=""')
        ->and($isi)->toContain('Buka di tab baru')
        ->and($isi)->toContain('bukan tautan Google Drive');
})->with([
    'host lain' => ['https://cdn.example.com/foto.jpg'],
    // Host palsu yang kebetulan memuat pola /d/ tidak boleh lolos.
    'host mirip' => ['https://drive.google.com.jahat.example/file/d/ABC123/view'],
]);

test('judul pratinjau memakai kode unit', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'nama_produk' => 'Panel Surya 450Wp',
        'kode_unit' => 'UNIT-20260413-0002',
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('data-judul="UNIT-20260413-0002"')
        // Alt tetap deskriptif untuk pembaca layar.
        ->and($isi)->toContain('alt="Gambar Panel Surya 450Wp"');
});

test('pratinjau bisa ditutup lewat tombol, area luar, dan Esc', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('x-on:keydown.escape.window="tutupGambar()"')
        ->and($isi)->toContain('aria-label="Tutup pratinjau"')
        // Klik latar juga menutup.
        ->and(substr_count($isi, 'tutupGambar()'))->toBeGreaterThanOrEqual(4);
});

test('klik foto tidak boleh memicu modal rincian', function () {
    // Sewaktu komponen pratinjau membungkus tabel, keduanya sama-sama punya
    // keadaan "terbuka"; penulisan dari dalam mendarat di komponen terdekat dan
    // yang terbuka justru modal rincian. Pemicunya kini lewat event window.
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'produksi_id' => 'PRD-778',
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc123',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    $posisiFoto = strpos($isi, 'window.bukaPratinjauGambar($event)');
    $posisiRincian = strpos($isi, 'window.bukaRincianBahan($event)');

    // Keduanya ada, dan pemicunya benar-benar berbeda.
    expect($posisiFoto)->not->toBeFalse()
        ->and($posisiRincian)->not->toBeFalse()
        ->and($posisiFoto)->not->toBe($posisiRincian)
        // Jendela pratinjau mendengarkan event, bukan dipanggil lewat scope Alpine.
        ->and($isi)->toContain('x-on:pratinjau-gambar.window="terimaGambar($event.detail)"');
});

test('foto produk dan nama produk punya kepala kolomnya sendiri', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Foto Produk')
        ->assertSee('Nama Produk');
});

test('thumbnail tab bahan juga bisa diperbesar', function () {
    palsukanInventory(badanTab('bahan', [barisBahan([
        'gambar_url' => 'https://inventory.internal/img/kabel.png',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('x-data="pratinjauGambar"')
        ->and($isi)->toContain('bukaPratinjauGambar($event)');
});

test('link_gambar berskema berbahaya tidak pernah jadi href', function (string $jahat) {
    // Berbeda dari src, `javascript:` pada href benar-benar dijalankan saat diklik.
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc123',
        'link_gambar' => $jahat,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Gambarnya tetap tampil, hanya tautannya yang dilepas.
    expect($isi)->toContain('data-thumbnail')
        ->and($isi)->not->toContain('javascript:')
        ->and($isi)->not->toContain('href="'.$jahat.'"');
})->with([
    'javascript' => ['javascript:alert(1)'],
    'data' => ['data:text/html;base64,PHNjcmlwdD4='],
]);

test('gambar tanpa link_gambar tampil tanpa pembungkus tautan', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'gambar_url' => 'https://lh3.googleusercontent.com/d/abc123',
        'link_gambar' => null,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('data-thumbnail')
        ->and($isi)->not->toContain('Buka gambar penuh');
});

/*
|--------------------------------------------------------------------------
| Tombol Lihat Bahan dan halaman rincian
|--------------------------------------------------------------------------
*/

test('tombol Lihat Bahan menunjuk rincian dengan tipe dan produksi_id baris itu', function (string $diminta, string $kunci) {
    palsukanInventory(badanTab($kunci, [barisUnit(['produksi_id' => 'PRD-778'])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => $diminta]))
        ->assertOk()
        ->assertSee('Lihat Bahan')
        ->assertSee(route('harga-modal.rincian', [
            'tipe' => $diminta,
            'produksi_id' => 'PRD-778',
            'kode' => 'PJ-0912',
        ]));
})->with([
    ['produk-jadi', 'produk_jadi'],
    ['setengah-jadi', 'produk_setengah_jadi'],
]);

test('produksi_id null menonaktifkan tombolnya', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit(['produksi_id' => null])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Bukan kegagalan: batch produksinya memang tidak tercatat.
    expect($isi)->toContain('disabled')
        ->and($isi)->toContain('Rincian bahan tidak ada di sistem untuk baris ini.')
        ->and($isi)->not->toContain('harga-modal/rincian');
});

test('baris leburan mengaku rinciannya hanya mewakili satu batch produksi', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['serial_number' => 'SN-001', 'produksi_id' => 'PRD-001']),
        barisUnit(['serial_number' => 'SN-002', 'produksi_id' => 'PRD-002']),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // Dua unit senama dan seharga dilebur, tapi batch produksinya berbeda.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('1 dari 2 produksi di baris ini')
        ->assertSee(route('harga-modal.rincian', [
            'tipe' => 'produk-jadi',
            'produksi_id' => 'PRD-001',
            'kode' => 'PJ-0912',
        ]));
});

test('tombol Lihat Bahan membawa nama dan kode produk untuk head modal', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'nama_produk' => 'Panel Surya 450Wp',
        'kode_produksi' => 'PJ-0912',
        'produksi_id' => 'PRD-778',
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Judul modal terisi dari baris yang diklik, jadi sudah terbaca sebelum
    // datanya selesai dimuat.
    expect($isi)->toContain('data-nama="Panel Surya 450Wp"')
        ->and($isi)->toContain('data-kode="PJ-0912"')
        ->and($isi)->toContain('data-produksi="PRD-778"')
        ->and($isi)->toContain('window.bukaRincianBahan($event)')
        ->and($isi)->toContain('x-data="rincianBahan"');
});

test('tombol Lihat Bahan menolak penanda loading tautan global', function () {
    // Penanda itu dipasang di fase capture, sebelum Alpine sempat membatalkan klik.
    // Karena halamannya tidak pernah berpindah, tombolnya akan tertinggal dalam
    // keadaan "Membuka..." selamanya setelah modal ditutup.
    palsukanInventory(badanTab('produk_jadi', [barisUnit(['produksi_id' => 'PRD-778'])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('data-no-link-loading');
});

test('harga modal per unit dibawa ke modal dari baris produksinya', function () {
    // Rincian bahan tidak membawa harga; angka yang berlaku ada di baris produksi.
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'produksi_id' => 'PRD-778',
        'harga_modal_satuan' => 12530172,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Sudah diformat di Blade, jadi JavaScript tidak merakit ulang angkanya.
    expect($isi)->toContain('data-harga="Rp 12.530.172"')
        ->and($isi)->toContain('Harga Modal / Unit');
});

test('harga per unit tidak pernah lewat URL', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'produksi_id' => 'PRD-778',
        'harga_modal_satuan' => 12530172,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // HPP di query string akan mengendap di riwayat browser dan log akses server.
    expect($isi)->not->toContain('harga=12530172')
        ->and($isi)->not->toContain('harga=Rp');
});

test('baris tanpa harga tidak memunculkan kolom harga di modal', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit([
        'produksi_id' => 'PRD-778',
        'harga_modal_satuan' => null,
    ])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('data-harga=""');
});

test('rincian memetakan bidangnya sendiri, bukan bidang tab Bahan', function () {
    Http::fake(['*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([barisRincian()]), 200)]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertSee('Kabel NYAF 1.5mm')
        ->assertSee('BHN-0042')
        ->assertSee('BATCH-2606')
        ->assertSee('Kabel')
        ->assertSee('Rp 9.500')
        ->assertSee('Rp 114.000');
});

test('kolom milik tab Bahan tidak ikut terbawa ke rincian', function () {
    // Bug sebelumnya: komponen tabel tab Bahan dipakai ulang, padahal bentuk
    // balikannya berbeda -- empat kolomnya jadi deretan strip.
    Http::fake(['*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([barisRincian()]), 200)]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertDontSee('Stok Sisa')
        ->assertDontSee('Rata-rata Tertimbang')
        ->assertDontSee('Nilai Persediaan')
        ->assertDontSee('Harga Beli Terakhir')
        ->assertDontSee('Sumber');
});

test('kepala rincian memperlihatkan total bahan dibagi jumlah produksi', function () {
    Http::fake(['*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([barisRincian()]), 200)]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // 426.025.848 / 34 = 12.530.172 -- angka yang sama dengan kolom di tab.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertSee('Total Biaya Bahan')
        ->assertSee('Rp 426.025.848')
        ->assertSee('Jumlah Produksi')
        ->assertSee('Harga Modal / Unit')
        ->assertSee('Rp 12.530.172');
});

test('angka kepala yang tidak dikirim tidak memunculkan bloknya', function () {
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(['rincian' => [barisRincian()]], 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertSee('Kabel NYAF 1.5mm')
        ->assertDontSee('Total Biaya Bahan');
});

test('bidang yang datang sebagai objek tidak menjatuhkan tabelnya', function () {
    // Penyebab 500 sebelumnya: nilai bersarang diteruskan apa adanya ke Blade,
    // lalu htmlspecialchars() melempar dan seluruh rincian gagal dirender.
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([
            barisRincian([
                'jenis' => ['id' => 3, 'nama' => 'Kabel'],
                'batch' => ['id' => 9, 'kode' => 'BATCH-2606'],
            ]),
        ]), 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // Nama dan kode di dalam objeknya yang dipakai.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertSee('Kabel')
        ->assertSee('BATCH-2606');
});

test('objek tanpa nama atau kode di dalamnya jadi sel kosong, bukan tebakan', function () {
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([
            barisRincian(['jenis' => ['id' => 3, 'urutan' => 7]]),
        ]), 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertSee('Kabel NYAF 1.5mm')
        // Nilai apa pun dari dalam objek itu hanya akan jadi tebakan.
        ->assertDontSee('urutan');
});

test('bidang bersarang di tab unit juga tidak menjatuhkan halaman', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['sumber' => ['id' => 1, 'nama' => 'Produksi Internal']]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index'))
        ->assertOk()
        ->assertSee('Produksi Internal');
});

test('qty pecahan tampil apa adanya, qty bulat tanpa koma', function () {
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([
            barisRincian(['nama' => 'Kabel Serabut', 'qty' => 2.5]),
            barisRincian(['nama' => 'Baut M4', 'qty' => 8]),
        ]), 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('2,5')
        ->and($isi)->not->toContain('8,00');
});

test('tab bahan tanpa angka harga juga menyembunyikan kolomnya', function () {
    palsukanInventory(badanTab('bahan', [
        ['nama_bahan' => 'Kabel NYAF 1.5mm', 'stok_sisa' => 12, 'sumber' => 'Pembelian'],
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertSee('Kabel NYAF 1.5mm')
        ->assertSee('Stok Sisa')
        ->assertDontSee('Harga Beli Terakhir');
});

test('modal hanya dipasang di tab berbasis unit', function () {
    palsukanInventory(badanTab('bahan', [barisBahan()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // Tab Bahan tidak punya tombol Lihat Bahan, jadi tidak perlu modalnya.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertDontSee('judul-rincian-bahan');
});

test('fragmen membalas hanya isi rincian, tanpa kerangka halaman', function () {
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([barisRincian()]), 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778', 'fragmen' => 1]))
        ->assertOk()
        ->getContent();

    // Isinya ada, tapi tanpa <html>/sidebar -- kalau tidak, modal akan menelan
    // seluruh halaman ke dalam dirinya.
    expect($isi)->toContain('Kabel NYAF 1.5mm')
        ->and($isi)->toContain('Harga Satuan')
        ->and($isi)->not->toContain('<!doctype html')
        ->and($isi)->not->toContain('application-sidebar');
});

test('fragmen dan halaman penuh memakai berkas isi yang sama', function () {
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([barisRincian()]), 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    foreach ([['fragmen' => 1], []] as $tambahan) {
        $this->actingAs($pengguna)
            ->get(route('harga-modal.rincian', array_merge(['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778'], $tambahan)))
            ->assertOk()
            ->assertSee('Kabel NYAF 1.5mm')
            ->assertSee('Rp 9.500')
            ->assertSee('Rp 114.000')
            ->assertSee('Menampilkan 1-1 dari 1 bahan.');
    }
});

test('kegagalan pada permintaan fragmen tetap terbaca di dalam modal', function () {
    Http::fake(['*/api/crm/harga-modal/rincian*' => Http::response([], 403)]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // 200 dengan kartu pesan di dalamnya, bukan status galat -- supaya modal
    // menampilkan sebabnya, bukan pesan "gagal dimuat" yang samar.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778', 'fragmen' => 1]))
        ->assertOk()
        ->assertSee('Anda tidak punya akses harga modal.');
});

test('permintaan fragmen yang tautannya tidak lengkap tidak diarahkan', function () {
    Http::fake();

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // Kalau diarahkan, modal akan memasang seluruh halaman tujuan sebagai isinya.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'fragmen' => 1]))
        ->assertStatus(422);

    Http::assertNothingSent();
});

test('tautan paginasi di dalam fragmen tidak membawa fragmen=1', function () {
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(badanRincian(banyakBarisRincian(120)), 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778', 'fragmen' => 1]))
        ->assertOk()
        ->getContent();

    // JavaScript modal yang menambahkan fragmen=1, jadi tautannya tetap tautan
    // wajar kalau dibuka langsung.
    expect($isi)->toContain('halaman=2')
        ->and($isi)->toContain('produksi_id=PRD-778')
        ->and($isi)->not->toContain('fragmen=1');
});

test('halaman rincian memanggil endpoint rincian dengan tipe dan produksi_id', function () {
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([barisRincian()]), 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'setengah-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertSee('Rincian Bahan')
        ->assertSee('Kabel NYAF 1.5mm')
        ->assertSee('Harga Satuan');

    $kueri = kueriTerkirim()[0];

    expect($kueri['tipe'])->toBe('setengah-jadi')
        ->and($kueri['produksi_id'])->toBe('PRD-778')
        ->and($kueri['email'])->toBe('dewi.priyambodo@yahoo.com');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/crm/harga-modal/rincian')
        && $request->hasHeader('X-API-KEY', KUNCI_UJI));
});

test('halaman rincian tidak pernah membocorkan kunci ke HTML', function () {
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(badanRincian([barisRincian()]), 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertDontSee(KUNCI_UJI);
});

test('rincian tanpa produksi_id atau bertipe salah dikembalikan ke halaman utama', function (array $kueri) {
    Http::fake();

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', $kueri))
        ->assertRedirect(route('harga-modal.index'));

    Http::assertNothingSent();
})->with([
    'tanpa produksi_id' => [['tipe' => 'produk-jadi']],
    'produksi_id kosong' => [['tipe' => 'produk-jadi', 'produksi_id' => '  ']],
    'tanpa tipe' => [['produksi_id' => 'PRD-778']],
    'tipe tak dikenal' => [['tipe' => 'sembarang', 'produksi_id' => 'PRD-778']],
    // Bahan tidak punya "bahan di dalamnya".
    'tipe bahan' => [['tipe' => 'bahan', 'produksi_id' => 'PRD-778']],
]);

test('rincian tetap dijaga izin yang sama', function () {
    Http::fake();

    $tanpaIzin = penggunaHargaModal('staf.baru@example.com', denganIzin: false);

    $this->actingAs($tanpaIzin)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertForbidden();

    Http::assertNothingSent();
});

test('kegagalan pada rincian memakai pesan yang sama seperti halaman utama', function (int $status, string $pesan) {
    Http::fake(['*/api/crm/harga-modal/rincian*' => Http::response([], $status)]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertSee($pesan);
})->with([
    [403, 'Anda tidak punya akses harga modal.'],
    [404, 'Email Anda belum terdaftar di inventory.'],
    [503, 'Layanan inventory sedang tidak tersedia (503)'],
]);

test('rincian yang tidak mengembalikan bahan dikatakan apa adanya', function () {
    Http::fake(['*/api/crm/harga-modal/rincian*' => Http::response(['rincian' => []], 200)]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertSee('Inventory tidak mengembalikan bahan apa pun untuk batch produksi ini.');
});

test('rincian dipenggal per 50 juga', function () {
    Http::fake([
        '*/api/crm/harga-modal/rincian*' => Http::response(badanRincian(banyakBarisRincian(120)), 200),
    ]);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.rincian', ['tipe' => 'produk-jadi', 'produksi_id' => 'PRD-778']))
        ->assertOk()
        ->assertSee('Bahan 0001')
        ->assertSee('Bahan 0050')
        ->assertDontSee('Bahan 0051')
        ->assertSee('Menampilkan 1-50 dari 120 bahan.');
});

/*
|--------------------------------------------------------------------------
| Margin dan harga jual
|--------------------------------------------------------------------------
*/

test('kolom harga jual ada di kanan harga modal pada tab unit', function (string $diminta, string $kunci) {
    palsukanInventory(badanTab($kunci, [barisUnit(['harga_modal_satuan' => 12530171.82])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => $diminta]))
        ->assertOk()
        ->getContent();

    // Urutannya: Harga Modal / Unit, lalu Margin, lalu Harga Jual. Kolom Margin
    // ditandai lewat sub-labelnya, karena kata "Margin" sendiri juga dipakai
    // bilah "Margin target" di atas tabel.
    expect($isi)->toContain('Harga Jual')
        ->and($isi)->toContain('bisa diubah per baris')
        ->and(strpos($isi, 'Harga Modal / Unit'))->toBeLessThan(strpos($isi, 'bisa diubah per baris'))
        ->and(strpos($isi, 'bisa diubah per baris'))->toBeLessThan(strpos($isi, 'Harga Jual'));
})->with([
    ['produk-jadi', 'produk_jadi'],
    ['setengah-jadi', 'produk_setengah_jadi'],
]);

test('harga jual sudah terisi benar dari server sebelum JavaScript jalan', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit(['harga_modal_satuan' => 12530171.82])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Margin 30% terhadap harga jual, dibulatkan ke atas.
    expect($isi)->toContain('value="17.901.000"')
        // Markup 30% akan memberi angka ini, dan marginnya cuma 23%.
        ->and($isi)->not->toContain('16.289.223');
});

test('keterangan margin efektif sudah dirender server, tidak menunggu JavaScript', function () {
    // Kalau baru muncul setelah Alpine jalan, tinggi barisnya bertambah sesudah
    // halaman tampil -- itu yang terlihat sebagai kedipan saat refresh.
    palsukanInventory(badanTab('produk_jadi', [barisUnit(['harga_modal_satuan' => 389343])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // 389.343 / 0,7 = 556.204 -> dibulatkan ke atas jadi 557.000 -> efektif 30,1%.
    expect($isi)->toContain('value="557.000"')
        ->and($isi)->toContain('margin efektif')
        ->and($isi)->toContain('>30,1</span>%');
});

test('baris yang pembulatannya tidak menggeser margin tidak memunculkan keterangannya', function () {
    // 700.000 / 0,7 = tepat 1.000.000, jadi tidak ada selisih yang perlu disebut.
    palsukanInventory(badanTab('produk_jadi', [barisUnit(['harga_modal_satuan' => 700000])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('value="1.000.000"')
        // Elemennya tetap ada untuk dipakai Alpine, tapi berangkat dalam keadaan tersembunyi.
        ->and($isi)->toContain('style="display: none;"');
});

test('bilah margin memakai margin bawaan dan menyimpannya per pengguna', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    expect($isi)->toContain('id="margin-target"')
        ->and($isi)->toContain('Margin target')
        ->and($isi)->toContain('setMarginGlobal($event.target.value)')
        ->and($isi)->toContain('x-data="marginHargaJual"');
});

test('catatan cakupan biaya wajib tampil di tab unit', function (string $diminta, string $kunci) {
    palsukanInventory(badanTab($kunci, [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    // Tanpa catatan ini, margin 30% di layar hampir pasti dibaca sebagai laba bersih.
    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => $diminta]))
        ->assertOk()
        ->assertSee('Harga modal hanya mencakup biaya bahan.')
        ->assertSee('Belum termasuk ongkos kirim bahan, upah produksi, dan overhead.');
})->with([
    ['produk-jadi', 'produk_jadi'],
    ['setengah-jadi', 'produk_setengah_jadi'],
]);

test('tab bahan tidak punya margin maupun harga jual', function () {
    // Bahan tidak dijual, jadi tidak ada harga jualnya.
    palsukanInventory(badanTab('bahan', [barisBahan()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)
        ->get(route('harga-modal.index', ['tab' => 'bahan']))
        ->assertOk()
        ->assertDontSee('Harga Jual')
        ->assertDontSee('Margin target')
        ->assertDontSee('marginHargaJual');
});

test('tiap baris bisa diubah sendiri dan dikembalikan ke margin global', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['serial_number' => 'SN-001']),
        barisUnit(['serial_number' => 'SN-002', 'harga_modal_satuan' => 2400000]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Indeks baris ditulis Blade, jadi tiap baris punya keadaannya sendiri.
    expect($isi)->toContain('setMarginBaris(0, $event.target.value)')
        ->and($isi)->toContain('setMarginBaris(1, $event.target.value)')
        ->and($isi)->toContain('setJualBaris(0, $event.target.value)')
        // Penanda visual dan tombol kembalikan hanya muncul saat baris diubah.
        ->and($isi)->toContain('diubah(0) ? \'border-amber-400\' : \'border-transparent\'')
        ->and($isi)->toContain('kembalikan(0)')
        ->and($isi)->toContain('Kembalikan ke margin global');
});

test('urutan indeks baris tetap sejajar walau ada baris tanpa harga modal', function () {
    palsukanInventory(badanTab('produk_jadi', [
        barisUnit(['nama_produk' => 'Tanpa Modal', 'harga_modal_satuan' => null]),
        barisUnit(['nama_produk' => 'Dengan Modal', 'harga_modal_satuan' => 1000000]),
    ]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // data-modal tetap ada walau kosong; kalau tidak, indeks yang dibaca JavaScript
    // akan bergeser dan harga jual mendarat di baris yang salah.
    expect(substr_count($isi, 'data-modal='))->toBe(2)
        ->and($isi)->toContain('data-modal=""')
        ->and($isi)->toContain('data-modal="1000000"');
});

test('baris tanpa harga modal tidak menawarkan harga jual', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit(['harga_modal_satuan' => null])]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $isi = $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk()->getContent();

    // Tanpa modal tidak ada dasar hitungannya; jangan menampilkan angka karangan.
    expect($isi)->not->toContain('setJualBaris(0,')
        ->and($isi)->not->toContain('setMarginBaris(0,');
});

/*
|--------------------------------------------------------------------------
| Menu sidebar
|--------------------------------------------------------------------------
*/

test('menu Harga Modal hanya muncul untuk yang berizin', function () {
    $berizin = penggunaHargaModal('dewi.priyambodo@yahoo.com');
    $tanpaIzin = penggunaHargaModal('staf.baru@example.com', denganIzin: false);

    $this->actingAs($berizin);
    expect(view('layouts.partials.sidebar')->render())->toContain(route('harga-modal.index'));

    $this->actingAs($tanpaIzin);
    expect(view('layouts.partials.sidebar')->render())->not->toContain(route('harga-modal.index'));
});

/*
|--------------------------------------------------------------------------
| Data tidak mengendap di CRM
|--------------------------------------------------------------------------
*/

test('harga modal tidak ditulis ke database CRM dan tiap kunjungan tanya ulang', function () {
    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk();
    $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk();

    Http::assertSentCount(2);

    // Store cache bawaan aplikasi ini adalah database, jadi tabel `cache` yang
    // tetap kosong membuktikan tidak ada baris HPP yang mengendap di CRM.
    expect(DB::table('cache')->count())->toBe(0);
});

test('cache yang dinyalakan dipisah per tab, tidak saling menimpa', function () {
    config()->set('services.inventory.harga_modal_cache_ttl', 60);
    config()->set('services.inventory.harga_modal_cache_store', 'array');

    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)->get(route('harga-modal.index', ['tab' => 'produk-jadi']))->assertOk();
    $this->actingAs($pengguna)->get(route('harga-modal.index', ['tab' => 'produk-jadi']))->assertOk();
    $this->actingAs($pengguna)->get(route('harga-modal.index', ['tab' => 'bahan']))->assertOk();

    // Tab kedua tidak boleh memakai simpanan tab pertama.
    Http::assertSentCount(2);
    expect(DB::table('cache')->count())->toBe(0);
});

test('cache dipisah antara yang tersaring dan yang tidak', function () {
    config()->set('services.inventory.harga_modal_cache_ttl', 60);
    config()->set('services.inventory.harga_modal_cache_store', 'array');

    palsukanInventory(badanTab('produk_jadi', [barisUnit()]));

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk();
    $this->actingAs($pengguna)->get(route('harga-modal.index', ['hanya_tersedia' => 1]))->assertOk();

    Http::assertSentCount(2);
});

test('jawaban gagal tidak pernah ikut disimpan ke cache', function () {
    config()->set('services.inventory.harga_modal_cache_ttl', 60);
    config()->set('services.inventory.harga_modal_cache_store', 'array');

    palsukanInventory(503);

    $pengguna = penggunaHargaModal('dewi.priyambodo@yahoo.com');

    $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk();
    $this->actingAs($pengguna)->get(route('harga-modal.index'))->assertOk();

    // Kalau kegagalan ikut di-cache, pemulihan di sisi inventory tidak akan terlihat
    // sampai TTL-nya habis.
    Http::assertSentCount(2);
});

/*
|--------------------------------------------------------------------------
| Izin lewat migration
|--------------------------------------------------------------------------
*/

test('izin harga modal terbentuk lewat migration tanpa menempel ke role mana pun', function () {
    $izin = Permission::where('slug', 'view-harga-modal')->first();

    expect($izin)->not->toBeNull()
        ->and($izin->group)->toBe('Harga Modal');

    // Siapa yang boleh melihat HPP ditentukan lewat Kelola Roles, bukan oleh deploy.
    expect(DB::table('permission_role')->where('permission_id', $izin->id)->count())->toBe(0);
});
