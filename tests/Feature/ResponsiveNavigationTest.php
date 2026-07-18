<?php

use App\Models\Invoice;
use App\Models\User;

test('authenticated layout exposes an accessible mobile navigation drawer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSee('data-mobile-sidebar', false)
        ->assertSee('data-sidebar-backdrop', false)
        ->assertSee('aria-label="Buka navigasi"', false)
        ->assertSee('aria-label="Tutup navigasi"', false);
});

test('invoice navigation is not tied to usulan permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSee('Daftar Invoice')
        ->assertSee('Buat Invoice');
});

test('invoice template page keeps the invoice navigation group open', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('templates.index'))
        ->assertOk()
        ->assertSee("invoice: true", false)
        ->assertSee('Atur Template')
        ->assertSee('bg-slate-900 text-white', false)
        ->assertSee('x-show="invoice" x-collapse style=""', false)
        ->assertSee('x-show="penawaran" x-collapse style="display: none;"', false);
});

test('application shell exposes synchronized accessible sidebar transitions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('penawaran.index'))
        ->assertOk()
        ->assertSee('data-sidebar-content', false)
        ->assertSee('x-transition:leave-start="translate-x-0"', false)
        ->assertSee('x-transition:leave-end="-translate-x-full"', false)
        ->assertSee('x-transition:enter-start="opacity-0"', false)
        ->assertSee('x-transition:enter="transition-opacity duration-300 ease-out motion-reduce:transition-none"', false)
        ->assertDontSee('duration-250', false)
        ->assertSee('motion-reduce:transition-none', false);
});

test('desktop sidebar keeps its initial layout while alpine boots', function () {
    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
    $sidebar = file_get_contents(resource_path('views/layouts/partials/sidebar.blade.php'));
    $css = file_get_contents(resource_path('css/app.css'));

    expect($layout)
        ->toContain('data-sidebar-preload="true"')
        ->toContain('data-sidebar-open="true"')
        ->toContain("document.documentElement.dataset.sidebarOpen")
        ->toContain("document.documentElement.removeAttribute('data-sidebar-preload')");

    expect($sidebar)
        ->toContain('data-mobile-sidebar')
        ->toContain('$sidebarGroups')
        ->toContain('$sidebarPanelStyle')
        ->not->toContain('data-mobile-sidebar x-cloak');

    expect($css)
        ->toContain('html[data-sidebar-preload="true"][data-sidebar-open="true"] [data-sidebar-content]')
        ->toContain('margin-left: 16rem');
});

test('mobile application shell can close the sidebar without horizontal layout overflow', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertSee('data-sidebar-close', false)
        ->assertSee('mobileSidebarOpen = !mobileSidebarOpen', false)
        ->assertSee('data-app-shell', false)
        ->assertSee('data-app-main', false)
        ->assertSee('min-w-0', false);
});

test('invoice index provides separate desktop table and mobile card views', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertSee('data-invoice-mobile-empty', false);

    Invoice::create([
        'user_id' => $user->id,
        'company_id' => $user->company_id,
        'judul' => 'Invoice Responsif Mobile',
        'tgl_invoice' => '2026-07-16',
        'jatuh_tempo' => '2026-07-30',
        'status' => 'sent',
        'grand_total' => 12500000,
    ]);

    $this->actingAs($user)
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertSee('data-invoice-desktop-table', false)
        ->assertSee('data-invoice-mobile-list', false)
        ->assertSee('hidden md:block', false)
        ->assertSee('md:hidden', false)
        ->assertSee('Invoice Responsif Mobile')
        ->assertSee('Rp 12.500.000')
        ->assertSee('Sent');
});

test('role bundle and proposal lists use one consistent floating action pattern', function () {
    expect(view()->exists('components.table-actions'))->toBeTrue();

    foreach ([
        'roles/index.blade.php' => ['scope="role"', 'data-primary-action="permissions"'],
        'price_list/index.blade.php' => ['scope="bundle"', 'data-primary-action="detail"'],
        'usulan/index.blade.php' => ['scope="usulan"', 'data-primary-action="detail"'],
    ] as $view => $markers) {
        $contents = file_get_contents(resource_path('views/'.$view));

        expect($contents)
            ->toContain('<x-table-actions')
            ->toContain($markers[0])
            ->toContain($markers[1]);
    }
});

test('bundle detail keeps desktop actions on one row beside long content', function () {
    $contents = file_get_contents(resource_path('views/price_list/show.blade.php'));

    expect($contents)
        ->toContain('class="min-w-0 flex-1"')
        ->toContain('class="flex flex-wrap gap-2 md:shrink-0 md:flex-nowrap"');
});

test('bundle detail provides navigation back to the bundle list', function () {
    $contents = file_get_contents(resource_path('views/price_list/show.blade.php'));

    expect($contents)
        ->toContain('data-bundle-back-link')
        ->toContain("route('price_list.index')")
        ->toContain('Kembali ke Daftar Bundle');
});

test('bundle detail uses the global accessible deletion modal instead of native confirmation', function () {
    $contents = file_get_contents(resource_path('views/price_list/show.blade.php'));

    expect($contents)
        ->toContain('data-confirm-title="Hapus Bundle?"')
        ->toContain('data-confirm-delete=')
        ->not->toContain('data-bundle-delete-modal')
        ->not->toContain("onsubmit=\"return confirm('Hapus bundle ini?')\"");
});

test('bundle detail keeps compact information without item count summaries', function () {
    $contents = file_get_contents(resource_path('views/price_list/show.blade.php'));

    expect($contents)
        ->toContain('Harga Satuan Bundle: Rp')
        ->not->toContain('data-bundle-summary')
        ->not->toContain('data-bundle-status')
        ->not->toContain('data-bundle-summary-item-count')
        ->not->toContain('data-bundle-list-item-count');
});
