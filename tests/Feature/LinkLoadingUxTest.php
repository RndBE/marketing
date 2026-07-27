<?php

test('global javascript provides loading states for important navigation links', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain('window.linkLoading')
        ->toContain('setLinkLoading')
        ->toContain('isCompactLoadingLink')
        ->toContain('isCurrentPageLink')
        ->toContain('isLoadableLink')
        ->toContain('data-no-link-loading')
        ->toContain('data-link-loading-original-html')
        ->toContain('data-download-loading')
        ->toContain('downloadTimeout')
        ->toContain("link.closest('#application-sidebar')")
        ->toContain('window.linkLoading.isCurrentPageLink(link)')
        ->toContain('data-compact-link-loading')
        ->toContain('Membuka...')
        ->toContain('Menyiapkan...')
        ->toContain("link.target && link.target !== '_self' && !link.matches('[data-download-loading]')")
        ->toContain("window.addEventListener('pageshow'")
        ->toContain("window.addEventListener('pagehide'");
});

test('sidebar navigation links are excluded from per-link loading animations', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain("|| link.closest('#application-sidebar')")
        ->not->toContain('loadingJustifyClassFor');
});

test('penawaran important links opt into consistent navigation and download feedback', function () {
    $contents = file_get_contents(resource_path('views/penawaran/index.blade.php'));
    $actions = file_get_contents(resource_path('views/penawaran/partials/actions.blade.php'));

    expect($contents)
        ->toContain('id="btn-export-excel"')
        ->toContain('data-download-loading')
        ->toContain('data-loading-label="Menyiapkan..."')
        ->toContain('data-penawaran-detail-link');

    expect($actions)
        ->toContain('data-compact-link-loading')
        ->toContain('Detail');
});

test('generated document links expose a dedicated download loading state', function () {
    $penawaranShow = file_get_contents(resource_path('views/penawaran/show.blade.php'));
    $invoiceShow = file_get_contents(resource_path('views/invoices/show.blade.php'));
    $leadReportShow = file_get_contents(resource_path('views/lead_reports/show.blade.php'));

    expect($penawaranShow)
        ->toContain("\$pdfRouteKey = \$penawaran->pdfRouteKey()")
        ->toContain("route('penawaran.pdf', \$pdfRouteKey)")
        ->toContain('Item {{ $itemIndex + 1 }}')
        ->toContain('{{ $detailLabel($loop->index) }}.')
        ->toContain('data-download-loading')
        ->toContain('data-loading-label="Menyiapkan PDF..."')
        ->toContain('data-download-timeout="30000"')
        ->toContain('data-penawaran-pdf-link');

    expect($invoiceShow)
        ->toContain("route('invoices.pdf', \$invoice->id)")
        ->toContain('target="_blank"')
        ->toContain('data-download-loading')
        ->toContain('data-loading-label="Menyiapkan PDF..."')
        ->toContain('data-download-timeout="30000"')
        ->toContain('data-invoice-pdf-link');

    expect($leadReportShow)
        ->toContain("route('lead-reports.download', \$leadReport)")
        ->toContain('data-download-loading')
        ->toContain('data-loading-label="Menyiapkan unduhan..."');
});

test('common list pages keep ordinary detail and edit anchors available for global link loading', function () {
    foreach ([
        'invoices/index.blade.php',
        'price_list/index.blade.php',
        'prospects/index.blade.php',
        'users/index.blade.php',
    ] as $view) {
        $contents = file_get_contents(resource_path('views/'.$view));

        expect($contents)
            ->toContain('<a ')
            ->not->toContain('data-no-link-loading');
    }
});
