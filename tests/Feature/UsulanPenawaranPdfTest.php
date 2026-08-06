<?php

use App\Http\Controllers\PenawaranHargaController;
use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
use App\Models\PenawaranSignature;
use App\Models\PenawaranTerm;
use App\Models\PenawaranValidity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UsulanItem;
use App\Models\UsulanPenawaran;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

function usulanPdfUser(Company $company, string $label): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Usulan PDF '.$label,
        'slug' => 'usulan-pdf-'.strtolower($label).'-'.uniqid(),
    ]);
    foreach (['view-usulan', 'create-usulan', 'edit-usulan', 'view-penawaran', 'edit-penawaran'] as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Usulan']
        );
        $role->permissions()->attach($permission->id);
    }
    $user->roles()->attach($role->id);

    return $user;
}

function usulanPdfFixture(): array
{
    $sender = Company::create([
        'code' => 'AS',
        'name' => 'CV. Arta Solusindo',
        'address' => 'Juwangen, Purwomartani, Kalasan, Sleman',
        'email' => 'sender@example.test',
        'phone' => '0274 5044026',
    ]);
    $recipient = Company::create([
        'code' => 'ATC',
        'name' => 'PT Arta Teknologi Comunindo',
        'address' => 'Jl. Kaliurang, Sleman',
        'email' => 'recipient@example.test',
        'phone' => '0274 4986889',
    ]);
    $creator = usulanPdfUser($sender, 'Creator');

    $usulan = UsulanPenawaran::create([
        'company_id' => $sender->id,
        'target_company_id' => $recipient->id,
        'judul' => 'Pengadaan Sistem Monitoring',
        'jenis_transaksi' => 'campuran',
        'deskripsi' => 'Spesifikasi lengkap sesuai daftar kebutuhan.',
        'nilai_estimasi' => 25000000,
        'created_by' => $creator->id,
        'status' => 'menunggu',
    ]);
    $usulan->forceFill([
        'created_at' => Carbon::create(2026, 8, 4, 9, 0),
        'updated_at' => Carbon::create(2026, 8, 4, 9, 0),
    ])->save();

    UsulanItem::create([
        'usulan_id' => $usulan->id,
        'urutan' => 1,
        'judul' => 'Beacon Data Logger AWGC',
        'catatan' => "Datalogger BL-1100 System Series\nDedicated power supply",
        'qty' => 2,
        'satuan' => 'Paket',
        'harga' => 12500000,
        'subtotal' => 25000000,
    ]);

    return compact('sender', 'recipient', 'creator', 'usulan');
}

function usulanPdfResponseContent($response): string
{
    return $response->baseResponse instanceof StreamedResponse
        ? $response->streamedContent()
        : $response->getContent();
}

function usulanQuotationFixture(): array
{
    $fixture = usulanPdfFixture();
    $seller = usulanPdfUser($fixture['recipient'], 'Quotation Seller');
    $docNumber = DocNumber::create([
        'company_id' => $fixture['recipient']->id,
        'prefix' => 'SPH06',
        'doc_type' => 'penawaran',
        'user_code' => 'SPH06',
        'seq' => 1,
        'month' => 8,
        'year' => 2026,
        'doc_no' => '001/SPH06/ATC/VIII/2026',
    ]);
    $penawaran = Penawaran::create([
        'company_id' => $fixture['recipient']->id,
        'id_user' => $seller->id,
        'doc_number_id' => $docNumber->id,
        'judul' => 'Bracket Pos Slinga',
        'catatan' => 'Harga FOB Daerah Istimewa Yogyakarta.',
        'instansi_tujuan' => $fixture['sender']->name,
        'nama_pekerjaan' => 'Pengadaan Bracket Pos Slinga',
        'tanggal_penawaran' => '2026-08-14',
        'date_created' => Carbon::create(2026, 8, 14, 9, 0)->timestamp,
        'date_updated' => Carbon::create(2026, 8, 14, 9, 0)->timestamp,
        'discount_enabled' => true,
        'discount_type' => 'percent',
        'discount_value' => 25,
        'tax_enabled' => false,
    ]);
    $item = PenawaranItem::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'judul' => 'Bracket Pos Slinga',
        'qty' => 1,
        'satuan' => 'Set',
        'subtotal' => 9599625,
    ]);
    PenawaranItemDetail::create([
        'penawaran_item_id' => $item->id,
        'urutan' => 1,
        'nama' => 'Bracket Pos Slinga',
        'spesifikasi' => 'Material baja galvanis',
        'qty' => 1,
        'satuan' => 'Set',
        'harga' => 9599625,
        'subtotal' => 9599625,
    ]);
    PenawaranTerm::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'judul' => 'Pembayaran',
        'isi' => 'Barang diproses setelah PO dan DP diterima.',
    ]);
    PenawaranValidity::create([
        'penawaran_id' => $penawaran->id,
        'mulai' => '2026-08-14',
        'sampai' => '2026-12-31',
        'berlaku_hari' => 139,
    ]);
    PenawaranSignature::create([
        'penawaran_id' => $penawaran->id,
        'urutan' => 1,
        'nama' => 'Zaini Noverna Sandi',
        'jabatan' => 'Project Operation Administration',
        'kota' => 'Yogyakarta',
        'tanggal' => '2026-08-14',
    ]);

    $penawaran->sharedCompanies()->attach($fixture['sender']->id);
    $fixture['usulan']->update([
        'penawaran_id' => $penawaran->id,
        'penawaran_status' => 'sent',
    ]);

    return array_merge($fixture, compact('seller', 'penawaran'));
}

test('requester and recipient can export the quotation request pdf but another company cannot', function () {
    ['recipient' => $recipient, 'creator' => $creator, 'usulan' => $usulan] = usulanPdfFixture();
    $recipientUser = usulanPdfUser($recipient, 'Recipient');
    $outsider = Company::create(['code' => 'OTHER', 'name' => 'PT Lain']);
    $outsiderUser = usulanPdfUser($outsider, 'Outsider');

    foreach ([$creator, $recipientUser] as $allowedUser) {
        $response = $this->actingAs($allowedUser)->get(route('penawaran-harga.pdf', $usulan));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        expect(usulanPdfResponseContent($response))->toStartWith('%PDF');
    }

    $this->actingAs($outsiderUser)
        ->get(route('penawaran-harga.pdf', $usulan))
        ->assertForbidden();
});

test('quotation request document contains request details without price fields', function () {
    ['sender' => $sender, 'usulan' => $usulan] = usulanPdfFixture();
    $usulan->load(['company', 'targetCompany', 'pic', 'creator.roles', 'items']);

    $html = view('penawaran_harga.pdf', [
        'usulan' => $usulan,
        'documentDate' => Carbon::create(2026, 8, 4, 9, 0),
        'documentNumber' => sprintf('%03d/PP-AS/VIII/2026', $usulan->id),
        'kop' => [
            'logo' => public_path('images/logo_arsol.png'),
            'stamp' => null,
            'name' => $sender->name,
            'address' => $sender->address,
            'phone' => $sender->phone,
            'email' => $sender->email,
        ],
        'signaturePath' => null,
    ])->render();

    expect($html)
        ->toContain('PERMOHONAN PENAWARAN')
        ->toContain('PT Arta Teknologi Comunindo')
        ->toContain('Beacon Data Logger AWGC')
        ->toContain('Datalogger BL-1100 System Series')
        ->toContain('<ol class="item-points" type="a">')
        ->toContain('Jumlah')
        ->toContain('Satuan')
        ->not->toContain('12.500.000')
        ->not->toContain('25.000.000')
        ->not->toContain('Nilai Estimasi')
        ->not->toContain('Subtotal');
});

test('request item forms provide manual point controls for every item', function () {
    $create = file_get_contents(resource_path('views/penawaran_harga/create.blade.php'));
    $edit = file_get_contents(resource_path('views/penawaran_harga/edit.blade.php'));
    $row = file_get_contents(resource_path('views/penawaran_harga/partials/item-row.blade.php'));

    foreach ([$create, $edit] as $form) {
        expect($form)
            ->toContain('function addItemPoint')
            ->toContain('function removeItemPoint')
            ->toContain('renderPointInput')
            ->toContain('item_poin[');
    }

    expect($row)
        ->toContain('Detail / Spesifikasi per Poin')
        ->toContain('+ Tambah Poin')
        ->toContain('name="item_poin[{{ $index }}][]"');
});

test('request signature can be imported while creating and replaced while editing', function () {
    Storage::fake('public');
    ['recipient' => $recipient, 'creator' => $creator] = usulanPdfFixture();

    $this->actingAs($creator)
        ->get(route('penawaran-harga.create'))
        ->assertOk()
        ->assertSee('Import / Upload TTD (opsional)');

    $this->actingAs($creator)
        ->post(route('penawaran-harga.store'), [
            'target_company_id' => $recipient->id,
            'judul' => 'Permohonan dengan TTD dari form buat',
            'jenis_transaksi' => 'barang',
            'status' => 'draft',
            'signature_name' => 'Megatri Ika Listina Dewi',
            'signature_position' => 'Corporate Account Manager',
            'signature_city' => 'Yogyakarta',
            'signature_date' => '2026-08-05',
            'signature_file' => UploadedFile::fake()->image('ttd-buat.png'),
        ])
        ->assertRedirect();

    $request = UsulanPenawaran::where('judul', 'Permohonan dengan TTD dari form buat')->firstOrFail();
    $firstPath = $request->signature_path;
    expect($firstPath)->toStartWith('usulan/ttd/')
        ->and($request->signature_name)->toBe('Megatri Ika Listina Dewi');
    Storage::disk('public')->assertExists($firstPath);

    $this->actingAs($creator)
        ->get(route('penawaran-harga.edit', $request))
        ->assertOk()
        ->assertSee('Import TTD pengganti (opsional)');

    $this->actingAs($creator)
        ->put(route('penawaran-harga.update', $request), [
            'target_company_id' => $recipient->id,
            'judul' => $request->judul,
            'jenis_transaksi' => 'barang',
            'status' => 'menunggu',
            'signature_name' => 'Megatri Ika Listina Dewi',
            'signature_position' => 'Account Manager',
            'signature_city' => 'Sleman',
            'signature_date' => '2026-08-06',
            'signature_file' => UploadedFile::fake()->image('ttd-edit.png'),
        ])
        ->assertRedirect(route('penawaran-harga.show', $request));

    $secondPath = $request->refresh()->signature_path;
    expect($secondPath)->toStartWith('usulan/ttd/')
        ->and($secondPath)->not->toBe($firstPath)
        ->and($request->signature_position)->toBe('Account Manager')
        ->and($request->signature_city)->toBe('Sleman');
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('request pdf centers visible signature strokes instead of the transparent image canvas', function () {
    Storage::fake('public');
    $image = imagecreatetruecolor(200, 100);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
    imagefill($image, 0, 0, $transparent);
    $ink = imagecolorallocatealpha($image, 20, 20, 20, 0);
    imagefilledrectangle($image, 145, 30, 190, 70, $ink);
    ob_start();
    imagepng($image);
    $content = ob_get_clean();
    imagedestroy($image);
    Storage::disk('public')->put('usulan/ttd/right-heavy.png', $content);

    $controller = app(PenawaranHargaController::class);
    $method = new ReflectionMethod($controller, 'pdfSignaturePlacement');
    $placement = $method->invoke(
        $controller,
        Storage::disk('public')->path('usulan/ttd/right-heavy.png')
    );
    $boxCenteredLeft = (220 - $placement['width']) / 2;

    expect($placement['left'])->toBeLessThan($boxCenteredLeft)
        ->and($placement['width'])->toBeGreaterThan(0)
        ->and($placement['height'])->toBeGreaterThan(0);

    // Dasar coretan -- bukan dasar kanvas -- yang dirapatkan ke dasar kotak setinggi
    // 100px, meniru bottom:0 pada Penawaran Harga biasa. Kalau coretan kembali
    // dipusatkan, dia akan tertimbun bagian tebal cap yang juga berpusat di tengah.
    $scale = $placement['width'] / 200;
    $inkBottomOnPage = $placement['top'] + (71 * $scale); // coretan digambar sampai y=70
    expect(abs($inkBottomOnPage - 100))->toBeLessThan(4.0);
});

test('signature scales by its visible strokes so canvas padding does not change its size', function () {
    Storage::fake('public');
    $controller = app(PenawaranHargaController::class);
    $method = new ReflectionMethod($controller, 'pdfSignaturePlacement');

    // Coretan berukuran sama (200x120 px) di tengah kanvas dengan ruang kosong yang
    // sangat berbeda. Ukuran tampil coretannya harus tetap sama -- kalau penskalaan
    // kembali memakai ukuran kanvas, TTD akan tampil besar-kecil tak menentu
    // tergantung seberapa banyak ruang kosong pada file yang diunggah.
    $inkWidths = [];

    foreach ([[200, 120], [400, 300], [700, 900], [300, 800]] as [$canvasWidth, $canvasHeight]) {
        $img = imagecreatetruecolor($canvasWidth, $canvasHeight);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 255, 255, 255, 127));
        imagealphablending($img, true);
        $ink = imagecolorallocatealpha($img, 15, 25, 90, 0);
        imagesetthickness($img, 6);
        $offsetX = (int) (($canvasWidth - 200) / 2);
        $offsetY = (int) (($canvasHeight - 120) / 2);
        imagerectangle($img, $offsetX, $offsetY, $offsetX + 199, $offsetY + 119, $ink);
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        $file = sprintf('usulan/ttd/ink-%dx%d.png', $canvasWidth, $canvasHeight);
        Storage::disk('public')->put($file, $png);
        $placement = $method->invoke($controller, Storage::disk('public')->path($file));

        $inkWidths[] = 200 * ($placement['width'] / $canvasWidth);
    }

    // Semua dalam 10% dari sasaran 100px, apa pun ukuran kanvasnya.
    foreach ($inkWidths as $inkWidth) {
        expect($inkWidth)->toBeGreaterThan(90.0)->toBeLessThan(110.0);
    }
});

test('signature without transparency is scaled by its canvas instead of its strokes', function () {
    Storage::fake('public');
    // Hasil pindai berlatar putih: seluruh kanvas adalah isinya, jadi kanvas itu yang
    // diskala. Kalau dipaksa mengikuti kotak coretan, latar putihnya ikut membesar dan
    // menutupi baris nama di bawah kotak TTD.
    $img = imagecreatetruecolor(400, 400);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $white);
    $ink = imagecolorallocate($img, 20, 20, 20);
    imagesetthickness($img, 6);
    imagerectangle($img, 150, 170, 250, 230, $ink);
    ob_start();
    imagepng($img);
    $png = ob_get_clean();
    imagedestroy($img);

    Storage::disk('public')->put('usulan/ttd/opaque.png', $png);

    $controller = app(PenawaranHargaController::class);
    $method = new ReflectionMethod($controller, 'pdfSignaturePlacement');
    $placement = $method->invoke($controller, Storage::disk('public')->path('usulan/ttd/opaque.png'));

    expect($placement['width'])->toBe(100.0)
        ->and($placement['height'])->toBe(100.0)
        ->and($placement['top'])->toBe(0.0)
        ->and($placement['left'])->toBe(60.0);
});

test('request and quotation signatures can be imported independently', function () {
    Storage::fake('public');
    ['creator' => $buyer, 'seller' => $seller, 'usulan' => $usulan, 'penawaran' => $penawaran] = usulanQuotationFixture();

    $this->actingAs($buyer)
        ->post(route('penawaran-harga.signature.update', $usulan), [
            'signature_name' => 'Megatri Ika Listina Dewi',
            'signature_position' => 'Corporate Account Manager',
            'signature_city' => 'Yogyakarta',
            'signature_date' => '2026-08-04',
            'signature_file' => UploadedFile::fake()->image('ttd-permohonan.png'),
        ])
        ->assertRedirect(route('penawaran-harga.show', $usulan));

    $this->actingAs($seller)
        ->post(route('penawaran.signatures.add', $penawaran), [
            'nama' => 'Zaini Noverna Sandi',
            'jabatan' => 'Project Operation Administration',
            'kota' => 'Yogyakarta',
            'tanggal' => '2026-08-14',
            'ttd' => UploadedFile::fake()->image('ttd-penawaran.png'),
        ])
        ->assertRedirect(route('penawaran.show', $penawaran));

    $requestSignature = $usulan->refresh()->signature_path;
    $quotationSignature = $penawaran->signatures()->firstOrFail()->ttd_path;

    expect($requestSignature)->toStartWith('usulan/ttd/')
        ->and($quotationSignature)->toStartWith('penawaran/ttd/')
        ->and($requestSignature)->not->toBe($quotationSignature);
    Storage::disk('public')->assertExists([$requestSignature, $quotationSignature]);
});

test('linked quotation price pdf is available to buyer and seller but not another company', function () {
    ['creator' => $buyer, 'seller' => $seller, 'usulan' => $usulan] = usulanQuotationFixture();
    $outsiderCompany = Company::create(['code' => 'QUOTE-OTHER', 'name' => 'PT Tidak Terkait']);
    $outsider = usulanPdfUser($outsiderCompany, 'Quotation Outsider');

    foreach ([$buyer, $seller] as $allowedUser) {
        $response = $this->actingAs($allowedUser)->get(route('penawaran-harga.quotation.pdf', $usulan));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        expect(usulanPdfResponseContent($response))->toStartWith('%PDF');
    }

    $this->actingAs($outsider)
        ->get(route('penawaran-harga.quotation.pdf', $usulan))
        ->assertForbidden();
});

test('quotation price document references its request and calculates discount and total', function () {
    ['sender' => $sender, 'usulan' => $usulan, 'penawaran' => $penawaran] = usulanQuotationFixture();
    $usulan->load([
        'company', 'targetCompany', 'pic', 'penawaran.company', 'penawaran.user.roles',
        'penawaran.docNumber', 'penawaran.items.product', 'penawaran.items.details',
        'penawaran.terms', 'penawaran.validity', 'penawaran.signatures',
    ]);
    $penawaran = $usulan->penawaran;

    $html = view('penawaran_harga.quotation_pdf', [
        'usulan' => $usulan,
        'penawaran' => $penawaran,
        'requestDate' => Carbon::create(2026, 8, 4, 9, 0),
        'requestDocumentNumber' => sprintf('%03d/PP-AS/VIII/2026', $usulan->id),
        'quotationDate' => Carbon::create(2026, 8, 14, 9, 0),
        'quotationNumber' => '001/SPH06/ATC/VIII/2026',
        'kop' => [
            'logo' => null,
            'stamp' => null,
            'name' => $penawaran->company->name,
            'address' => $penawaran->company->address,
            'phone' => $penawaran->company->phone,
            'email' => $penawaran->company->email,
        ],
        'signature' => $penawaran->signatures->first(),
        'signaturePath' => null,
    ])->render();

    expect($html)
        ->toContain('PENAWARAN HARGA')
        ->toContain($sender->name)
        ->toContain(sprintf('%03d/PP-AS/VIII/2026', $usulan->id))
        ->toContain('001/SPH06/ATC/VIII/2026')
        ->toContain('Bracket Pos Slinga')
        ->toContain('Material baja galvanis')
        ->toContain('9.599.625')
        ->toContain('DISKON 25 %')
        ->toContain('2.399.906')
        ->toContain('7.199.719')
        ->toContain('Barang diproses setelah PO dan DP diterima.')
        ->toContain('Zaini Noverna Sandi');
});

test('quotation signature block follows the same positioning contract as request pdf', function () {
    $requestTemplate = file_get_contents(resource_path('views/penawaran_harga/pdf.blade.php'));
    $quotationTemplate = file_get_contents(resource_path('views/penawaran_harga/quotation_pdf.blade.php'));

    // Kontrak geometri disamakan dengan dokumen Penawaran Harga biasa:
    // kotak 220x100, cap selebar 220px di tengah dengan opacity 0,5.
    foreach ([
        '.signature-wrap {',
        'width: 270px;',
        'margin-top: 20px;',
        'margin-left: auto;',
        'width: 220px;',
        'height: 100px;',
        'transform: translate(-50%, -50%);',
        'opacity: .5;',
        'z-index: 2;',
        'z-index: 1;',
        'class="signature-images"',
    ] as $positioningRule) {
        expect($requestTemplate)->toContain($positioningRule)
            ->and($quotationTemplate)->toContain($positioningRule);
    }

    // Cap harus menimpa TTD, jadi <img class="signature"> ditulis lebih dulu
    // dan diberi z-index lebih rendah -- sama seperti penawaran biasa.
    foreach ([$requestTemplate, $quotationTemplate] as $template) {
        expect(strpos($template, 'class="signature" src='))
            ->toBeLessThan(strpos($template, 'class="stamp" src='));
    }
});

test('sidebar keeps Usulan and adds a separate Penawaran Harga group', function () {
    $f = usulanPdfFixture();

    $html = $this->actingAs($f['creator'])->get(route('usulan.index'))->assertOk()->getContent();

    expect($html)
        ->toContain('<span>Usulan</span>')
        ->toContain('<span>Daftar Usulan</span>')
        ->toContain('<span>Penawaran Harga</span>')
        ->toContain('<span>Daftar Penawaran Harga</span>')
        // Penamaan lama dari commit borongan tidak boleh kembali.
        ->not()->toContain('<span>Permintaan Harga</span>')
        ->not()->toContain('<span>Daftar Permintaan</span>');
});

test('usulan module only lists internal proposals and penawaran harga only inter-company ones', function () {
    $f = usulanPdfFixture();          // punya target_company_id -> Penawaran Harga
    $internal = UsulanPenawaran::create([
        'company_id' => $f['sender']->id,
        'judul' => 'Usulan Internal Pengadaan Laptop',
        'deskripsi' => 'Kebutuhan internal tim.',
        'nilai_estimasi' => 15000000,
        'created_by' => $f['creator']->id,
        'status' => 'menunggu',
    ]);

    // Daftar Usulan: hanya yang tanpa perusahaan tujuan.
    $this->actingAs($f['creator'])
        ->get(route('usulan.index'))
        ->assertOk()
        ->assertSee('Usulan Internal Pengadaan Laptop')
        ->assertDontSee($f['usulan']->judul);

    // Daftar Penawaran Harga: hanya yang punya perusahaan tujuan.
    $this->actingAs($f['creator'])
        ->get(route('penawaran-harga.index'))
        ->assertOk()
        ->assertSee($f['usulan']->judul)
        ->assertDontSee('Usulan Internal Pengadaan Laptop');

    // Form Usulan kembali ke bentuk production: tanpa perusahaan tujuan dan TTD.
    $usulanForm = $this->actingAs($f['creator'])->get(route('usulan.create'))->assertOk()->getContent();
    expect($usulanForm)
        ->toContain('Buat Usulan Penawaran')
        ->not()->toContain('Kirim ke Perusahaan')
        ->not()->toContain('Jenis Transaksi');

    // Form Penawaran Harga tetap membawa isian alur antar perusahaan.
    $phForm = $this->actingAs($f['creator'])->get(route('penawaran-harga.create'))->assertOk()->getContent();
    expect($phForm)
        ->toContain('Kirim ke Perusahaan')
        ->toContain('Jenis Transaksi');

    // Penerbitan penawaran tidak lagi tersedia dari modul Usulan.
    expect(\Illuminate\Support\Facades\Route::has('usulan.buat-penawaran'))->toBeFalse();
});
